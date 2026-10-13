-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 07, 2026 at 01:14 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `divisional_secretariat_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `alloc_receive`
--

DROP TABLE IF EXISTS `alloc_receive`;
CREATE TABLE IF NOT EXISTS `alloc_receive` (
  `rid` int NOT NULL AUTO_INCREMENT,
  `head` varchar(255) NOT NULL,
  `vote` varchar(100) NOT NULL,
  `insti_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expire_date` date DEFAULT NULL,
  `extended_date` date DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `program` varchar(25) DEFAULT NULL,
  `project` varchar(25) DEFAULT NULL,
  `matter_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `inventory_allocation_id` int DEFAULT NULL,
  PRIMARY KEY (`rid`),
  KEY `idx_inventory_allocation_id` (`inventory_allocation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `alloc_receive`
--

INSERT INTO `alloc_receive` (`rid`, `head`, `vote`, `insti_id`, `amount`, `expire_date`, `extended_date`, `user_id`, `received_date`, `program`, `project`, `matter_id`, `created_by`, `created_at`, `inventory_allocation_id`) VALUES
(21, '103', '2202', 16, 34000000.00, '2026-12-31', NULL, 75, '2026-01-08', '2', '18', 12, NULL, '2026-04-05 06:03:51', NULL),
(22, '103', '1-1501', 16, 4250000.00, '2026-12-31', NULL, 75, '2026-01-22', '02', '18', 13, NULL, '2026-04-05 06:03:51', NULL),
(23, '274', '1', 23, 1374800.00, '2026-12-31', '2026-12-31', NULL, '2026-01-13', '0', '11', 14, NULL, '2026-04-05 06:03:51', NULL),
(24, '274', '2', 23, 589200.00, '2026-12-31', '2026-12-31', NULL, '2026-01-13', '0', '11', 14, NULL, '2026-04-05 06:03:51', NULL),
(25, '274', '0-1001', 23, 9000000.00, '2026-12-31', NULL, 5079, '2026-01-08', '1', '2', 17, NULL, '2026-04-05 06:03:51', NULL),
(26, '274', '0-1002', 23, 165000.00, '2026-12-31', NULL, 5079, '2026-01-08', '1', '2', 18, NULL, '2026-04-05 06:03:51', NULL),
(27, '274', '0-1003', 23, 3200000.00, '2026-12-31', NULL, NULL, '2026-01-08', '1', '2', 27, NULL, '2026-04-05 06:03:51', NULL),
(28, '274', '0-1101', 23, 59200.00, '2026-12-31', NULL, 5079, '2026-01-08', '1', '2', 11, NULL, '2026-04-05 06:03:51', NULL),
(29, '274', '0-1101', 23, 100800.00, '2026-12-31', NULL, 5079, '2026-01-08', '1', '2', 20, NULL, '2026-04-05 06:03:51', NULL),
(30, '274', '0-1201', 23, 183000.00, '2026-12-31', NULL, 5079, '2026-01-08', '1', '2', 21, NULL, '2026-04-05 06:03:51', 3),
(31, '274', '0-1202-002', 23, 182820.00, '2026-12-31', NULL, 5079, '2026-01-08', '1', '2', 22, NULL, '2026-04-05 06:03:51', NULL),
(34, '274', '0-1001', 23, 3500000.00, '2026-12-31', NULL, 5079, '2026-01-08', '1', '1', 17, NULL, '2026-04-05 06:03:51', NULL),
(35, '149', '0-1001', 28, 362962.00, '2026-12-31', '2026-12-31', 40, '2026-02-03', '03', '02', 17, NULL, '2026-04-05 06:03:51', NULL),
(36, '149', '1003', 28, 108600.00, '2026-12-31', '2026-12-31', 40, '2026-02-06', '03', '02', 27, NULL, '2026-04-05 06:03:51', NULL),
(37, '149', '2202', 28, 4800000.00, '2027-12-31', '2026-12-31', 40, '2026-02-06', '48', '2', 23, NULL, '2026-04-05 06:03:51', NULL),
(38, '103', '1001', 16, 113926.00, '2027-12-31', '2026-12-31', 75, '2026-02-03', '18', '02', 17, NULL, '2026-04-05 06:03:51', NULL),
(39, '103', '1003', 16, 36200.00, '2027-12-31', '2026-12-31', 75, '2026-02-03', '18', '2', 27, NULL, '2026-04-05 06:03:51', NULL),
(40, '103', '1101', 16, 6000.00, '2026-12-31', '2026-12-31', 75, '2026-02-03', '18', '02', 11, NULL, '2026-04-05 06:03:51', NULL),
(41, '103', '1402', 16, 2600.00, '2026-12-31', '2026-12-31', 75, '2026-02-03', '18', '02', 8, NULL, '2026-04-05 06:03:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `district`
--

DROP TABLE IF EXISTS `district`;
CREATE TABLE IF NOT EXISTS `district` (
  `district_id` int NOT NULL AUTO_INCREMENT,
  `district_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `province_id` int DEFAULT NULL,
  PRIMARY KEY (`district_id`),
  KEY `fk_districts_province` (`province_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `district`
--

INSERT INTO `district` (`district_id`, `district_name`, `province_id`) VALUES
(1, 'Ampara', 5),
(2, 'Anuradhapura', 7),
(3, 'Badulla', 8),
(4, 'Batticaloa', 5),
(5, 'Colombo', 1),
(6, 'Galle', 3),
(7, 'Gampaha', 1),
(8, 'Hambantota', 3),
(9, 'Jaffna', 4),
(10, 'Kalutara', 1),
(11, 'Kandy', 2),
(12, 'Kegalle', 9),
(13, 'Kilinochchi', 4),
(14, 'Kurunegala', 6),
(15, 'Mannar', 4),
(16, 'Matale', 2),
(17, 'Matara', 3),
(18, 'Monaragala', 8),
(19, 'Mullaitivu', 4),
(20, 'Nuwara Eliya', 2),
(21, 'Polonnaruwa', 7),
(22, 'Puttalam', 6),
(23, 'Ratnapura', 9),
(24, 'Trincomalee', 5),
(25, 'Vavuniya', 4);

-- --------------------------------------------------------

--
-- Table structure for table `dsdivision`
--

DROP TABLE IF EXISTS `dsdivision`;
CREATE TABLE IF NOT EXISTS `dsdivision` (
  `dsid` int NOT NULL AUTO_INCREMENT,
  `dsname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `district_id` int DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`dsid`),
  KEY `fk_ds_division_district` (`district_id`)
) ENGINE=InnoDB AUTO_INCREMENT=330 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `dsdivision`
--

