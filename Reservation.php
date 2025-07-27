<?php
// Start session at the very top to prevent 'headers already sent' errors.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'cnx.php';

/**
 * Sends an email using PHP's native mail() function.
 *
 * @param string $recipient Email address of the recipient.
 * @param string $subject Subject of the email.
 * @param string $message HTML content of the email.
 * @return bool True if email was sent successfully, false otherwise.
 */
function sendReservationEmail(string $recipient, string $subject, string $message): bool
{
    error_log("Attempting to send email to: " . $recipient);

    $headers = "From: reservations@legourmet.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    if (mail($recipient, $subject, $message, $headers)) {
        error_log("Email sent successfully via native mail() to: " . $recipient);
        return true;
    } else {
        error_log("Email sending failed via native mail() to $recipient: " . (error_get_last() ? error_get_last()['message'] : 'Unknown error'));
        return false;
    }
}

/**
 * Checks table availability for a given date, time, and number of people.
 *
 * @param mysqli $cnx Database connection object.
 * @param string $date Reservation date (Y-m-d).
 * @param string $time Reservation time (H:i).
 * @param int $numPeople Number of people for the reservation.
 * @return array Associative array with 'available' (bool), 'tablesNeeded' (int), and 'tablesAvailable' (int).
 */
function checkTableAvailability(mysqli $cnx, string $date, string $time, int $numPeople): array
{
    $dateTime = date('Y-m-d H:i:s', strtotime($date . ' ' . $time));
    $tablesNeeded = (int)ceil($numPeople / 4);
    $windowStart = date('Y-m-d H:i:s', strtotime($dateTime . ' -1 hour'));
    $windowEnd = date('Y-m-d H:i:s', strtotime($dateTime . ' +2 hours'));

    $stmt = $cnx->prepare("SELECT SUM(CEIL(personnes/4)) as reserved_tables FROM reservations WHERE heure BETWEEN ? AND ? AND statut != 'annulée'");
    if (!$stmt) {
        error_log("Prepare failed: " . $cnx->error);
        return ['available' => false, 'tablesNeeded' => $tablesNeeded, 'tablesAvailable' => 0];
    }
    $stmt->bind_param("ss", $windowStart, $windowEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $reservedTables = $row['reserved_tables'] ?? 0;
    $totalTables = 5; // Updated to match current table count (adjust if you add more tables)
    $availableTables = $totalTables - $reservedTables;

    return [
        'available' => $availableTables >= $tablesNeeded,
        'tablesNeeded' => $tablesNeeded,
        'tablesAvailable' => $availableTables
    ];
}

/**
 * Generates a unique reservation number.
 *
 * @return string Unique reservation number.
 */
function generateReservationNumber(): string
{
    return 'RES-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

// Initialize form data and errors
$formData = [
    'nom' => '',
    'email' => '',
    'telephone' => '',
    'date' => '',
    'heure' => '',
    'personnes' => ''
];
$errors = [];
$successMessage = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and validate input
    $formData['nom'] = trim($_POST['nom'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['telephone'] = trim($_POST['telephone'] ?? '');
    $formData['date'] = $_POST['date'] ?? '';
    $formData['heure'] = $_POST['heure'] ?? '';
    $formData['personnes'] = (int)($_POST['personnes'] ?? 0);

    // Basic validation
    if (empty($formData['nom'])) {
        $errors[] = "Le nom est requis.";
    }
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide.";
    }
    if (empty($formData['telephone']) || !preg_match("/^[0-9+\s()-]{8,15}$/", $formData['telephone'])) {
        $errors[] = "Numéro de téléphone invalide.";
    }

    // Date validation
    $currentDate = date('Y-m-d');
    if (empty($formData['date']) || $formData['date'] < $currentDate) {
        $errors[] = "La date de réservation ne peut pas être dans le passé.";
    }

    // Time validation (12h - 22h)
    $reservationHour = (int)explode(':', $formData['heure'])[0];
    if (empty($formData['heure']) || $reservationHour < 12 || $reservationHour > 22) {
        $errors[] = "Les réservations sont possibles entre 12h et 22h.";
    }

    // Number of people validation
    if ($formData['personnes'] < 1 || $formData['personnes'] > 20) {
        $errors[] = "Le nombre de personnes doit être entre 1 et 20.";
    }

    // Table selection validation (if applicable)
    $selectedTables = $_POST['tables'] ?? [];
    if (empty($selectedTables)) {
        $errors[] = "Vous devez sélectionner au moins une table.";
    } elseif (count($selectedTables) > 3) {
        $errors[] = "Vous ne pouvez pas réserver plus de 3 tables.";
    } else {
        // Verify selected tables are still free (security check)
        $placeholders = implode(',', array_fill(0, count($selectedTables), '?'));
        $types = str_repeat('i', count($selectedTables));

        $stmtTables = $cnx->prepare("SELECT COUNT(*) as count FROM tables WHERE id IN ($placeholders) AND statut = 'libre'");
        if (!$stmtTables) {
            error_log("Prepare failed for table check: " . $cnx->error);
            $errors[] = "Erreur interne lors de la vérification des tables.";
        } else {
            $stmtTables->bind_param($types, ...$selectedTables);
            $stmtTables->execute();
            $resultTables = $stmtTables->get_result();
            $count = $resultTables->fetch_assoc()['count'];
            $stmtTables->close();

            if ($count != count($selectedTables)) {
                $errors[] = "Une ou plusieurs tables sélectionnées ne sont plus disponibles.";
            }
        }
    }

    // Check overall availability if no other errors
    if (empty($errors)) {
        $availability = checkTableAvailability($cnx, $formData['date'], $formData['heure'], $formData['personnes']);
        if (!$availability['available']) {
            $errors[] = "Désolé, nous n'avons pas assez de tables disponibles à cette heure. Nous avons {$availability['tablesAvailable']} tables disponibles, mais vous avez besoin de {$availability['tablesNeeded']} tables.";
        }
    }

    // If no errors, proceed with reservation
    if (empty($errors)) {
        $reservationTime = $formData['heure'] . ':00'; // Adjusted for TIME type
        $reservationNumber = generateReservationNumber();
        $status = "confirmée";
        $clientId = $_SESSION['client_id'] ?? null; // Assuming login sets this

        $stmt = $cnx->prepare("INSERT INTO reservations (`numero_reservation`, `nom`, `email`, `telephone`, `heure`, `personnes`, `statut`, `date_creation`, `client_id`) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        if (!$stmt) {
            error_log("Prepare failed for reservation insert: " . $cnx->error);
            $errors[] = "Erreur interne lors de la préparation de la réservation.";
        } else {
            $stmt->bind_param("sssssisi", $reservationNumber, $formData['nom'], $formData['email'], $formData['telephone'], $reservationTime, $formData['personnes'], $status, $clientId);

            if ($stmt->execute()) {
                // Update table statuses and link to reservation
                if (!empty($selectedTables)) {
                    $updatePlaceholders = implode(',', array_fill(0, count($selectedTables), '?'));
                    $updateTypes = str_repeat('i', count($selectedTables));
                    $updateStmt = $cnx->prepare("UPDATE tables SET statut = 'réservée' WHERE id IN ($updatePlaceholders)");
                    if ($updateStmt) {
                        $updateStmt->bind_param($updateTypes, ...$selectedTables);
                        $updateStmt->execute();
                        $updateStmt->close();
                    } else {
                        error_log("Prepare failed for table update: " . $cnx->error);
                    }

                    // Insert into reservation_tables
                    $reservationId = $cnx->insert_id;
                    $insertStmt = $cnx->prepare("INSERT INTO reservation_tables (reservation_id, table_id) VALUES (?, ?)");
                    if ($insertStmt) {
                        foreach ($selectedTables as $tableId) {
                            $insertStmt->bind_param("ii", $reservationId, $tableId);
                            $insertStmt->execute();
                        }
                        $insertStmt->close();
                    } else {
                        error_log("Prepare failed for reservation_tables insert: " . $cnx->error);
                    }
                }

                $_SESSION['reservation_success'] = true;
                $_SESSION['reservation_data'] = [
                    'numero' => $reservationNumber,
                    'nom' => $formData['nom'],
                    'date' => $formData['date'],
                    'heure' => $formData['heure'],
                    'personnes' => $formData['personnes'],
                ];

                // Prepare and send confirmation email
                $emailSubject = "Confirmation de réservation - Le Gourmet";
                $emailMessage = "
                <html>
                <head>
                    <title>Confirmation de réservation</title>
                </head>
                <body>
                    <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
                        <h2 style='color: #1a2a5a;'>Votre réservation est confirmée!</h2>
                        <p>Cher(e) <strong>{$formData['nom']}</strong>,</p>
                        <p>Merci d'avoir choisi Le Gourmet. Votre réservation a été enregistrée avec succès.</p>
                        <div style='background-color: #f8f8f8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <p><strong>Numéro de réservation:</strong> $reservationNumber</p>
                            <p><strong>Date:</strong> {$formData['date']}</p>
                            <p><strong>Heure:</strong> {$formData['heure']}</p>
                            <p><strong>Nombre de personnes:</strong> {$formData['personnes']}</p>
                        </div>
                        <p>Veuillez présenter votre numéro de réservation à l'arrivée: <strong>$reservationNumber</strong></p>
                        <p>Nous sommes impatients de vous accueillir!</p>
                        <p>Pour toute modification ou annulation, veuillez nous contacter au +212670251030 ou répondre à cet email.</p>
                        <hr>
                        <p style='font-size: 0.8em; color: #777;'>Le Gourmet - 123 Rue de la Gastronomie, 75001 Paris</p>
                    </div>
                </body>
                </html>
                ";

                $_SESSION['email_sent'] = sendReservationEmail($formData['email'], $emailSubject, $emailMessage);

                header("Location: confirmation_reservation.php");
                exit();
            } else {
                $errors[] = "Erreur lors de l'enregistrement de la réservation: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Load available tables for display with debugging
$availableTables = [];
$tablesResult = $cnx->query("SELECT id, numero_table FROM tables WHERE statut = 'libre'");
if (!$tablesResult) {
    error_log("Query failed: " . $cnx->error);
} else {
    if ($tablesResult->num_rows == 0) {
        error_log("No tables found with statut = 'libre' on " . date('Y-m-d H:i:s'));
    }
    while ($row = $tablesResult->fetch_assoc()) {
        $availableTables[] = $row;
    }
}

// Prepare error message for display
$errorMessageHtml = '';
if (!empty($errors)) {
    $errorMessageHtml = "<div class='error'><ul>";
    foreach ($errors as $error) {
        $errorMessageHtml .= "<li>" . htmlspecialchars($error) . "</li>";
    }
    $errorMessageHtml .= "</ul></div>";
}

// Handle reservation cancellation (from profile.php or similar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    if (isset($_SESSION['client_id'])) {
        $userId = $_SESSION['client_id'];
        $reservationNumberToCancel = $_POST['reservation_number_to_cancel'] ?? '';

        // Fetch the reservation to get associated table IDs
        $getReservationStmt = $cnx->prepare("SELECT GROUP_CONCAT(table_id) as table_ids FROM reservation_tables WHERE reservation_id = (SELECT id FROM reservations WHERE numero_reservation = ? AND client_id = ? AND statut != 'annulée')");
        if ($getReservationStmt) {
            $getReservationStmt->bind_param("si", $reservationNumberToCancel, $userId);
            $getReservationStmt->execute();
            $result = $getReservationStmt->get_result();
            $reservation = $result->fetch_assoc();
            $getReservationStmt->close();

            if ($reservation && !empty($reservation['table_ids'])) {
                $tableIds = explode(',', $reservation['table_ids']);
                $placeholders = implode(',', array_fill(0, count($tableIds), '?'));
                $types = str_repeat('i', count($tableIds));

                // Update table statuses to 'libre'
                $updateTableStmt = $cnx->prepare("UPDATE tables SET statut = 'libre' WHERE id IN ($placeholders)");
                if ($updateTableStmt) {
                    $updateTableStmt->bind_param($types, ...$tableIds);
                    $updateTableStmt->execute();
                    $updateTableStmt->close();
                } else {
                    error_log("Prepare failed for table status update on cancellation: " . $cnx->error);
                }
            }
        }

        // Update the reservation status
        $updateStmt = $cnx->prepare("UPDATE reservations SET statut = 'annulée' WHERE numero_reservation = ? AND client_id = ? AND statut != 'annulée'");
        if ($updateStmt) {
            $updateStmt->bind_param("si", $reservationNumberToCancel, $userId);
            $updateStmt->execute();
            if ($updateStmt->affected_rows > 0) {
                $_SESSION['cancellation_success'] = "Votre réservation a été annulée avec succès.";
            } else {
                $_SESSION['cancellation_error'] = "Aucune réservation active trouvée pour annulation ou déjà annulée.";
            }
            $updateStmt->close();
        } else {
            error_log("Prepare failed for cancellation: " . $cnx->error);
            $_SESSION['cancellation_error'] = "Erreur interne lors de l'annulation.";
        }
    } else {
        $_SESSION['cancellation_error'] = "Vous devez être connecté pour annuler une réservation.";
    }
    header("Location: profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - Le Gourmet</title>
    
    <!-- External CSS and JS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <!-- Embedded CSS -->
    <style>
        @font-face {
            font-family: "BodoniModa-Italic";
            src: url("fonts/BodoniModa-Italic-VariableFont_opsz,wght.ttf") format("truetype");
            font-display: swap;
        }

        /* Reset and base styles */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        body {
            min-height: 100vh;
            font-family: 'BodoniModa-Italic', sans-serif !important;
            background: linear-gradient(120deg, #060834, #1a2a5a, #060834);
            background-size: 200% 200%;
            animation: gradientBG 10s ease infinite;
            position: relative;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Background animations */
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.3;
            z-index: 0;
        }

        /* Floating particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(128, 0, 32, 0.3);
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(128, 0, 32, 0.2);
            animation: float 9s infinite ease-in-out;
        }

        .particle:nth-child(1) { width: 12px; height: 12px; top: 10%; left: 25%; animation-duration: 8s; }
        .particle:nth-child(2) { width: 18px; height: 18px; top: 65%; left: 75%; animation-duration: 10s; }
        .particle:nth-child(3) { width: 15px; height: 15px; top: 35%; left: 45%; animation-duration: 7s; }
        .particle:nth-child(4) { width: 10px; height: 10px; top: 80%; left: 15%; animation-duration: 11s; }
        .particle:nth-child(5) { width: 14px; height: 14px; top: 20%; left: 60%; animation-duration: 9s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.4; }
            50% { transform: translateY(-30px) translateX(10px); opacity: 0.7; }
        }

        /* Main container */
        .reservation-container {
            position: relative;
            max-width: 550px;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 245, 224, 0.3);
            border-radius: 20px;
            backdrop-filter: blur(15px);
            padding: 30px;
            box-shadow: 0 0 20px rgba(6, 8, 52, 0.3);
            z-index: 1;
            margin: 40px auto;
            transition: all 0.3s ease;
        }

        .reservation-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(6, 8, 52, 0.4);
        }

        /* Typography */
        h2 {
            font-size: 2.2rem;
            color: #fff5e0;
            text-align: center;
            margin-bottom: 25px;
            text-shadow: 0 2px 5px rgba(6, 8, 52, 0.4);
            position: relative;
            padding-bottom: 15px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, transparent, #fff5e0, transparent);
        }

        /* Message styles */
        .success {
            color: #e6f7d9;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            background: rgba(40, 167, 69, 0.2);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            animation: fadeIn 0.5s ease-in-out;
        }

        .error {
            color: #f8d7da;
            text-align: left;
            font-weight: bold;
            margin-bottom: 20px;
            background: rgba(220, 53, 69, 0.2);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            animation: fadeIn 0.5s ease-in-out;
        }

        .error ul {
            margin: 10px 0 0 20px;
        }

        .error li {
            margin-bottom: 5px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Hours info */
        .hours-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 12px;
            margin: 15px 0;
            color: #fff5e0;
            text-align: center;
        }

        .hours-info p {
            margin: 5px 0;
        }

        .hours-info strong {
            color: #fff;
        }

        /* Form styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 0;
        }

        .form-col {
            flex: 1;
            min-width: 0;
        }

        .inputbox {
            position: relative;
            margin: 0;
            border-bottom: 2px solid #fff5e0;
            transition: border-color 0.3s ease;
        }

        .inputbox.error {
            border-bottom-color: #dc3545;
        }

        input {
            width: 100%;
            height: 45px;
            background: transparent;
            border: none;
            outline: none;
            font-size: 1rem;
            padding: 0 35px 0 5px;
            color: #fff;
            font-family: 'BodoniModa-Italic', sans-serif;
            transition: all 0.3s ease;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        input:focus {
            border-bottom-color: #fff;
        }

        input:invalid {
            border-bottom-color: #dc3545;
        }

        .inputbox ion-icon {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #fff5e0;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .inputbox:hover ion-icon {
            color: #fff;
        }

        label {
            position: absolute;
            top: 50%;
            left: 5px;
            transform: translateY(-50%);
            color: #fff;
            font-size: 1rem;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        input:focus ~ label,
        input:not(:placeholder-shown) ~ label {
            top: -5px;
            font-size: 0.8rem;
            color: #fff5e0;
        }

        .info-text {
            color: #fff5e0;
            font-size: 0.85rem;
            margin-top: 5px;
            margin-bottom: 0;
            opacity: 0.7;
        }

        /* Table selection styles */
        .table-selection {
            margin: 15px 0;
        }

        .table-selection-label {
            display: block;
            color: #fff5e0;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 0;
        }

        .table-option {
            position: relative;
        }

        .table-checkbox {
            display: none;
        }

        .table-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 245, 224, 0.3);
            border-radius: 8px;
            color: #fff5e0;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 600;
            min-height: 50px;
            height: auto;
        }

        .table-label:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 245, 224, 0.5);
            transform: translateY(-2px);
        }

        .table-checkbox:checked + .table-label {
            background: linear-gradient(45deg, #060834, #1a2a5a);
            border-color: #fff5e0;
            color: #fff;
            box-shadow: 0 4px 12px rgba(6, 8, 52, 0.3);
        }

        .no-tables-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin: 15px 0;
            text-align: center;
            color: #f8d7da;
        }

        /* Button styles */
        button {
            width: 100%;
            height: 45px;
            border-radius: 40px;
            background: linear-gradient(45deg, #060834, #1a2a5a);
            border: 1px solid #fff5e0;
            outline: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff5e0;
            transition: all 0.4s ease;
            box-shadow: 0 4px 12px rgba(6, 8, 52, 0.3);
            text-transform: uppercase;
            margin-top: 15px;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.4s ease;
        }

        button:hover {
            background: linear-gradient(45deg, #1a2a5a, #060834);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(6, 8, 52, 0.5);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Footer styles */
        footer {
            width: 100%;
            text-align: center;
            padding: 20px;
            color: #fff5e0;
            position: relative;
            z-index: 1;
            margin-top: auto;
            font-size: 0.9rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .reservation-container {
                margin: 20px 15px;
                padding: 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            
            h2 {
                font-size: 1.8rem;
            }
            
            .table-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
                gap: 8px;
            }
            
            .table-label {
                padding: 8px;
                font-size: 0.8rem;
                min-height: 45px;
                height: auto;
            }
        }

        @media (max-width: 480px) {
            .reservation-container {
                padding: 15px;
            }
            
            h2 {
                font-size: 1.6rem;
            }
            
            input {
                font-size: 0.9rem;
                height: 40px;
            }
            
            button {
                font-size: 1rem;
                height: 40px;
            }
            
            .table-label {
                min-height: 40px;
                height: auto;
            }
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <!-- Animated background particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="reservation-container animate__animated animate__fadeIn">
        <h2>Réserver une Table</h2>
        
        <!-- Display error messages -->
        <?php if (!empty($errorMessageHtml)): ?>
            <?php echo $errorMessageHtml; ?>
        <?php endif; ?>

        <!-- Restaurant hours information -->
        <div class="hours-info">
            <p><strong>Heures d'ouverture:</strong> Tous les jours de 12h à 23h</p>
            <p><strong>Dernière réservation:</strong> 22h</p>
        </div>

        <!-- Reservation form -->
        <form action="" method="POST" id="reservationForm" novalidate>
            <!-- Name and Phone row -->
            <div class="form-row">
                <div class="form-col">
                    <div class="inputbox">
                        <ion-icon name="person-outline"></ion-icon>
                        <input type="text" 
                               name="nom" 
                               value="<?php echo htmlspecialchars($formData['nom']); ?>" 
                               placeholder=" " 
                               required 
                               maxlength="100">
                        <label>Nom complet</label>
                    </div>
                </div>
                <div class="form-col">
                    <div class="inputbox">
                        <ion-icon name="call-outline"></ion-icon>
                        <input type="tel" 
                               name="telephone" 
                               value="<?php echo htmlspecialchars($formData['telephone']); ?>" 
                               placeholder=" " 
                               required 
                               pattern="[0-9+\s()-]{8,15}">
                        <label>Téléphone</label>
                    </div>
                </div>
            </div>
            
            <!-- Email -->
            <div class="inputbox">
                <ion-icon name="mail-outline"></ion-icon>
                <input type="email" 
                       name="email" 
                       value="<?php echo htmlspecialchars($formData['email']); ?>" 
                       placeholder=" " 
                       required 
                       maxlength="255">
                <label>Email</label>
            </div>
            
            <!-- Date and Time row -->
            <div class="form-row">
                <div class="form-col">
                    <div class="inputbox">
                        <ion-icon name="calendar-outline"></ion-icon>
                        <input type="date" 
                               name="date" 
                               value="<?php echo htmlspecialchars($formData['date']); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>"
                               placeholder=" " 
                               required>
                        <label>Date</label>
                    </div>
                </div>
                <div class="form-col">
                    <div class="inputbox">
                        <ion-icon name="time-outline"></ion-icon>
                        <input type="time" 
                               name="heure" 
                               value="<?php echo htmlspecialchars($formData['heure']); ?>" 
                               min="12:00" 
                               max="22:00" 
                               placeholder=" " 
                               required>
                        <label>Heure</label>
                    </div>
                    <p class="info-text">Réservations entre 12h et 22h</p>
                </div>
            </div>
            
            <!-- Number of people -->
            <div class="inputbox">
                <ion-icon name="people-outline"></ion-icon>
                <input type="number" 
                       name="personnes" 
                       min="1" 
                       max="20" 
                       value="<?php echo $formData['personnes'] ? htmlspecialchars($formData['personnes']) : ''; ?>" 
                       placeholder=" " 
                       required>
                <label>Nombre de personnes</label>
            </div>
            <p class="info-text">Maximum 20 personnes par réservation. Pour les groupes plus importants, veuillez nous contacter.</p>
            
            <!-- Table selection -->
            <?php if (!empty($availableTables)): ?>
                <div class="table-selection">
                    <label class="table-selection-label">Choisissez jusqu'à 3 tables disponibles :</label>
                    <div class="table-grid">
                        <?php foreach ($availableTables as $table): ?>
                            <div class="table-option">
                                <input type="checkbox" 
                                       name="tables[]" 
                                       value="<?php echo $table['id']; ?>" 
                                       id="table<?php echo $table['numero_table']; ?>"
                                       class="table-checkbox">
                                <label for="table<?php echo $table['numero_table']; ?>" class="table-label">
                                    Table <?php echo $table['numero_table']; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-tables-message">
                    <p>Aucune table disponible pour le moment. Veuillez réessayer plus tard ou contactez-nous.</p>
                </div>
            <?php endif; ?>
            
            <!-- Submit button -->
            <button type="submit">Réserver ma table</button>
        </form>
    </div>

    <?php include("footer.php"); ?>

    <!-- Embedded JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reservationForm');
            const tableCheckboxes = document.querySelectorAll('.table-checkbox');
            
            // Form validation on submit
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Get form fields
                const nom = form.querySelector('input[name="nom"]');
                const email = form.querySelector('input[name="email"]');
                const telephone = form.querySelector('input[name="telephone"]');
                const date = form.querySelector('input[name="date"]');
                const heure = form.querySelector('input[name="heure"]');
                const personnes = form.querySelector('input[name="personnes"]');
                
                // Clear previous error states
                clearErrorStates();
                
                // Validate required fields
                const requiredFields = [nom, email, telephone, date, heure, personnes];
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        setFieldError(field, 'Ce champ est requis');
                        isValid = false;
                    }
                });
                
                // Validate email format
                if (email.value && !isValidEmail(email.value)) {
                    setFieldError(email, 'Format d\'email invalide');
                    isValid = false;
                }
                
                // Validate phone number
                if (telephone.value && !isValidPhone(telephone.value)) {
                    setFieldError(telephone, 'Numéro de téléphone invalide');
                    isValid = false;
                }
                
                // Validate date (not in the past)
                if (date.value && !isValidDate(date.value)) {
                    setFieldError(date, 'La date ne peut pas être dans le passé');
                    isValid = false;
                }
                
                // Validate time (between 12:00 and 22:00)
                if (heure.value && !isValidTime(heure.value)) {
                    setFieldError(heure, 'L\'heure doit être entre 12h et 22h');
                    isValid = false;
                }
                
                // Validate number of people
                if (personnes.value && !isValidPersonCount(personnes.value)) {
                    setFieldError(personnes, 'Le nombre de personnes doit être entre 1 et 20');
                    isValid = false;
                }
                
                // Validate table selection
                if (!isValidTableSelection()) {
                    showTableSelectionError();
                    isValid = false;
                }
                
                // Prevent form submission if validation fails
                if (!isValid) {
                    e.preventDefault();
                    showValidationSummary();
                }
            });
            
            // Real-time validation for individual fields
            form.querySelectorAll('input').forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    clearFieldError(this);
                });
            });
            
            // Table selection validation
            tableCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    validateTableSelection();
                });
            });
            
            // Validation helper functions
            function validateField(field) {
                const fieldName = field.getAttribute('name');
                const value = field.value.trim();
                
                switch(fieldName) {
                    case 'nom':
                        if (!value) {
                            setFieldError(field, 'Le nom est requis');
                        } else if (value.length < 2) {
                            setFieldError(field, 'Le nom doit contenir au moins 2 caractères');
                        }
                        break;
                        
                    case 'email':
                        if (!value) {
                            setFieldError(field, 'L\'email est requis');
                        } else if (!isValidEmail(value)) {
                            setFieldError(field, 'Format d\'email invalide');
                        }
                        break;
                        
                    case 'telephone':
                        if (!value) {
                            setFieldError(field, 'Le téléphone est requis');
                        } else if (!isValidPhone(value)) {
                            setFieldError(field, 'Format de téléphone invalide');
                        }
                        break;
                        
                    case 'date':
                        if (!value) {
                            setFieldError(field, 'La date est requise');
                        } else if (!isValidDate(value)) {
                            setFieldError(field, 'La date ne peut pas être dans le passé');
                        }
                        break;
                        
                    case 'heure':
                        if (!value) {
                            setFieldError(field, 'L\'heure est requise');
                        } else if (!isValidTime(value)) {
                            setFieldError(field, 'L\'heure doit être entre 12h et 22h');
                        }
                        break;
                        
                    case 'personnes':
                        if (!value) {
                            setFieldError(field, 'Le nombre de personnes est requis');
                        } else if (!isValidPersonCount(value)) {
                            setFieldError(field, 'Entre 1 et 20 personnes');
                        }
                        break;
                }
            }
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            function isValidPhone(phone) {
                const phoneRegex = /^[0-9+\s()-]{8,15}$/;
                return phoneRegex.test(phone);
            }
            
            function isValidDate(dateString) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const selectedDate = new Date(dateString);
                selectedDate.setHours(0, 0, 0, 0);
                return selectedDate >= today;
            }
            
            function isValidTime(timeString) {
                const [hours, minutes] = timeString.split(':').map(Number);
                return hours >= 12 && hours <= 22;
            }
            
            function isValidPersonCount(count) {
                const num = parseInt(count);
                return !isNaN(num) && num >= 1 && num <= 20;
            }
            
            function isValidTableSelection() {
                const checkedTables = document.querySelectorAll('.table-checkbox:checked');
                return checkedTables.length >= 1 && checkedTables.length <= 3;
            }
            
            function validateTableSelection() {
                const checkedTables = document.querySelectorAll('.table-checkbox:checked');
                const tableSelection = document.querySelector('.table-selection');
                
                if (tableSelection) {
                    const errorElement = tableSelection.querySelector('.table-error');
                    if (errorElement) {
                        errorElement.remove();
                    }
                    
                    if (checkedTables.length === 0) {
                        showTableError('Vous devez sélectionner au moins une table');
                    } else if (checkedTables.length > 3) {
                        showTableError('Vous ne pouvez pas sélectionner plus de 3 tables');
                    }
                }
            }
            
            function setFieldError(field, message) {
                const inputbox = field.closest('.inputbox');
                if (inputbox) {
                    inputbox.classList.add('error');
                    field.style.borderBottomColor = '#dc3545';
                    
                    const existingError = inputbox.querySelector('.field-error');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    const errorElement = document.createElement('div');
                    errorElement.className = 'field-error';
                    errorElement.style.cssText = 'color: #dc3545; font-size: 0.8rem; margin-top: 5px;';
                    errorElement.textContent = message;
                    inputbox.appendChild(errorElement);
                }
            }
            
            function clearFieldError(field) {
                const inputbox = field.closest('.inputbox');
                if (inputbox) {
                    inputbox.classList.remove('error');
                    field.style.borderBottomColor = '#fff5e0';
                    
                    const errorElement = inputbox.querySelector('.field-error');
                    if (errorElement) {
                        errorElement.remove();
                    }
                }
            }
            
            function clearErrorStates() {
                document.querySelectorAll('.inputbox').forEach(inputbox => {
                    inputbox.classList.remove('error');
                    const errorElement = inputbox.querySelector('.field-error');
                    if (errorElement) {
                        errorElement.remove();
                    }
                });
                
                document.querySelectorAll('input').forEach(input => {
                    input.style.borderBottomColor = '#fff5e0';
                });
                
                const tableErrors = document.querySelectorAll('.table-error');
                tableErrors.forEach(error => error.remove());
            }
            
            function showTableError(message) {
                const tableSelection = document.querySelector('.table-selection');
                if (tableSelection) {
                    const errorElement = document.createElement('div');
                    errorElement.className = 'table-error';
                    errorElement.style.cssText = 'color: #dc3545; font-size: 0.85rem; margin-top: 10px; padding: 8px; background: rgba(220, 53, 69, 0.1); border-radius: 4px;';
                    errorElement.textContent = message;
                    tableSelection.appendChild(errorElement);
                }
            }
            
            function showTableSelectionError() {
                const checkedTables = document.querySelectorAll('.table-checkbox:checked');
                if (checkedTables.length === 0) {
                    showTableError('Vous devez sélectionner au moins une table');
                } else if (checkedTables.length > 3) {
                    showTableError('Vous ne pouvez pas sélectionner plus de 3 tables');
                }
            }
            
            function showValidationSummary() {
                const firstError = document.querySelector('.inputbox.error, .table-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                const existingSummary = document.querySelector('.validation-summary');
                if (existingSummary) {
                    existingSummary.remove();
                }
                
                const summaryElement = document.createElement('div');
                summaryElement.className = 'validation-summary error';
                summaryElement.innerHTML = '<p>Veuillez corriger les erreurs dans le formulaire avant de continuer.</p>';
                
                const form = document.getElementById('reservationForm');
                form.insertBefore(summaryElement, form.firstChild);
                
                setTimeout(() => {
                    if (summaryElement.parentNode) {
                        summaryElement.remove();
                    }
                }, 5000);
            }
            
            document.addEventListener('input', function() {
                const summary = document.querySelector('.validation-summary');
                if (summary) {
                    summary.remove();
                }
            });
            
            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') {
                    e.preventDefault();
                    const inputs = Array.from(form.querySelectorAll('input'));
                    const currentIndex = inputs.indexOf(e.target);
                    if (currentIndex < inputs.length - 1) {
                        inputs[currentIndex + 1].focus();
                    }
                }
            });
        });
    </script>
</body>
</html>