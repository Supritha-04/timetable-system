-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 15, 2026 at 09:58 AM
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
-- Database: `timatable_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `classrooms`
--

CREATE TABLE `classrooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classrooms`
--

INSERT INTO `classrooms` (`room_id`, `room_name`, `capacity`) VALUES
(1, 'AIML-CLASSROOM-1', 60),
(2, 'AIML-CLASSROOM-2', 60),
(3, 'AIML-CLASSROOM-3', 60),
(4, 'IOT-CLASSROOM-1', 60);

-- --------------------------------------------------------

--
-- Table structure for table `constraints`
--

CREATE TABLE `constraints` (
  `constraint_id` int(11) NOT NULL,
  `constraint_name` varchar(150) DEFAULT NULL,
  `constraint_type` enum('HARD','SOFT') DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `max_lectures_per_day` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `name`, `email`, `max_lectures_per_day`) VALUES
(1, 'Ms. R. Jyothsna', 'jyothsna@vnrvjiet.ac.in', 6),
(2, 'Ms. Preety Singh', 'preety.singh@vnrvjiet.ac.in', 6),
(3, 'Ms. Sri Pavani', 'sri.pavani@vnrvjiet.ac.in', 6),
(4, 'Ms. Veda Sahiti', 'veda.sahiti@vnrvjiet.ac.in', 6),
(5, 'Dr. Kousar Nikhath', 'kousar.nikhath@vnrvjiet.ac.in', 6),
(6, 'Dr. Chalumuru Suresh', 'chalumuru.suresh@vnrvjiet.ac.in', 6),
(7, 'Ms. M. Swapnakumari', 'swapnakumari@vnrvjiet.ac.in', 6),
(8, 'Dr. Rachel Irdaya Raj', 'rachel.irdaya@vnrvjiet.ac.in', 6),
(9, 'Dr. Ayesha Salma', 'ayesha.salma@vnrvjiet.ac.in', 6),
(10, 'Mr. U. Veeresh', 'u.veeresh@vnrvjiet.ac.in', 6),
(11, 'Mr. E. Gurumohan Rao', 'gurumohan.rao@vnrvjiet.ac.in', 6),
(12, 'Mr. V. Kishore', 'v.kishore@vnrvjiet.ac.in', 6),
(13, 'Dr. Sailaja Simma', 'sailaja.simma@vnrvjiet.ac.in', 6),
(14, 'Ms. P. Sujatha', 'p.sujatha@vnrvjiet.ac.in', 6),
(15, 'Dr. K. Madhavi', 'k.madhavi@vnrvjiet.ac.in', 6),
(16, 'Dr. A. Kousar Nikhath', 'a.kousar@vnrvjiet.ac.in', 6),
(17, 'Mr. Shaik Mabasha', 'shaik.mabasha@vnrvjiet.ac.in', 6),
(18, 'Dr. Khamar Jahan', 'khamar.jahan@vnrvjiet.ac.in', 6),
(19, 'Dr. Lalitha Sreedevi', 'lalitha.sreedevi@vnrvjiet.ac.in', 6),
(20, 'Dr. Ch. VLL Kusuma Kumari', 'kusuma.kumari@vnrvjiet.ac.in', 6),
(21, 'Dr. A. Harshavardhan', 'harshavardhan@vnrvjiet.ac.in', 6),
(22, 'Mr. K. Sreenivas Rao', 'sreenivas.rao@vnrvjiet.ac.in', 6),
(23, 'Dr. Sudha Rani', 'sudha.rani@vnrvjiet.ac.in', 6),
(24, 'Mr. K. Ashok', 'k.ashok@vnrvjiet.ac.in', 6),
(25, 'Ms. P. Lakshmi Prasanna', 'lakshmi.prasanna@vnrvjiet.ac.in', 6),
(26, 'Mr. JVA Bala Krishna', 'bala.krishna@vnrvjiet.ac.in', 6),
(27, 'Mr. D. Bhupesh', 'd.bhupesh@vnrvjiet.ac.in', 6),
(28, 'Ms. Akhila Tejaswini', 'akhila.tejaswini@vnrvjiet.ac.in', 6),
(29, 'Ms. K. Anusha', 'k.anusha@vnrvjiet.ac.in', 6),
(30, 'Dr. G. Nagaraju', 'g.nagaraju@vnrvjiet.ac.in', 6),
(31, 'Ms. P. Swetha', 'p.swetha@vnrvjiet.ac.in', 6),
(32, 'Dr. K. Archana Kalidindi', 'archana.kalidindi@vnrvjiet.ac.in', 6),
(33, 'Ms. K. B. Anusha', 'kb.anusha@vnrvjiet.ac.in', 6),
(34, 'Ms.K.Veena', 'veena_k@vnrvjiet.in', 6),
(35, 'Dr. Y.V.Sudha Devi ', 'sudhadevi_yv@vnrvjiet.in', 6);

-- --------------------------------------------------------

--
-- Table structure for table `faculty_availability`
--

CREATE TABLE `faculty_availability` (
  `availability_id` int(11) NOT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `timeslot_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `open_elective_slot`