INSERT INTO `dsdivision` (`dsid`, `dsname`, `district_id`, `email`) VALUES
(1, 'Addalaichenai', 1, NULL),
(2, 'Akkaraipattu', 1, NULL),
(3, 'Alayadiwembu', 1, NULL),
(4, 'Damana', 1, NULL),
(5, 'Dehiattakandiya', 1, NULL),
(6, 'Irakkamam', 1, NULL),
(7, 'Kalmunai', 1, NULL),
(8, 'Lahugala', 1, NULL),
(9, 'Mahaoya', 1, NULL),
(10, 'Navithanveli', 1, NULL),
(11, 'Ninthavur', 1, NULL),
(12, 'Padiyathalawa', 1, NULL),
(13, 'Sainthamaruthu', 1, NULL),
(14, 'Samanthurai', 1, NULL),
(15, 'Thirukkovil', 1, NULL),
(16, 'Uhana', 1, NULL),
(18, 'Galenbindunuwewa', 2, NULL),
(19, 'Galnewa', 2, NULL),
(20, 'Horowpothana', 2, NULL),
(21, 'Ipalogama', 2, NULL),
(22, 'Kahatagasdigiliya', 2, NULL),
(23, 'Kebithigollewa', 2, NULL),
(24, 'Mahavilachchiya', 2, NULL),
(25, 'Mihintale', 2, NULL),
(26, 'Nochchiyagama', 2, NULL),
(27, 'Nuwaragam Palatha Central', 2, NULL),
(28, 'Nuwaragam Palatha East', 2, NULL),
(29, 'Palagala', 2, NULL),
(30, 'Palugaswewa', 2, NULL),
(31, 'Rajanganaya', 2, 'moha.divi.rajanganaya@gmail.com'),
(32, 'Rambewa', 2, NULL),
(33, 'Thalawa', 2, NULL),
(34, 'Thambuttegama', 2, NULL),
(35, 'Kekirawa', 2, NULL),
(36, 'Badulla', 3, NULL),
(37, 'Bandarawela', 3, NULL),
(38, 'Ella', 3, NULL),
(39, 'Haldummulla', 3, NULL),
(40, 'Hali-Ela', 3, NULL),
(41, 'Haputale', 3, NULL),
(42, 'Lunugala', 3, NULL),
(43, 'Mahiyanganaya', 3, NULL),
(44, 'Meegahakivula', 3, NULL),
(45, 'Passara', 3, NULL),
(46, 'Rideemaliyadda', 3, NULL),
(47, 'Soranatota', 3, NULL),
(48, 'Uva Paranagama', 3, NULL),
(49, 'Welimada', 3, NULL),
(50, 'Batticaloa', 4, NULL),
(51, 'Eravur Pattu', 4, NULL),
(52, 'Eravur Town', 4, NULL),
(53, 'Kattankudy', 4, NULL),
(54, 'Koralai Pattu', 4, NULL),
(55, 'Koralai Pattu North', 4, NULL),
(56, 'Koralai Pattu South', 4, NULL),
(57, 'Koralai Pattu West', 4, NULL),
(58, 'Manmunai North', 4, NULL),
(59, 'Manmunai Pattu', 4, NULL),
(60, 'Manmunai South and Eruvil Pattu', 4, NULL),
(61, 'Manmunai South West', 4, NULL),
(62, 'Porativu Pattu', 4, NULL),
(63, 'Akurassa', 6, NULL),
(64, 'Ambalangoda', 6, NULL),
(65, 'Baddegama', 6, NULL),
(66, 'Balapitiya', 6, NULL),
(67, 'Benthota', 6, NULL),
(68, 'Elpitiya', 6, NULL),
(69, 'Galle Four Gravets', 6, NULL),
(70, 'Gonapinuwala', 6, NULL),
(71, 'Habaraduwa', 6, NULL),
(72, 'Hiniduma', 6, NULL),
(73, 'Imaduwa', 6, NULL),
(74, 'Karandeniya', 6, NULL),
(75, 'Nagoda', 6, NULL),
(76, 'Neluwa', 6, NULL),
(77, 'Thawalama', 6, NULL),
(78, 'Welivitiya Divithura', 6, NULL),
(79, 'Yakkalamulla', 6, NULL),
(80, 'Attanagalla', 7, NULL),
(81, 'Biyagama', 7, NULL),
(82, 'Divulapitiya', 7, NULL),
(83, 'Dompe', 7, NULL),
(84, 'Gampaha', 7, NULL),
(85, 'Ja-Ela', 7, NULL),
(86, 'Kelaniya', 7, NULL),
(87, 'Mahara', 7, NULL),
(88, 'Minuwangoda', 7, NULL),
(89, 'Mirigama', 7, NULL),
(90, 'Negombo', 7, NULL),
(91, 'Wattala', 7, NULL),
(92, 'Katana', 7, NULL),
(93, 'Ambalantota', 8, NULL),
(94, 'Angunakolapelessa', 8, NULL),
(95, 'Beliatta', 8, NULL),
(96, 'Hambantota', 8, NULL),
(97, 'Katuwana', 8, NULL),
(98, 'Lunugamvehera', 8, NULL),
(99, 'Okewela', 8, NULL),
(100, 'Sooriyawewa', 8, NULL),
(101, 'Tangalle', 8, NULL),
(102, 'Tissamaharama', 8, NULL),
(103, 'Walasmulla', 8, NULL),
(104, 'Weeraketiya', 8, NULL),
(105, 'Colombo', 5, NULL),
(106, 'Dehiwala', 5, NULL),
(107, 'Homagama', 5, NULL),
(108, 'Kaduwela', 5, NULL),
(109, 'Kesbewa', 5, NULL),
(110, 'Kolonnawa', 5, NULL),
(111, 'Maharagama', 5, NULL),
(112, 'Moratuwa', 5, NULL),
(113, 'Padukka', 5, NULL),
(114, 'Ratmalana', 5, NULL),
(115, 'Seethawaka', 5, NULL),
(116, 'Sri Jayawardenepura Kotte', 5, NULL),
(117, 'Thimbirigasyaya', 5, NULL),
(118, 'Hanwella', 5, NULL),
(119, 'Boralesgamuwa', 5, NULL),
(120, 'Delft', 9, NULL),
(121, 'Island North', 9, NULL),
(122, 'Island South', 9, NULL),
(123, 'Jaffna', 9, NULL),
(124, 'Karainagar', 9, NULL),
(125, 'Nallur', 9, NULL),
(126, 'Sandilipay', 9, NULL),
(127, 'Tellippalai', 9, NULL),
(128, 'Uduvil', 9, NULL),
(129, 'Valikamam East', 9, NULL),
(130, 'Valikamam North', 9, NULL),
(131, 'Valikamam South', 9, NULL),
(132, 'Valikamam West', 9, NULL),
(133, 'Vadamarachchi East', 9, NULL),
(134, 'Vadamarachchi North', 9, NULL),
(135, 'Vadamarachchi South West', 9, NULL),
(136, 'Agalawatta', 10, NULL),
(137, 'Bandaragama', 10, NULL),
(138, 'Beruwala', 10, NULL),
(139, 'Bulathsinhala', 10, NULL),
(140, 'Dodangoda', 10, NULL),
(141, 'Horana', 10, NULL),
(142, 'Ingiriya', 10, NULL),
(143, 'Kalutara', 10, NULL),
(144, 'Madurawala', 10, NULL),
(145, 'Matugama', 10, NULL),
(146, 'Millaniya', 10, NULL),
(147, 'Panadura', 10, NULL),
(148, 'Palindanuwara', 10, NULL),
(149, 'Walallavita', 10, NULL),
(150, 'Akurana', 11, NULL),
(151, 'Delthota', 11, NULL),
(152, 'Doluwa', 11, NULL),
(153, 'Gangawata Korale', 11, NULL),
(154, 'Ganga Ihala Korale', 11, NULL),
(155, 'Harispattuwa', 11, NULL),
(156, 'Hatharaliyadda', 11, NULL),
(157, 'Kundasale', 11, NULL),
(158, 'Medadumbara', 11, NULL),
(159, 'Minipe', 11, NULL),
(160, 'Panvila', 11, NULL),
(161, 'Pasbage Korale', 11, NULL),
(162, 'Pathadumbara', 11, NULL),
(163, 'Pathahewaheta', 11, NULL),
(164, 'Poojapitiya', 11, NULL),
(165, 'Thumpane', 11, NULL),
(166, 'Udadumbara', 11, NULL),
(167, 'Udapalatha', 11, NULL),
(168, 'Udunuwara', 11, NULL),
(169, 'Yatinuwara', 11, NULL),
(170, 'Aranayaka', 12, NULL),
(171, 'Bulathkohupitiya', 12, NULL),
(172, 'Dehiovita', 12, NULL),
(173, 'Deraniyagala', 12, NULL),
(174, 'Galigamuwa', 12, NULL),
(175, 'Kegalle', 12, NULL),
(176, 'Mawanella', 12, NULL),
(177, 'Rambukkana', 12, NULL),
(178, 'Ruwanwella', 12, NULL),
(179, 'Warakapola', 12, NULL),
(180, 'Yatiyanthota', 12, NULL),
(181, 'Kandavalai', 13, NULL),
(182, 'Karachchi', 13, NULL),
(183, 'Pachchilaipalli', 13, NULL),
(184, 'Poonakary', 13, NULL),
(185, 'Alawwa', 14, NULL),
(186, 'Ambanpola', 14, NULL),
(187, 'Bamunakotuwa', 14, NULL),
(188, 'Bingiriya', 14, NULL),
(189, 'Ehetuwewa', 14, NULL),
(190, 'Galgamuwa', 14, NULL),
(191, 'Ganewatta', 14, NULL),
(192, 'Giribawa', 14, NULL),
(193, 'Ibbagamuwa', 14, NULL),
(194, 'Katupotha', 14, NULL),
(195, 'Kobeigane', 14, NULL),
(196, 'Kotavehera', 14, NULL),
(197, 'Kuliyapitiya East', 14, NULL),
(198, 'Kuliyapitiya West', 14, NULL),
(199, 'Kurunegala', 14, NULL),
(200, 'Mahawa', 14, NULL),
(201, 'Mallawapitiya', 14, NULL),
(202, 'Maspotha', 14, NULL),
(203, 'Mawathagama', 14, NULL),
(204, 'Narammala', 14, NULL),
(205, 'Nikaweratiya', 14, NULL),
(206, 'Panduwasnuwara', 14, NULL),
(207, 'Pannala', 14, NULL),
(208, 'Polgahawela', 14, NULL),
(209, 'Polpithigama', 14, NULL),
(210, 'Rasnayakapura', 14, NULL),
(211, 'Rideegama', 14, NULL),
(212, 'Udubaddawa', 14, NULL),
(213, 'Wariyapola', 14, NULL),
(214, 'Weerambugedara', 14, NULL),
(215, 'Madhu', 15, NULL),
(216, 'Mannar', 15, NULL),
(217, 'Manthai West', 15, NULL),
(218, 'Musalai', 15, NULL),
(219, 'Nanaddan', 15, NULL),
(220, 'Ambanganga Korale', 16, NULL),
(221, 'Dambulla', 16, NULL),
(222, 'Galewela', 16, NULL),
(223, 'Laggala-Pallegama', 16, NULL),
(224, 'Matale', 16, NULL),
(225, 'Naula', 16, NULL),
(226, 'Pallepola', 16, NULL),
(227, 'Rattota', 16, NULL),
(228, 'Ukuwela', 16, NULL),
(229, 'Wilgamuwa', 16, NULL),
(230, 'Yatawatta', 16, NULL),
(231, 'Akuressa', 17, NULL),
(232, 'Athuraliya', 17, NULL),
(233, 'Devinuwara', 17, NULL),
(234, 'Dickwella', 17, NULL),
(235, 'Hakmana', 17, NULL),
(236, 'Kamburupitiya', 17, NULL),
(237, 'Kirinda Puhulwella', 17, NULL),
(238, 'Kotapola', 17, NULL),
(239, 'Malimbada', 17, NULL),
(240, 'Matara', 17, NULL),
(241, 'Mulatiyana', 17, NULL),
(242, 'Pasgoda', 17, NULL),
(243, 'Pitabeddara', 17, NULL),
(244, 'Thihagoda', 17, NULL),
(245, 'Weligama', 17, NULL),
(246, 'Welipitiya', 17, NULL),
(247, 'Badalkumbura', 18, NULL),
(248, 'Bibile', 18, NULL),
(249, 'Buttala', 18, NULL),
(250, 'Katharagama', 18, NULL),
(251, 'Madulla', 18, NULL),
(252, 'Medagama', 18, NULL),
(253, 'Moneragala', 18, NULL),
(254, 'Sevanagala', 18, NULL),
(255, 'Siyambalanduwa', 18, NULL),
(256, 'Thanamalvila', 18, NULL),
(257, 'Wellawaya', 18, NULL),
(258, 'Manthai East', 19, NULL),
(259, 'Maritimepattu', 19, NULL),
(260, 'Oddusuddan', 19, NULL),
(261, 'Puthukudiyiruppu', 19, NULL),
(262, 'Thunukkai', 19, NULL),
(263, 'Welioya', 19, NULL),
(264, 'Ambagamuwa', 20, NULL),
(265, 'Hanguranketha', 20, NULL),
(266, 'Kothmale', 20, NULL),
(267, 'Nuwara Eliya', 20, NULL),
(268, 'Walapane', 20, NULL),
(269, 'Norwood', 20, NULL),
(270, 'Kothmale West', 20, NULL),
(271, 'Nildandahinna', 20, NULL),
(272, 'Thalawakale', 20, NULL),
(273, 'Mathurata', 20, NULL),
(274, 'Dimbulagala', 21, NULL),
(275, 'Elahera', 21, NULL),
(276, 'Hingurakgoda', 21, NULL),
(277, 'Lankapura', 21, NULL),
(278, 'Medirigiriya', 21, NULL),
(279, 'Thamankaduwa', 21, NULL),
(280, 'Welikanda', 21, NULL),
(281, 'Anamaduwa', 22, NULL),
(282, 'Arachchikattuwa', 22, NULL),
(283, 'Chilaw', 22, NULL),
(284, 'Dankotuwa', 22, 'divsecdankotuwa@gmail.com'),
(285, 'Kalpitiya', 22, NULL),
(286, 'Karuwalagaswewa', 22, NULL),
(287, 'Madampe', 22, NULL),
(288, 'Mahakumbukkadawala', 22, NULL),
(289, 'Mahawewa', 22, NULL),
(290, 'Mundalama', 22, NULL),
(291, 'Nattandiya', 22, NULL),
(292, 'Nawagattegama', 22, NULL),
(293, 'Pallama', 22, NULL),
(294, 'Puttalam', 22, NULL),
(295, 'Vanathavilluwa', 22, NULL),
(296, 'Wennappuwa', 22, NULL),
(297, 'Ayagama', 23, NULL),
(298, 'Balangoda', 23, NULL),
(299, 'Eheliyagoda', 23, NULL),
(300, 'Elapattha', 23, NULL),
(301, 'Embilipitiya', 23, NULL),
(302, 'Godakawela', 23, NULL),
(303, 'Imbulpe', 23, NULL),
(304, 'Kahawatta', 23, NULL),
(305, 'Kalawana', 23, NULL),
(306, 'Kiriella', 23, NULL),
(307, 'Kolonna', 23, NULL),
(308, 'Kuruvita', 23, NULL),
(309, 'Nivithigala', 23, NULL),
(310, 'Opanayaka', 23, NULL),
(311, 'Pelmadulla', 23, NULL),
(312, 'Ratnapura', 23, NULL),
(313, 'Weligepola', 23, NULL),
(314, 'Gomarankadawala', 24, NULL),
(315, 'Kantalai', 24, NULL),
(316, 'Kinniya', 24, NULL),
(317, 'Kuchchaveli', 24, NULL),
(318, 'Morawewa', 24, NULL),
(319, 'Muttur', 24, NULL),
(320, 'Padaviya', 24, NULL),
(321, 'Seruvila', 24, NULL),
(322, 'Thambalagamuwa', 24, NULL),
(323, 'Trincomalee', 24, NULL),
(324, 'Verugal', 24, NULL),
(325, 'Vavuniya', 25, NULL),
(326, 'Vavuniya North', 25, NULL),
(327, 'Vavuniya South', 25, NULL),
(328, 'Vengalacheddikulam', 25, NULL),
(329, 'Madawachchiya', 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_approvals`
--

DROP TABLE IF EXISTS `inventory_approvals`;
CREATE TABLE IF NOT EXISTS `inventory_approvals` (
  `Approval_ID` int NOT NULL AUTO_INCREMENT,
  `Request_ID` int NOT NULL,
  `DS_Officer_ID` int NOT NULL,
  `dsid` int DEFAULT NULL,
  `Approval_Status` enum('Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT 'Approved',
  `Approval_Date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Approval_ID`),
  KEY `fk_approval_request` (`Request_ID`),
  KEY `fk_approval_ds` (`DS_Officer_ID`),
  KEY `fk_inventory_approvals_dsid` (`dsid`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `inventory_approvals`
--

INSERT INTO `inventory_approvals` (`Approval_ID`, `Request_ID`, `DS_Officer_ID`, `dsid`, `Approval_Status`, `Approval_Date`) VALUES
(5, 6, 5, 31, 'Approved', '2026-01-01 05:06:04'),
(6, 7, 5, 31, 'Approved', '2026-01-01 05:58:59'),
(7, 8, 5, 31, 'Rejected', '2026-01-01 05:59:04'),
(8, 13, 9, NULL, 'Approved', '2026-01-03 09:14:01'),
(9, 14, 5, NULL, 'Approved', '2026-01-03 09:19:04'),
(10, 10, 5, NULL, 'Approved', '2026-01-03 10:40:00'),
(11, 9, 5, NULL, 'Approved', '2026-01-03 10:40:16'),
(12, 15, 9, NULL, 'Approved', '2026-01-03 10:43:15');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
CREATE TABLE IF NOT EXISTS `inventory_categories` (
  `Category_ID` int NOT NULL AUTO_INCREMENT,
  `Category_Name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`Category_ID`),
  UNIQUE KEY `Category_Name` (`Category_Name`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`Category_ID`, `Category_Name`) VALUES
