<?php
session_start();
require 'cnx.php';

if (!isset($_SESSION['nom'])) {
    header("Location: signin.php");
    exit;
}

$nom = $_SESSION['nom'];

$stmt = mysqli_prepare($cnx, "SELECT * FROM reservations WHERE nom = ?");
mysqli_stmt_bind_param($stmt, "s", $nom);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Réservation</title>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        @font-face {
            font-family: "BodoniModa-Italic";
            src: url("fonts/BodoniModa-Italic-VariableFont_opsz,wght.ttf") format("truetype");
            font-display: swap;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'BodoniModa-Italic', sans-serif !important;
            background: linear-gradient(120deg, #0a1140, #2a428a, #0a1140);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
            position: relative;
            overflow-x: hidden;
            padding: 80px 0;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.07) 1px, transparent 1px);
            background-size: 25px 25px;
            opacity: 0.4;
            z-index: 0;
        }
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        .particle {
            position: absolute;
            background: rgba(173, 216, 230, 0.4); /* Light blue for particles */
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(173, 216, 230, 0.3);
            animation: float 12s infinite ease-in-out;
        }
        .particle:nth-child(1) { width: 15px; height: 15px; top: 15%; left: 20%; animation-duration: 10s; }
        .particle:nth-child(2) { width: 20px; height: 20px; top: 70%; left: 80%; animation-duration: 13s; }
        .particle:nth-child(3) { width: 18px; height: 18px; top: 40%; left: 50%; animation-duration: 9s; }
        .particle:nth-child(4) { width: 12px; height: 12px; top: 85%; left: 10%; animation-duration: 14s; }
        .particle:nth-child(5) { width: 16px; height: 16px; top: 25%; left: 65%; animation-duration: 11s; }
        .particle:nth-child(6) { width: 10px; height: 10px; top: 5%; left: 90%; animation-duration: 10s; }
        .particle:nth-child(7) { width: 22px; height: 22px; top: 95%; left: 30%; animation-duration: 12s; }
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.5; }
            50% { transform: translateY(-40px) translateX(15px); opacity: 0.8; }
        }
        .main-container {
            position: relative;
            max-width: 500px; /* Slightly wider */
            width: 100%;
            background-color: rgba(255, 255, 255, 0.15); /* More transparent */
            border: 2px solid rgba(255, 245, 224, 0.4); /* Slightly stronger border */
            border-radius: 25px; /* More rounded corners */
            backdrop-filter: blur(20px); /* Stronger blur */
            padding: 50px; /* More padding */
            box-shadow: 0 0 40px rgba(10, 17, 64, 0.4); /* Stronger shadow */
            z-index: 1;
            margin: 20px;
            transition: all 0.3s ease-in-out;
            animation: borderPulse 4s infinite alternate;
        }
        @keyframes borderPulse {
            0% { border-color: rgba(255, 245, 224, 0.4); }
            100% { border-color: rgba(255, 245, 224, 0.7); }
        }
        .main-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 50px rgba(10, 17, 64, 0.6);
        }
        h2 {
            font-size: 2.5rem; /* Larger heading */
            color: #e0f2f7; /* Lighter color */
            text-align: center;
            margin-bottom: 40px;
            text-shadow: 0 3px 8px rgba(10, 17, 64, 0.5);
        }
        .info-item {
            position: relative;
            margin: 30px 0;
            border-bottom: 1px solid rgba(255, 245, 224, 0.3); /* Lighter border */
            padding-bottom: 15px;
        }
        .info-label {
            color: #e0f2f7;
            font-size: 1rem;
            font-weight: 700; /* Bolder */
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .info-value {
            color: #f0f8ff; /* Even lighter color */
            font-size: 1.2rem;
            padding: 5px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-top: 8px;
            transition: all 0.3s ease;
        }
        .status-confirmed {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        .status-pending {
            background: linear-gradient(45deg, #f1c40f, #e67e22);
            color: #34495e;
            box-shadow: 0 5px 15px rgba(241, 196, 15, 0.4);
        }
        .status-cancelled {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        .status-badge:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .no-reservation {
            text-align: center;
            color: #e0f2f7;
            font-size: 1.4rem;
            padding: 30px;
        }
        .icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #e0f2f7;
            font-size: 1.5rem; /* Larger icons */
        }
        button {
            width: 100%;
            height: 55px; /* Taller buttons */
            border-radius: 45px;
            background: linear-gradient(45deg, #0a1140, #2a428a);
            border: 1px solid #e0f2f7;
            outline: none;
            cursor: pointer;
            font-size: 1.1rem; /* Larger font */
            font-weight: 700; /* Bolder font */
            color: #e0f2f7;
            transition: all 0.4s ease;
            box-shadow: 0 5px 15px rgba(10, 17, 64, 0.4);
            text-transform: uppercase;
            margin-top: 35px;
            letter-spacing: 1px;
        }
        button:hover {
            background: linear-gradient(45deg, #2a428a, #0a1140);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(10, 17, 64, 0.6);
        }
        /* Responsive adjustments */
        @media (max-width: 550px) {
            .main-container {
                padding: 30px 20px;
            }
            
            h2 {
                font-size: 2rem;
            }
            .info-label {
                font-size: 0.9rem;
            }
            .info-value {
                font-size: 1.1rem;
            }
            .status-badge {
                font-size: 0.9rem;
                padding: 8px 15px;
            }
            button {
                height: 50px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Particles Background -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="main-container">
        <?php if ($user): ?>
            <h2>Bienvenue <?php echo htmlspecialchars($user['nom']); ?></h2>
            
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                <ion-icon name="mail-outline" class="icon"></ion-icon>
            </div>

            <div class="info-item">
                <div class="info-label">Téléphone</div>
                <div class="info-value"><?php echo htmlspecialchars($user['telephone']); ?></div>
                <ion-icon name="call-outline" class="icon"></ion-icon>
            </div>

            <div class="info-item">
                <div class="info-label">Heure de réservation</div>
                <div class="info-value"><?php echo htmlspecialchars($user['heure']); ?></div>
                <ion-icon name="time-outline" class="icon"></ion-icon>
            </div>

            <div class="info-item">
                <div class="info-label">Nombre de personnes</div>
                <div class="info-value"><?php echo htmlspecialchars($user['personnes']); ?></div>
                <ion-icon name="people-outline" class="icon"></ion-icon>
            </div>

            <div class="info-item">
                <div class="info-label">Statut de réservation</div>
                <div class="info-value">
                    <?php 
                    $status = strtolower($user['statut']);
                    $statusClass = '';
                    switch($status) {
                        case 'confirmé':
                        case 'confirmed':
                            $statusClass = 'status-confirmed';
                            break;
                        case 'en attente':
                        case 'pending':
                            $statusClass = 'status-pending';
                            break;
                        case 'annulé':
                        case 'cancelled':
                            $statusClass = 'status-cancelled';
                            break;
                        default:
                            $statusClass = 'status-pending';
                    }
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($user['statut']); ?>
                    </span>
                </div>
                <ion-icon name="checkmark-circle-outline" class="icon"></ion-icon>
            </div>

            <button onclick="window.location.href='dashboard.php'">
                Retour au tableau de bord
            </button>

        <?php else: ?>
            <div class="no-reservation">
                <ion-icon name="alert-circle-outline" style="font-size: 3rem; margin-bottom: 20px; display: block;"></ion-icon>
                Aucune réservation trouvée.
            </div>
            <button onclick="window.location.href='reservation.php'">
                Faire une réservation
            </button>
        <?php endif; ?>
    </div>
</body>
</html>