--

CREATE TABLE `open_elective_slot` (
  `id` int(11) NOT NULL,
  `timeslot_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `open_elective_slot`
--

INSERT INTO `open_elective_slot` (`id`, `timeslot_id`, `created_at`) VALUES
(9, 8, '2026-03-12 20:05:57'),
(10, 9, '2026-03-12 20:05:57'),
(11, 24, '2026-03-12 20:05:57');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_name`) VALUES
(1, 'III-A CSE-AIML'),
(2, 'III-B CSE-AIML'),
(3, 'III-C CSE-AIML'),
(4, 'III CSE-IoT');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `credits` int(11) NOT NULL,
  `is_lab` tinyint(1) DEFAULT 0,
  `is_open_elective` tinyint(1) NOT NULL DEFAULT 0,
  `is_non_credit` tinyint(1) DEFAULT 0,
  `branch` varchar(10) DEFAULT NULL,
  `weekly_hours` int(11) DEFAULT NULL,
  `lecture_hours` int(11) DEFAULT 0,
  `practical_hours` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `credits`, `is_lab`, `is_open_elective`, `is_non_credit`, `branch`, `weekly_hours`, `lecture_hours`, `practical_hours`) VALUES
(1, 'Neural Networks and Deep Learning', 3, 0, 0, 0, 'AIML', NULL, 4, 0),
(2, 'Natural Language Processing', 3, 0, 0, 0, 'AIML', NULL, 4, 0),
(3, 'Cloud Computing', 3, 0, 0, 0, 'AIML', NULL, 4, 0),
(4, 'PMOB', 3, 0, 0, 0, 'COMMON', NULL, 3, 0),
(5, 'Ancient Wisdom', 0, 0, 0, 1, 'COMMON', NULL, 2, 0),
(6, 'Summer Internship', 2, 0, 0, 0, 'COMMON', NULL, 0, 4),
(9, 'ACS Lab', 1, 1, 0, 0, 'COMMON', NULL, 0, 3),
(10, 'NNDL Lab', 1, 1, 0, 0, 'AIML', NULL, 0, 3),
(11, 'NLP Lab', 1, 1, 0, 0, 'AIML', NULL, 0, 3),
(12, 'Embedded System Design', 3, 0, 0, 0, 'IOT', NULL, 4, 0),
(13, 'Machine Learning and Neural Networks', 3, 0, 0, 0, 'IOT', NULL, 4, 0),
(14, 'Artificial Intelligence', 3, 0, 0, 0, 'IOT', NULL, 4, 0),
(15, 'MLNN Lab', 1, 1, 0, 0, 'IOT', NULL, 0, 3),
(16, 'ESD Lab', 1, 1, 0, 0, 'IOT', NULL, 0, 3),
(17, 'Open Elective', 0, 0, 1, 0, 'COMMON', NULL, 3, 0),
(18, 'MTP', 0, 0, 0, 0, 'COMMON', NULL, 0, 0),
(19, 'ECA/CCA', 0, 0, 0, 0, 'COMMON', NULL, 0, 0),
(20, 'SPORTS', 0, 0, 0, 0, 'COMMON', NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `subject_faculty`
--

CREATE TABLE `subject_faculty` (
  `subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_faculty`
--

INSERT INTO `subject_faculty` (`subject_id`, `section_id`, `faculty_id`) VALUES
(1, 1, 1),
(1, 2, 6),
(1, 3, 21),
(2, 1, 2),
(2, 2, 16),
(2, 3, 16),
(3, 1, 4),
(3, 2, 4),
(3, 3, 27),
(3, 4, 27),
(4, 1, 3),
(4, 2, 15),
(4, 3, 20),
(4, 4, 3),
(5, 1, 13),
(5, 2, 13),
(5, 3, 13),
(5, 4, 13),
(6, 1, 34),
(6, 2, 17),
(6, 3, 25),
(6, 4, 29),
(9, 1, 8),
(9, 1, 9),
(9, 2, 18),
(9, 2, 19),
(9, 3, 23),
(9, 3, 24),
(9, 4, 24),
(9, 4, 35),
(10, 1, 1),
(10, 1, 4),
(10, 2, 1),
(10, 2, 6),
(10, 3, 6),
(10, 3, 21),
(11, 1, 2),
(11, 1, 7),
(11, 2, 12),
(11, 2, 16),
(11, 3, 16),
(11, 3, 22),
(12, 4, 26),
(13, 4, 17),
(15, 4, 17),
(15, 4, 27),
(16, 4, 26),
(16, 4, 28);

-- --------------------------------------------------------

--
-- Table structure for table `timeslots`
--

CREATE TABLE `timeslots` (
  `timeslot_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `period_no` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timeslots`
--

INSERT INTO `timeslots` (`timeslot_id`, `day`, `period_no`, `start_time`, `end_time`) VALUES
(1, 'Monday', 1, '10:00:00', '11:00:00'),
(2, 'Monday', 2, '11:00:00', '12:00:00'),
(3, 'Monday', 3, '12:00:00', '13:00:00'),
(4, 'Monday', 4, '13:40:00', '14:40:00'),
(5, 'Monday', 5, '14:40:00', '15:40:00'),
(6, 'Monday', 6, '15:40:00', '16:40:00'),
(7, 'Tuesday', 1, '10:00:00', '11:00:00'),
(8, 'Tuesday', 2, '11:00:00', '12:00:00'),
(9, 'Tuesday', 3, '12:00:00', '13:00:00'),
(10, 'Tuesday', 4, '13:40:00', '14:40:00'),
(11, 'Tuesday', 5, '14:40:00', '15:40:00'),
(12, 'Tuesday', 6, '15:40:00', '16:40:00'),
(13, 'Wednesday', 1, '10:00:00', '11:00:00'),
(14, 'Wednesday', 2, '11:00:00', '12:00:00'),
(15, 'Wednesday', 3, '12:00:00', '13:00:00'),
(16, 'Wednesday', 4, '13:40:00', '14:40:00'),
(17, 'Wednesday', 5, '14:40:00', '15:40:00'),
(18, 'Wednesday', 6, '15:40:00', '16:40:00'),
(19, 'Thursday', 1, '10:00:00', '11:00:00'),
(20, 'Thursday', 2, '11:00:00', '12:00:00'),
(21, 'Thursday', 3, '12:00:00', '13:00:00'),
(22, 'Thursday', 4, '13:40:00', '14:40:00'),
(23, 'Thursday', 5, '14:40:00', '15:40:00'),
(24, 'Thursday', 6, '15:40:00', '16:40:00'),
(25, 'Friday', 1, '10:00:00', '11:00:00'),
(26, 'Friday', 2, '11:00:00', '12:00:00'),
(27, 'Friday', 3, '12:00:00', '13:00:00'),
(28, 'Friday', 4, '13:40:00', '14:40:00'),
(29, 'Friday', 5, '14:40:00', '15:40:00'),
(30, 'Friday', 6, '15:40:00', '16:40:00'),
(31, 'Saturday', 1, '10:00:00', '11:00:00'),
(32, 'Saturday', 2, '11:00:00', '12:00:00'),
(33, 'Saturday', 3, '12:00:00', '13:00:00'),
(34, 'Saturday', 4, '13:40:00', '14:40:00'),
(35, 'Saturday', 5, '14:40:00', '15:40:00'),
(36, 'Saturday', 6, '15:40:00', '16:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `timetable_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `timeslot_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`timetable_id`, `section_id`, `subject_id`, `room_id`, `timeslot_id`) VALUES
(9940, 1, 6, 1, 31),
(9941, 1, 6, 1, 33),
(9942, 1, 6, 1, 5),
(9943, 1, 6, 1, 6),
(9944, 1, 1, 1, 13),
(9945, 1, 1, 1, 7),
(9946, 1, 1, 1, 32),
(9947, 1, 1, 1, 19),
(9948, 1, 2, 1, 34),
(9949, 1, 2, 1, 14),
(9950, 1, 2, 1, 10),
(9951, 1, 2, 1, 20),
(9952, 1, 3, 1, 11),
(9953, 1, 3, 1, 28),
(9954, 1, 3, 1, 15),
(9955, 1, 3, 1, 4),
(9956, 1, 4, 1, 29),
(9957, 1, 4, 1, 21),
(9958, 1, 4, 1, 35),
(9959, 1, 11, 1, 25),
(9960, 1, 11, 1, 26),
(9961, 1, 11, 1, 27),
(9962, 1, 5, 1, 22),
(9963, 1, 5, 1, 23),
(9964, 1, 10, 1, 16),
(9965, 1, 10, 1, 17),
(9966, 1, 10, 1, 18),
(9967, 1, 9, 1, 1),
(9968, 1, 9, 1, 2),
(9969, 1, 9, 1, 3),
(9970, 2, 10, 1, 28),
(9971, 2, 10, 1, 29),
(9972, 2, 10, 1, 30),
(9973, 2, 6, 1, 5),
(9974, 2, 6, 1, 6),
(9975, 2, 6, 1, 27),
(9976, 2, 6, 1, 21),
(9977, 2, 5, 1, 35),
(9978, 2, 5, 1, 36),
(9979, 2, 1, 1, 1),
(9980, 2, 1, 1, 7),
(9981, 2, 1, 1, 31),
(9982, 2, 1, 1, 19),
(9983, 2, 2, 1, 10),
(9984, 2, 2, 1, 25),
(9985, 2, 2, 1, 32),
(9986, 2, 2, 1, 2),
(9987, 2, 3, 1, 20),
(9988, 2, 3, 1, 3),
(9989, 2, 3, 1, 33),
(9990, 2, 3, 1, 26),
(9991, 2, 4, 1, 11),
(9992, 2, 4, 1, 4),
(9993, 2, 4, 1, 34),
(9994, 2, 9, 1, 13),
(9995, 2, 9, 1, 14),
(9996, 2, 9, 1, 15),
(9997, 2, 11, 1, 16),
(9998, 2, 11, 1, 17),
(9999, 2, 11, 1, 18),
(10000, 3, 6, 1, 5),
(10001, 3, 6, 1, 6),
(10002, 3, 6, 1, 34),
(10003, 3, 6, 1, 29),
(10004, 3, 1, 1, 7),
(10005, 3, 1, 1, 25),
(10006, 3, 1, 1, 1),
(10007, 3, 1, 1, 19),
(10008, 3, 2, 1, 26),
(10009, 3, 2, 1, 35),
(10010, 3, 2, 1, 20),
(10011, 3, 2, 1, 13),
(10012, 3, 3, 1, 21),
(10013, 3, 3, 1, 27),
(10014, 3, 3, 1, 36),
(10015, 3, 3, 1, 14),
(10016, 3, 4, 1, 15),
(10017, 3, 4, 1, 28),
(10018, 3, 4, 1, 4),
(10019, 3, 9, 1, 31),
(10020, 3, 9, 1, 32),
(10021, 3, 9, 1, 33),
(10022, 3, 10, 1, 16),
(10023, 3, 10, 1, 17),
(10024, 3, 10, 1, 18),
(10025, 3, 5, 1, 2),
(10026, 3, 5, 1, 3),
(10027, 3, 11, 1, 10),
(10028, 3, 11, 1, 11),
(10029, 3, 11, 1, 12),
(10030, 4, 6, 1, 33),
(10031, 4, 6, 1, 6),
(10032, 4, 6, 1, 11),
(10033, 4, 6, 1, 12),
(10034, 4, 5, 1, 13),
(10035, 4, 5, 1, 14),
(10036, 4, 3, 1, 4),
(10037, 4, 3, 1, 19),
(10038, 4, 3, 1, 31),
(10039, 4, 3, 1, 25),
(10040, 4, 4, 1, 32),
(10041, 4, 4, 1, 20),
(10042, 4, 4, 1, 15),
(10043, 4, 12, 1, 21),
(10044, 4, 12, 1, 7),
(10045, 4, 12, 1, 34),
(10046, 4, 12, 1, 5),
(10047, 4, 13, 1, 26),
(10048, 4, 13, 1, 35),
(10049, 4, 13, 1, 10),
(10050, 4, 13, 1, 22),
(10051, 4, 15, 1, 16),
(10052, 4, 15, 1, 17),
(10053, 4, 15, 1, 18),
(10054, 4, 16, 1, 28),
(10055, 4, 16, 1, 29),
(10056, 4, 16, 1, 30),
(10057, 4, 9, 1, 1),
(10058, 4, 9, 1, 2),
(10059, 4, 9, 1, 3),
(10060, 1, 17, 1, 8),
(10061, 1, 17, 1, 9),
(10062, 1, 17, 1, 24),
(10063, 2, 17, 1, 8),
(10064, 2, 17, 1, 9),
(10065, 2, 17, 1, 24),
(10066, 3, 17, 1, 8),
(10067, 3, 17, 1, 9),
(10068, 3, 17, 1, 24),
(10069, 4, 17, 1, 8),
(10070, 4, 17, 1, 9),
(10071, 4, 17, 1, 24),
(10072, 1, 18, 1, 12),
(10073, 1, 19, 1, 30),
(10074, 1, 20, 1, 36),
(10075, 2, 18, 1, 12),
(10076, 3, 18, 1, 30),
(10077, 4, 18, 1, 36);

-- --------------------------------------------------------

--
-- Table structure for table `timetable_faculty`
--

CREATE TABLE `timetable_faculty` (
  `timetable_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_faculty`
--

INSERT INTO `timetable_faculty` (`timetable_id`, `faculty_id`) VALUES
(9940, 34),
(9941, 34),
(9942, 34),
(9943, 34),
(9944, 1),
(9945, 1),
(9946, 1),
(9947, 1),
(9948, 2),
(9949, 2),
(9950, 2),
(9951, 2),
(9952, 4),
(9953, 4),
(9954, 4),
(9955, 4),
(9956, 3),
(9957, 3),
(9958, 3),
(9959, 2),
(9959, 7),
(9960, 2),
(9960, 7),
(9961, 2),
(9961, 7),
(9962, 13),
(9963, 13),
(9964, 1),
(9964, 4),
(9965, 1),
(9965, 4),
(9966, 1),
(9966, 4),
(9967, 8),
(9967, 9),
(9968, 8),
(9968, 9),
(9969, 8),
(9969, 9),
(9970, 1),
(9970, 6),
(9971, 1),
(9971, 6),
(9972, 1),
(9972, 6),
(9973, 17),
(9974, 17),
(9975, 17),
(9976, 17),
(9977, 13),
(9978, 13),
(9979, 6),
(9980, 6),
(9981, 6),
(9982, 6),
(9983, 16),
(9984, 16),
(9985, 16),
(9986, 16),
(9987, 4),
(9988, 4),
(9989, 4),
(9990, 4),
(9991, 15),
(9992, 15),
(9993, 15),
(9994, 18),
(9994, 19),
(9995, 18),
(9995, 19),
(9996, 18),
(9996, 19),
(9997, 12),
(9997, 16),
(9998, 12),
(9998, 16),
(9999, 12),
(9999, 16),
(10000, 25),
(10001, 25),
(10002, 25),
(10003, 25),
(10004, 21),
(10005, 21),
(10006, 21),
(10007, 21),
(10008, 16),
(10009, 16),
(10010, 16),
(10011, 16),
(10012, 27),
(10013, 27),
(10014, 27),
(10015, 27),
(10016, 20),
(10017, 20),
(10018, 20),
(10019, 23),
(10019, 24),
(10020, 23),
(10020, 24),
(10021, 23),
(10021, 24),
(10022, 6),
(10022, 21),
(10023, 6),
(10023, 21),
(10024, 6),
(10024, 21),
(10025, 13),
(10026, 13),
(10027, 16),
(10027, 22),
(10028, 16),
(10028, 22),
(10029, 16),
(10029, 22),
(10030, 29),
(10031, 29),
(10032, 29),
(10033, 29),
(10034, 13),
(10035, 13),
(10036, 27),
(10037, 27),
(10038, 27),
(10039, 27),
(10040, 3),
(10041, 3),
(10042, 3),
(10043, 26),
(10044, 26),
(10045, 26),
(10046, 26),
(10047, 17),
(10048, 17),
(10049, 17),
(10050, 17),
(10051, 17),
(10051, 27),
(10052, 17),
(10052, 27),
(10053, 17),
(10053, 27),
(10054, 26),
(10054, 28),
(10055, 26),
(10055, 28),
(10056, 26),
(10056, 28),
(10057, 24),
(10057, 35),
(10058, 24),
(10058, 35),
(10059, 24),
(10059, 35);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN','STAFF','STUDENT') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Supritha', '23071a6693@vnrvjiet.ac.in', 'Supritha2006', 'ADMIN', '2026-02-16 10:20:04'),
(2, 'Admin', 'admin@vnrvjiet.ac.in', '$2y$10$ItBiTJBOiMAq0PifRUkwP.NAhUh/W2yPaw4l4xEn/LyOQXXuQgoaC', 'ADMIN', '2026-02-16 13:02:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `constraints`
--
ALTER TABLE `constraints`
  ADD PRIMARY KEY (`constraint_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`);

--
-- Indexes for table `faculty_availability`
--
ALTER TABLE `faculty_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `timeslot_id` (`timeslot_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `open_elective_slot`
--
ALTER TABLE `open_elective_slot`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_single_row` (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `subject_faculty`
--
ALTER TABLE `subject_faculty`
  ADD PRIMARY KEY (`subject_id`,`section_id`,`faculty_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `timeslots`
--
ALTER TABLE `timeslots`
  ADD PRIMARY KEY (`timeslot_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`timetable_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `timeslot_id` (`timeslot_id`);

--
-- Indexes for table `timetable_faculty`
--
ALTER TABLE `timetable_faculty`
  ADD PRIMARY KEY (`timetable_id`,`faculty_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `classrooms`
--
ALTER TABLE `classrooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `constraints`
--
ALTER TABLE `constraints`
  MODIFY `constraint_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `faculty_availability`
--
ALTER TABLE `faculty_availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `open_elective_slot`
--
ALTER TABLE `open_elective_slot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `timeslots`
--
ALTER TABLE `timeslots`
  MODIFY `timeslot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10078;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `faculty_availability`
--
ALTER TABLE `faculty_availability`
  ADD CONSTRAINT `faculty_availability_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faculty_availability_ibfk_2` FOREIGN KEY (`timeslot_id`) REFERENCES `timeslots` (`timeslot_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `subject_faculty`
--
ALTER TABLE `subject_faculty`
  ADD CONSTRAINT `subject_faculty_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_faculty_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_faculty_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`),
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `classrooms` (`room_id`),
  ADD CONSTRAINT `timetable_ibfk_4` FOREIGN KEY (`timeslot_id`) REFERENCES `timeslots` (`timeslot_id`);

--
-- Constraints for table `timetable_faculty`
--
ALTER TABLE `timetable_faculty`
  ADD CONSTRAINT `timetable_faculty_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetable` (`timetable_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_faculty_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