(2, 'Stationery'),
(3, 'IT Accessories');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_issued_items`
--

DROP TABLE IF EXISTS `inventory_issued_items`;
CREATE TABLE IF NOT EXISTS `inventory_issued_items` (
  `Issue_ID` int NOT NULL AUTO_INCREMENT,
  `Request_ID` int NOT NULL,
  `Officer_ID` int NOT NULL,
  `dsid` int DEFAULT NULL,
  `Item_ID` int NOT NULL,
  `Issue_Quantity` int NOT NULL DEFAULT '0',
  `Quantity` int NOT NULL,
  `Issue_Date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Issue_ID`),
  KEY `fk_issue_request` (`Request_ID`),
  KEY `fk_issue_officer` (`Officer_ID`),
  KEY `fk_issue_item` (`Item_ID`),
  KEY `fk_issued_items_dsid` (`dsid`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `inventory_issued_items`
--

INSERT INTO `inventory_issued_items` (`Issue_ID`, `Request_ID`, `Officer_ID`, `dsid`, `Item_ID`, `Issue_Quantity`, `Quantity`, `Issue_Date`) VALUES
(3, 6, 2, 31, 6, 0, 5, '2025-12-31 23:36:46'),
(4, 7, 2, 31, 8, 0, 2, '2026-01-01 00:31:18'),
(5, 13, 7, NULL, 12, 0, 1, '2026-01-03 03:44:48'),
(6, 14, 2, NULL, 7, 0, 1, '2026-01-03 03:50:30'),
(7, 9, 2, NULL, 8, 0, 1, '2026-01-03 05:10:37'),
(8, 10, 2, NULL, 9, 0, 1, '2026-01-03 05:10:41'),
(9, 15, 7, NULL, 11, 0, 2, '2026-01-03 05:13:41');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `Item_ID` int NOT NULL AUTO_INCREMENT,
  `Item_Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Unit_Price` decimal(10,2) NOT NULL,
  `Quantity` int NOT NULL,
  `Total_Value` decimal(10,2) GENERATED ALWAYS AS ((`Unit_Price` * `Quantity`)) STORED,
  `dsid` int DEFAULT NULL,
  `Date_Added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Supplier_ID` int NOT NULL,
  `Min_Quantity` int NOT NULL DEFAULT '5',
  `Category_ID` int DEFAULT NULL,
  PRIMARY KEY (`Item_ID`),
  KEY `fk_item_ds` (`dsid`),
  KEY `fk_items_supplier` (`Supplier_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`Item_ID`, `Item_Name`, `Image`, `Unit_Price`, `Quantity`, `dsid`, `Date_Added`, `Supplier_ID`, `Min_Quantity`, `Category_ID`) VALUES
