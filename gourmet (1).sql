-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2025 at 12:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gourmet`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(66) NOT NULL,
  `nom_complet` varchar(44) NOT NULL,
  `Email` varchar(99) NOT NULL,
  `telephone` varchar(85) NOT NULL,
  `mdp` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `active` int(11) NOT NULL,
  `date_inscription` datetime DEFAULT current_timestamp(),
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `nom_complet`, `Email`, `telephone`, `mdp`, `type`, `active`, `date_inscription`, `date_creation`) VALUES
(2, 'youssef', 'youssef@gmail.com', '06225878', '4df48752d2757d1c2963b6e7b85b23b714d6d9d7', 'client', 1, '2025-04-13 12:27:29', '2025-04-14 22:26:19'),
(3, 'aymano', 'thraymen031@gmail.com', '0670251030', '9e390e67bb8a042b486acb022696118527028ee1', 'client', 0, '2025-04-13 12:27:29', '2025-04-14 22:26:19'),
(4, 'aymenno', 'thraymenn0312@gmail.com', '0670251030', '9e390e67bb8a042b486acb022696118527028ee1', 'client', 0, '2025-04-13 12:27:29', '2025-04-14 22:26:19'),
(5, 'admin', 'admin@gmail.com', '0670251030', 'f865b53623b121fd34ee5426c792e5c33af8c227', 'admin', 1, '2025-04-13 12:27:29', '2025-04-14 22:26:19'),
(6, 'AYMENN', 'ruvotodug@mailinator.com', '0670251030', '$2y$10$L8S3pLztyVm2F8/DJ3U31eyyhrpMB8mlCTOBgaInzltClC21eng6m', 'client', 1, '2025-07-14 19:02:25', '2025-07-14 19:02:25'),
(7, 'aymen', 'thraymen0314@gmail.com', '0670251030', '$2y$10$Dy2oT.54.zsxAQOVUgr6NePSIpBXVL3S9SwVrdp57a6AnH8JrSZmm', 'admin', 1, '2025-07-14 19:05:56', '2025-07-14 19:05:56'),
(8, 'aymen', 'aymen0311@gmail.com', '0670251030', '$2y$10$XPa.lPEbjrqiVBh0XFYS/.Bn5xgYoSZePPvKTPBjts97jMpXGnJBK', 'admin', 1, '2025-07-14 19:21:47', '2025-07-14 19:21:47'),
(9, 'aymen', 'admin123@gmail.com', '0670251030', '$2y$10$.FCkoA1Ic6W87/8atZCY5eTwY9n68gsH5OvpAmD/3WjttPIqoCx0G', 'admin', 1, '2025-07-14 20:41:50', '2025-07-14 20:41:50'),
(10, 'aymen', 'aymen031133@gmail.com', '0670251030', '$2y$10$v.zq07y310XRB82Itchy0e9QDuBSIg47HzywOjKId2SDjC41qD/b2', 'client', 1, '2025-07-18 19:03:10', '2025-07-18 19:03:10');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `submitted_at`) VALUES
(5, 'dfbg', 'aymen123@gmail.com', 'aggqdfgqdfg', '2025-07-14 18:30:55');

-- --------------------------------------------------------

--
-- Table structure for table `plats`
--

CREATE TABLE `plats` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `prix` decimal(10,0) NOT NULL,
  `image` varchar(255) NOT NULL,
  `categorie` varchar(50) NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plats`
--

INSERT INTO `plats` (`id`, `nom`, `description`, `prix`, `image`, `categorie`, `disponible`, `date_ajout`) VALUES
(15, 'Tarte aux Pommes de Terre Gratin', 'Pommes de terre, lardons fumés, oignons caramélisés, crème fraîche, fromage, thym frais, le tout sur une pâte croustillante. Servie dans sa terrine en terre cuite', 889, 'uploads/plats/plat.1_1745944033.jpg', '0', 1, '2025-04-29 17:27:13'),
(32, 'fgvc', 'fuufghfg', 22, 'Uploads/plats/github_1752570659.png', '0', 1, '2025-07-15 10:10:59');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `heure` time NOT NULL,
  `personnes` int(11) NOT NULL,
  `numero_reservation` varchar(20) NOT NULL,
  `statut` varchar(20) NOT NULL,
  `date_creation` datetime DEFAULT NULL,
  `table_ids` varchar(255) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `nom`, `email`, `telephone`, `heure`, `personnes`, `numero_reservation`, `statut`, `date_creation`, `table_ids`, `client_id`) VALUES
