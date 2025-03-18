-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2025 at 08:37 AM
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
-- Database: `cityhalldtr`
--

-- --------------------------------------------------------

--
-- Table structure for table `dtr_records`
--

CREATE TABLE `dtr_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in_am` time DEFAULT NULL,
  `time_out_am` time DEFAULT NULL,
  `time_in_pm` time DEFAULT NULL,
  `time_out_pm` time DEFAULT NULL,
  `total_hours` decimal(5,2) GENERATED ALWAYS AS (ifnull(timestampdiff(MINUTE,`time_in_am`,`time_out_am`) / 60.0,0) + ifnull(timestampdiff(MINUTE,`time_in_pm`,`time_out_pm`) / 60.0,0)) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dtr_records`
--

INSERT INTO `dtr_records` (`id`, `user_id`, `date`, `time_in_am`, `time_out_am`, `time_in_pm`, `time_out_pm`, `created_at`, `updated_at`) VALUES
(51, 1, '2025-02-12', '08:00:00', '12:07:00', '12:07:00', '17:09:00', '2025-03-13 08:18:36', '2025-03-13 08:18:36'),
(53, 1, '2025-02-13', '07:43:00', '12:04:00', '12:04:00', '17:04:00', '2025-03-13 08:20:21', '2025-03-13 08:20:21'),
(54, 1, '2025-02-14', '08:00:00', '12:00:00', '12:00:00', '17:00:00', '2025-03-13 08:20:55', '2025-03-13 08:20:55'),
(55, 1, '2025-02-17', '07:59:00', '12:04:00', '12:04:00', '17:05:00', '2025-03-13 08:21:54', '2025-03-13 08:21:54'),
(56, 1, '2025-02-18', '07:47:00', '12:09:00', '12:09:00', '17:14:00', '2025-03-13 08:22:26', '2025-03-13 08:22:26'),
(57, 1, '2025-02-19', '07:58:00', '12:06:00', '12:06:00', '17:09:00', '2025-03-13 08:23:34', '2025-03-13 08:23:34'),
(58, 1, '2025-02-20', '07:57:00', '12:07:00', '12:07:00', '17:05:00', '2025-03-13 08:26:22', '2025-03-13 08:26:22'),
(59, 1, '2025-02-21', '08:00:00', '12:00:00', '12:00:00', '17:09:00', '2025-03-13 08:26:59', '2025-03-13 08:27:28'),
(60, 1, '2025-02-22', '08:00:00', '12:00:00', '12:00:00', '17:00:00', '2025-03-13 08:27:44', '2025-03-13 08:27:44'),
(61, 1, '2025-02-25', '08:00:00', '12:06:00', '12:06:00', '17:33:00', '2025-03-13 08:28:49', '2025-03-13 08:28:49'),
(63, 1, '2025-02-26', '08:00:00', '12:10:00', '12:10:00', '17:30:00', '2025-03-13 08:31:03', '2025-03-13 08:31:03'),
(64, 1, '2025-02-27', '08:00:00', '12:24:00', '12:24:00', '17:30:00', '2025-03-13 08:31:29', '2025-03-13 08:31:29'),
(65, 1, '2025-02-28', '07:55:00', '12:13:00', '12:13:00', '17:30:00', '2025-03-13 08:32:08', '2025-03-13 08:32:08'),
(66, 1, '2025-03-01', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-13 08:32:51', '2025-03-13 08:32:51'),
(67, 1, '2025-03-03', '08:00:00', '12:07:00', '12:07:00', '17:33:00', '2025-03-13 08:35:10', '2025-03-13 08:35:10'),
(68, 1, '2025-03-04', '08:00:00', '12:18:00', '12:18:00', '17:30:00', '2025-03-13 08:36:34', '2025-03-13 08:37:01'),
(69, 1, '2025-03-05', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-13 08:37:28', '2025-03-13 08:37:28'),
(70, 1, '2025-03-06', '08:00:00', '12:10:00', '12:10:00', '17:00:00', '2025-03-13 08:39:29', '2025-03-13 08:39:29'),
(71, 1, '2025-03-07', '07:53:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-13 08:39:56', '2025-03-13 08:39:56'),
(72, 1, '2025-03-08', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-13 08:40:13', '2025-03-13 08:40:13'),
(73, 1, '2025-03-10', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-13 08:42:05', '2025-03-13 08:42:05'),
(74, 1, '2025-03-11', '07:54:00', '12:07:00', '12:07:00', '17:30:00', '2025-03-13 08:43:48', '2025-03-13 08:43:48'),
(75, 1, '2025-03-13', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-13 08:44:11', '2025-03-13 08:44:11'),
(76, 1, '2025-03-12', '08:00:00', '12:00:00', '12:00:00', '17:00:00', '2025-03-13 08:44:21', '2025-03-13 08:44:21'),
(77, 1, '2025-03-14', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-14 05:21:51', '2025-03-14 05:21:51'),
(78, 1, '2025-03-15', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-17 01:06:39', '2025-03-17 01:06:39'),
(79, 1, '2025-03-17', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-17 01:06:51', '2025-03-17 01:06:51'),
(80, 1, '2025-03-18', '08:00:00', '12:00:00', '12:00:00', '17:30:00', '2025-03-18 05:44:10', '2025-03-18 05:44:10'),
(81, 2, '2025-03-15', '08:00:00', '12:00:00', '12:00:00', '17:00:00', '2025-03-18 07:29:52', '2025-03-18 07:29:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'ryan', '$2y$10$JA8VF/rKTI.60/CTpEoX3O9p3sIOKcYVnu.hEmNE87.MVKc/4h5X.'),
(2, 'vivar', '$2y$10$6pLV7OgW/Lf8tBl/CRU1dOtBE10ufqr5bvRgJALBAnWVcocjGCb2y');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dtr_records`
--
ALTER TABLE `dtr_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dtr_records`
--
ALTER TABLE `dtr_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dtr_records`
--
ALTER TABLE `dtr_records`
  ADD CONSTRAINT `dtr_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