(6, 'pen', 'uploads/items/1767243815_1536.jpg', 20.00, 95, 31, '2026-01-01 05:03:35', 5, 20, 2),
(7, 'key board', 'uploads/items/1767244482_1139.png', 1000.00, 9, 31, '2026-01-01 05:14:42', 5, 2, 3),
(8, 'Note Book', 'uploads/items/1767244526_6125.jpg', 200.00, 17, 31, '2026-01-01 05:15:26', 6, 5, 2),
(9, 'parper cutter', 'uploads/items/1767253886_4988.jpg', 100.00, 19, 31, '2026-01-01 07:51:26', 6, 5, 2),
(10, 'pen', 'uploads/items/1767416414_2231.jpg', 20.00, 100, 200, '2026-01-03 05:00:14', 7, 20, 2),
(11, 'high lighters', 'uploads/items/1767416986_8178.jpg', 100.00, 48, 200, '2026-01-03 05:09:46', 7, 10, 2),
(12, 'Tipex', 'uploads/items/1767417039_5033.jpg', 150.00, 19, 200, '2026-01-03 05:10:39', 7, 5, 2);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_item_requests`
--

DROP TABLE IF EXISTS `inventory_item_requests`;
CREATE TABLE IF NOT EXISTS `inventory_item_requests` (
  `Request_ID` int NOT NULL AUTO_INCREMENT,
  `Officer_ID` int NOT NULL,
  `dsid` int DEFAULT NULL,
  `Item_ID` int NOT NULL,
  `Quantity` int NOT NULL,
  `Request_Date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Approved_Date` timestamp NULL DEFAULT NULL,
  `Approved_By` int DEFAULT NULL,
  `Status` enum('Pending','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT 'Pending',
  PRIMARY KEY (`Request_ID`),
  KEY `fk_request_officer` (`Officer_ID`),
  KEY `fk_request_item` (`Item_ID`),
  KEY `fk_item_requests_dsid` (`dsid`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `inventory_item_requests`
--

INSERT INTO `inventory_item_requests` (`Request_ID`, `Officer_ID`, `dsid`, `Item_ID`, `Quantity`, `Request_Date`, `Approved_Date`, `Approved_By`, `Status`) VALUES
(6, 2, 31, 6, 5, '2026-01-01 05:05:30', '2026-01-01 05:06:04', 0, 'Approved'),
(7, 2, 31, 8, 2, '2026-01-01 05:16:40', '2026-01-01 05:58:59', 0, 'Approved'),
(8, 2, 31, 7, 1, '2026-01-01 05:58:23', '2026-01-01 05:59:04', 0, 'Rejected'),
(9, 2, 31, 8, 1, '2026-01-01 05:59:49', '2026-01-03 10:40:16', 0, 'Approved'),
(10, 2, 31, 9, 1, '2026-01-01 07:52:42', '2026-01-03 10:40:00', 0, 'Approved'),
(11, 7, NULL, 10, 5, '2026-01-03 05:01:28', NULL, NULL, 'Pending'),
(12, 2, NULL, 6, 1, '2026-01-03 05:13:59', NULL, NULL, 'Pending'),
(13, 7, 200, 12, 1, '2026-01-03 09:13:27', '2026-01-03 09:14:01', 0, 'Approved'),
(14, 2, 31, 7, 1, '2026-01-03 09:18:14', '2026-01-03 09:19:04', 0, 'Approved'),
(15, 7, 200, 11, 2, '2026-01-03 10:42:38', '2026-01-03 10:43:15', 0, 'Approved'),
(16, 7, 200, 10, 1, '2026-01-03 10:49:46', NULL, NULL, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_officer_allocations`
--