(18, 'aymen', 'thraymen031@gmail.com', '0670251030', '13:09:00', 3, 'RES-73EFCCF4', 'annulée', '2025-04-19 21:04:50', NULL, NULL),
(19, 'aymen', 'thraymen031@gmail.com', '0670251030', '13:09:00', 3, 'RES-1CF693AC', 'annulée', '2025-04-19 21:04:52', NULL, NULL),
(20, 'aymen', 'thraymen031@gmail.com', '0670251030', '13:09:00', 3, 'RES-65E5C099', 'annulée', '2025-04-19 21:04:54', NULL, NULL),
(21, 'aymen', 'thraymen031@gmail.com', '0670251030', '13:09:00', 3, 'RES-F97349B3', 'annulée', '2025-04-19 21:24:42', NULL, NULL),
(22, 'aymen', 'thraymen031@gmail.com', '0670251030', '18:08:00', 4, 'RES-A73E53BA', 'annulée', '2025-04-20 01:04:24', NULL, NULL),
(23, 'aymen', 'thraymen031@gmail.com', '0670251030', '16:07:00', 3, 'RES-02569FA2', 'annulée', '2025-05-13 13:10:30', NULL, NULL),
(24, 'aymen', 'thraymen031@gmail.com', '0670251030', '16:20:00', 3, 'RES-D08F58D6', 'annulée', '2025-05-14 14:20:24', NULL, NULL),
(25, 'aymen', 'thraymen031@gmail.com', '0670251030', '16:20:00', 3, 'RES-9ED6528E', 'annulée', '2025-05-14 14:20:26', NULL, NULL),
(26, 'aymen', 'thraymen031@gmail.com', '0670251030', '16:41:00', 2, 'RES-2CA77DE0', 'annulée', '2025-05-17 12:36:18', NULL, NULL),
(27, 'aymen', 'thraymen031@gmail.com', '0670251030', '16:41:00', 2, 'RES-A7596C9B', 'annulée', '2025-05-17 12:44:05', NULL, NULL),
(28, 'aymen', 'thraymen031@gmail.com', '0670251030', '16:41:00', 2, 'RES-5328DB02', 'annulée', '2025-05-17 12:44:08', NULL, NULL),
(29, 'aymen', 'thraymen031@gmail.com', '0670251030', '16:41:00', 4, 'RES-49C80902', 'annulée', '2025-05-17 12:44:14', NULL, NULL),
(30, 'aymen', 'thraymen031@gmail.com', '0670251030', '17:30:00', 4, 'RES-455E0BF3', 'annulée', '2025-05-17 13:30:19', NULL, NULL),
(31, 'aymen', 'thraymen031@gmail.com', '0670251030', '15:16:00', 2, 'RES-6F2CCBC3', 'annulée', '2025-05-17 23:12:59', NULL, NULL),
(32, 'aymen', 'thraymen031@gmail.com', '0670251030', '15:16:00', 2, 'RES-BF11216C', 'annulée', '2025-05-17 23:13:00', NULL, NULL),
(33, 'Aliquam reiciendis d', 'bavepupa@mailinator.com', '0670251030', '18:19:00', 9, 'RES-4BBBC254', 'confirmée', '2025-05-18 01:59:22', NULL, NULL),
(34, 'Aliquam reiciendis d', 'bavepupa@mailinator.com', '0670251030', '18:19:00', 9, 'RES-6A4729B6', 'confirmée', '2025-05-18 01:59:24', NULL, NULL),
(35, 'Aliquam reiciendis d', 'bavepupa@mailinator.com', '0670251030', '18:19:00', 9, 'RES-F5C3AA68', 'confirmée', '2025-05-18 01:59:26', NULL, NULL),
(36, 'Consequuntur harum e', 'nebyvyt@mailinator.com', '0670251030', '17:57:00', 1, 'RES-4CEEBCAE', 'confirmée', '2025-07-13 16:56:17', NULL, NULL),
(37, 'aymen', 'pybo@mailinator.com', '0670251030', '20:58:00', 16, 'RES-EEB002D6', 'annulée', '2025-07-14 12:43:14', NULL, NULL),
(38, 'Sit culpa est ad asp', 'baxibufa@mailinator.com', '0670251030', '20:01:00', 9, 'RES-7BAD5C25', 'confirmée', '2025-07-14 19:32:27', NULL, NULL),
(39, 'Sit culpa est ad asp', 'baxibufa@mailinator.com', '0670251030', '20:01:00', 9, 'RES-88720161', 'confirmée', '2025-07-14 19:32:29', NULL, NULL),
(40, 'Est non est eum ex', 'fusu@mailinator.com', '0670251030', '17:50:00', 10, 'RES-79E05F31', 'confirmée', '2025-07-15 10:12:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_tables`
--

CREATE TABLE `reservation_tables` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `numero_table` varchar(50) NOT NULL,
  `statut` enum('libre','réservée','annulée') DEFAULT 'libre'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `numero_table`, `statut`) VALUES
(1, 'Table 1', 'libre'),
(2, 'Table 2', 'libre'),
(3, 'Table 3', 'libre'),
(4, 'Table 4', 'libre'),
(5, 'Table 5', 'libre'),
(6, 'Table 6', 'libre'),
(7, 'Table 7', 'libre'),
(8, 'Table 8', 'libre'),
(9, 'Table 9', 'libre'),
(10, 'Table 10', 'libre'),
(11, 'Table 11', 'libre'),
(12, 'Table 12', 'libre'),
(13, 'Table 13', 'libre'),
(14, 'Table 14', 'libre'),
(15, 'Table 15', 'libre');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `plats`
--
ALTER TABLE `plats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `reservation_tables`
--
ALTER TABLE `reservation_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reservation_table` (`reservation_id`,`table_id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(66) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `plats`
--
ALTER TABLE `plats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `reservation_tables`
--
ALTER TABLE `reservation_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reservation_tables`
--
ALTER TABLE `reservation_tables`
  ADD CONSTRAINT `reservation_tables_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_tables_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
