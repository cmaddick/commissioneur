-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2018 at 08:24 AM
-- Server version: 10.1.25-MariaDB
-- PHP Version: 7.1.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `commission`
--
CREATE DATABASE IF NOT EXISTS `commission` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `commission`;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--
-- Creation: May 11, 2018 at 04:35 AM
--

DROP TABLE IF EXISTS `submissions`;
CREATE TABLE `submissions` (
  `SubmissionID` int(11) NOT NULL,
  `SubmissionOwner` int(11) NOT NULL,
  `ContentType` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ContentPath` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ContentTitle` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ContentDescription` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `DatetimeUploaded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELATIONSHIPS FOR TABLE `submissions`:
--   `SubmissionOwner`
--       `users` -> `UserID`
--

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`SubmissionID`, `SubmissionOwner`, `ContentType`, `ContentPath`, `ContentTitle`, `ContentDescription`, `DatetimeUploaded`) VALUES
(338022385, 431119583, 'image', '../public/resources/usercontent/images/338022385.jpg', 'test img', 'This is a test image', '2018-05-01 08:00:00'),
(579592181, 431119583, 'image', '../public/resources/usercontent/images/579592181.jpg', 'test2', 'test2img', '2018-05-02 15:13:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Creation: Mar 19, 2018 at 08:06 AM
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Email` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `DisplayName` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELATIONSHIPS FOR TABLE `users`:
--

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Email`, `Password`, `DisplayName`) VALUES
(153529177, 'testoop@test.test', '$2y$10$KxAFQSThMFJfh/DAGZyNGO2FWE.0wtttoqWvpSVzgtgTs6h4Zbx06', 'testoop'),
(306843605, 'testtest@test.test', '$2y$10$ct7TQMJIOhnFOa9uIvtlkeiskJIB.jjTfFwNBnWtOQR0ckWvClsce', 'testtesttest'),
(431119583, 'test@test.test', '$2y$10$PQjrypfivn0ym6/RuJBIZum1xvpEUa.J/Ry3o6ia448JrTKh25Jzi', 'testtest'),
(657110603, 'test2@test.test', '$2y$10$mn/oILal4PIeeGHgKsepYODFjmFLmFY7ZNBAXpJC6QwyBRhUl1qoq', 'test2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`SubmissionID`),
  ADD KEY `SubmissionOwner` (`SubmissionOwner`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`SubmissionOwner`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