DROP TABLE IF EXISTS `inventory_officer_allocations`;
CREATE TABLE IF NOT EXISTS `inventory_officer_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `officer_id` int NOT NULL,
  `dsid` int DEFAULT NULL,
  `year` int NOT NULL,
  `given_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `carry_forward_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `debt_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `final_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `used_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `allocated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `officer_id` (`officer_id`),
  KEY `idx_officer_allocations_dsid` (`dsid`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `inventory_officer_allocations`
--

INSERT INTO `inventory_officer_allocations` (`id`, `officer_id`, `dsid`, `year`, `given_amount`, `carry_forward_amount`, `debt_amount`, `final_amount`, `used_amount`, `allocated_date`) VALUES
(3, 2, 31, 2026, 5000.00, 0.00, 0.00, 5000.00, 1500.00, '2026-01-01 10:34:00'),
(4, 2, 31, 2026, 500.00, 0.00, 0.00, 500.00, 1500.00, '2026-01-01 10:45:46'),
(5, 5, 31, 2026, 6000.00, 0.00, 0.00, 6000.00, 0.00, '2026-01-01 13:05:16'),
(6, 7, 200, 2026, 5000.00, 0.00, 0.00, 5000.00, 150.00, '2026-01-03 10:30:40'),
(7, 7, 200, 2026, 700.00, 0.00, 0.00, 700.00, 150.00, '2026-01-03 10:41:15');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_settings`
--

DROP TABLE IF EXISTS `inventory_settings`;
CREATE TABLE IF NOT EXISTS `inventory_settings` (
  `config_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `config_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`config_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_suppliers`
--

DROP TABLE IF EXISTS `inventory_suppliers`;
CREATE TABLE IF NOT EXISTS `inventory_suppliers` (
  `Supplier_ID` int NOT NULL AUTO_INCREMENT,
  `dsid` int NOT NULL,
  `Supplier_Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Contact_Person` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Contact_Number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Added_Date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Supplier_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `inventory_suppliers`
--

INSERT INTO `inventory_suppliers` (`Supplier_ID`, `dsid`, `Supplier_Name`, `Contact_Person`, `Contact_Number`, `Email`, `Address`, `Added_Date`) VALUES
(5, 31, 'ABC storse', 'Amal Bandara', '0785252456', 'abcstorse12@gmail.com', 'kurunegala,maho', '2026-01-01 05:02:10'),
(6, 31, 'anoma book shop', 'anoma', '0785245856', 'anoma12@gmail.com', 'anuradhapura', '2026-01-01 05:13:17'),
(7, 200, 'Sahas Book Shop', 'Sahas Bandara', '0785245963', 'sahas12@gmail.com', 'Galgamuwa', '2026-01-03 04:59:32');

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

DROP TABLE IF EXISTS `leaves`;
CREATE TABLE IF NOT EXISTS `leaves` (
  `leave_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `leave_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `applied_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `leafsheet` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `status_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT 'Pending',
  PRIMARY KEY (`leave_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `ministries`
--

DROP TABLE IF EXISTS `ministries`;
CREATE TABLE IF NOT EXISTS `ministries` (
  `ministry_id` int NOT NULL AUTO_INCREMENT,
  `ministry_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`ministry_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `ministries`
--

INSERT INTO `ministries` (`ministry_id`, `ministry_name`) VALUES
(1, 'Ministry of Defence'),
(2, 'Ministry of Finance, Planning and Economic Development'),
(3, 'Ministry of Energy'),
(4, 'Ministry of Agriculture, Livestock, Land, and Irrigation'),
(5, 'Ministry of Justice and National Integration'),
(6, 'Ministry of Education, Higher Education and Vocational Education'),
(7, 'Ministry of Women and Child Affairs'),
(8, 'Ministry of Trade, Commerce, Food Security and Co-operative Development'),
(9, 'Ministry of Health and Mass Media'),
(10, 'Ministry of Buddhasasana, Religious and Cultural Affairs'),
(11, 'Ministry of Transport, Highways, Ports and Civil Aviation'),
(12, 'Ministry of Public Security and Parliamentary Affairs'),
(13, 'Ministry of Foreign Affairs, Foreign Employment and Tourism'),
(14, 'Ministry of Environment'),
(15, 'Ministry of Urban Development, Construction and Housing'),
(16, 'Ministry of Industry and Entrepreneurship Development'),
(17, 'Ministry of Rural Development, Social Security, and Community Empowerment'),
(18, 'Ministry of Science and Technology'),
(19, 'Ministry of Labour'),
(20, 'Ministry of Plantation and Community Infrastructure'),
(21, 'Ministry of Public Administration, Provincial Councils and Local Government'),
(22, 'Ministry of Fisheries, Aquatic and Ocean Resources'),
(23, 'Ministry of Digital Economy'),
(24, 'Ministry of Youth Affairs and Sports'),
(25, 'North Central Provincial Council'),
(26, 'North Western Provincial Council'),
(27, 'Northern Provincial Council'),
(28, 'Southern Provincial Council'),
(29, 'Central Provincial Council'),
(30, 'Western Provincial Council'),
(31, 'Uva Provincial Council'),
(32, 'Eastern Provincial Council'),
(33, 'Sabaragamuwa Provincial Council');

-- --------------------------------------------------------

--
-- Table structure for table `provinces`
--

DROP TABLE IF EXISTS `provinces`;
CREATE TABLE IF NOT EXISTS `provinces` (
  `province_id` int NOT NULL AUTO_INCREMENT,
  `province_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`province_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `provinces`
--

INSERT INTO `provinces` (`province_id`, `province_name`) VALUES
(1, 'Western Province'),
(2, 'Central Province'),
(3, 'Southern Province'),
(4, 'Northern Province'),
(5, 'Eastern Province'),
(6, 'North Western Province'),
(7, 'North Central Province'),
(8, 'Uva Province'),
(9, 'Sabaragamuwa Province');

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

DROP TABLE IF EXISTS `section`;
CREATE TABLE IF NOT EXISTS `section` (
  `section_id` int NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `section_name`) VALUES
(1, 'සංවර්ධන අංශය'),
(2, 'සමෘධි අංශය'),
(3, 'ග්‍රාම නිලධාරී'),
(4, 'ක්ෂේත්‍ර නිලධාරීන් (අමාත්‍යංශගත)'),
(5, 'පරිපාලන (DS, ADS, AO)'),
(6, 'ගිණුම් අංශය'),
(7, 'විදේශ රැකියා ප්‍රවර්ධන අංශය'),
(8, 'කුඩා ව්‍යාපාර සංවර්ධන අංශය'),
(9, 'රෙජිස්ට්‍රාර් අංශය'),
(10, 'ආපදා සහන අංශය'),
(11, 'බෞද්ධ කටයුතු අංශය'),
(12, 'විදාතා සම්පත් මධ්‍යස්ථානය'),
(13, 'ඉඩම් අංශය'),
(14, 'හැඳුනුම්පත් අංශය'),
(15, 'සමාජ සේවා අංශය'),
(16, 'ආයතන අංශය');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `NIC` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Birthdate` date DEFAULT NULL,
  `Gender` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Contact` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Role` enum('Admin','DS_Officer','Accountant','Officer') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Limit_ID` int DEFAULT NULL,
  `Created_At` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `province_id` int DEFAULT NULL,
  `district_id` int DEFAULT NULL,
  `dsid` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Username` (`Username`),
  KEY `fk_user_province` (`province_id`),
  KEY `fk_user_district` (`district_id`),
  KEY `fk_user_ds` (`dsid`),
  KEY `fk_user_section` (`section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ID`, `Name`, `NIC`, `Birthdate`, `Gender`, `Position`, `Contact`, `Username`, `Password`, `Role`, `Email`, `Limit_ID`, `Created_At`, `province_id`, `district_id`, `dsid`, `section_id`) VALUES
(1, 'H.P.S.S.K.WIJERATHNA', '587843095v', '1987-03-24', 'Female', 'Development Officer', '12345789', 'testuser1', 's1231234', 'Admin', 'giwijerathna87@gmail.com', NULL, '2025-10-29 20:14:25', 7, 2, 31, 14),
(2, 'D.M.S.B Dissanayake', '200000600390', '1989-01-06', 'Male', 'Economics Development Officer', '1234578787', 'testuser2', '434312', 'Officer', 'thbandarahis@gmail.com', NULL, '2025-10-29 20:14:25', 7, 2, 31, 1),
(5, 'Divisional Secretary', '751234567V', '1975-03-12', 'Male', 'Divisional Secretary', '0712345678', 'ds_officer', '12345', 'DS_Officer', 'ds_officer@gmail.com', NULL, '2025-11-02 02:26:59', 1, 2, 31, 1),
(6, 'Accountant User', '881234567V', '1988-09-22', 'Male', 'Accountant', '0771234567', 'accountant', '123', 'Accountant', 'accountant@gmail.com', NULL, '2025-11-03 02:18:04', 1, 2, 31, 1),
(7, 'Amal Bandara', '930555666V', '1993-08-20', 'Male', 'Inventory Officer', '0775556666', 'tester', '2021', 'Officer', 'store.raja@agri.lk', NULL, '2026-01-02 13:23:35', 6, 14, 200, 2),
(8, 'nimali', '934565666V', '1993-08-25', 'female', 'community service Officer', '0775554565', 'accountant1', '2022', 'Accountant', 'store.raja@agri.lk', NULL, '2026-01-02 13:27:01', 6, 14, 200, 2),
(9, 'Upul bandara', '782555666V', '1978-08-20', 'Male', 'Divisional Secretary', '0778856666', 'ds_officer1', '2023', 'DS_Officer', 'dsofficer1@agri.lk', NULL, '2026-01-02 13:37:16', 6, 14, 200, 2);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `district`
--
ALTER TABLE `district`
  ADD CONSTRAINT `fk_districts_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`province_id`);

--
-- Constraints for table `dsdivision`
--
ALTER TABLE `dsdivision`
  ADD CONSTRAINT `fk_ds_division_district` FOREIGN KEY (`district_id`) REFERENCES `district` (`district_id`);

--
-- Constraints for table `inventory_approvals`
--
ALTER TABLE `inventory_approvals`
  ADD CONSTRAINT `fk_approval_ds` FOREIGN KEY (`DS_Officer_ID`) REFERENCES `user` (`ID`),
  ADD CONSTRAINT `fk_approval_request` FOREIGN KEY (`Request_ID`) REFERENCES `inventory_item_requests` (`Request_ID`),
  ADD CONSTRAINT `fk_inventory_approvals_dsid` FOREIGN KEY (`dsid`) REFERENCES `dsdivision` (`dsid`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `inventory_issued_items`
--
ALTER TABLE `inventory_issued_items`
  ADD CONSTRAINT `fk_issued_items_dsid` FOREIGN KEY (`dsid`) REFERENCES `dsdivision` (`dsid`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `fk_item_ds` FOREIGN KEY (`dsid`) REFERENCES `dsdivision` (`dsid`),
  ADD CONSTRAINT `fk_items_supplier` FOREIGN KEY (`Supplier_ID`) REFERENCES `inventory_suppliers` (`Supplier_ID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `inventory_item_requests`
--
ALTER TABLE `inventory_item_requests`
  ADD CONSTRAINT `fk_item_requests_dsid` FOREIGN KEY (`dsid`) REFERENCES `dsdivision` (`dsid`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_item` FOREIGN KEY (`Item_ID`) REFERENCES `inventory_items` (`Item_ID`),
  ADD CONSTRAINT `fk_request_officer` FOREIGN KEY (`Officer_ID`) REFERENCES `user` (`ID`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_district` FOREIGN KEY (`district_id`) REFERENCES `district` (`district_id`),
  ADD CONSTRAINT `fk_user_ds` FOREIGN KEY (`dsid`) REFERENCES `dsdivision` (`dsid`),
  ADD CONSTRAINT `fk_user_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`province_id`),
  ADD CONSTRAINT `fk_user_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
