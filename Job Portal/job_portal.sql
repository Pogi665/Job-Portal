-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 11:05 AM
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
-- Database: `job_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `applicant` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `connections`
--

CREATE TABLE `connections` (
  `id` int(11) NOT NULL,
  `user1` varchar(30) NOT NULL,
  `user2` varchar(30) NOT NULL,
  `status` enum('pending','accepted') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `connections`
--

INSERT INTO `connections` (`id`, `user1`, `user2`, `status`) VALUES
(33, 'Jhave', 'John Rey', 'accepted'),
(34, 'Becca', 'John Rey', 'accepted'),
(35, 'John Rey', 'employer', 'accepted'),
(36, 'Becca', 'employer', 'accepted'),
(37, 'melvin', 'employer', 'accepted'),
(38, 'Jhave', 'employer', 'accepted'),
(39, 'Renz', 'employer', 'accepted'),
(40, 'test', 'employer', 'accepted'),
(41, 'melvin', 'number', 'accepted'),
(42, 'Becca', 'Jhave', 'accepted'),
(43, 'number', 'employer', 'accepted'),
(44, 'melvin', 'Reizsa', 'accepted'),
(45, 'Becca', 'Reizsa', 'accepted'),
(46, 'melvin', 'Becca', 'accepted'),
(47, 'Becca', 'Renz', 'pending'),
(48, 'Jhave', 'Renz', 'accepted'),
(49, 'catdog', 'Jhave', 'accepted');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `employer` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `location` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `description`, `employer`, `timestamp`, `status`, `location`, `company`) VALUES
(15, 'Hotdog Seller', 'lorem ipsum', 'employer', '2025-05-04 23:30:57', 'pending', 'Bayan', NULL),
(16, 'Web Developer', 'lorem ipsum', 'employer', '2025-05-04 23:31:09', 'pending', 'Bay Laguna', NULL),
(17, 'Tralalelo Tralala', 'lorem ipsum', 'employer', '2025-05-04 23:31:24', 'pending', 'Los Baños, Laguna', NULL),
(18, 'Tripi tropi', 'sadsadsadsa', 'employer', '2025-05-04 23:45:03', 'pending', 'Bay Laguna', 'Brainrot Company'),
(19, 'Web Developer', 'lorem ipsum', 'John Rey', '2025-05-05 02:16:39', 'pending', 'Bay Laguna', 'ICT CMDI'),
(20, 'Barbie doll', 'lorem ipsum', 'Reizsa', '2025-05-05 04:03:03', 'pending', 'Bay Laguna', 'Barbie Corp'),
(21, 'Web Analyst', 'lorem ipsum', 'Reizsa', '2025-05-05 04:47:26', 'pending', 'Bay Laguna', 'Reizsa Inc'),
(22, 'Longganisa Seller', 'Seller ng Longanisa', 'employer', '2025-05-05 08:39:54', 'pending', 'Bay Laguna', 'CMDI ICT'),
(23, 'Cat Dog Analyst', 'lorem ipsum', 'catdog', '2025-05-05 08:52:05', 'pending', 'Bay Laguna', 'Cat Dog Corp.');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `applicant` varchar(255) NOT NULL,
  `resume_url` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(255) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`id`, `job_id`, `applicant`, `resume_url`, `full_name`, `contact_number`, `email`, `cover_letter`, `application_date`, `status`) VALUES
(30, 18, 'Becca', 'https://www.facebook.com/JhalRvin', 'Rebecca Faustino Larios', '09667504783', 'lariosbecca@gmail.com', 'asdasdsadsadsadsadasdsad', '2025-05-05 01:17:01', 'rejected'),
(31, 18, 'Jhave', 'https://www.facebook.com/JhalRvin', 'Jhal Rvin Larios', '09667504783', 'ljhave24@gmail.com', '6465464566546556456', '2025-05-05 01:28:04', 'accepted'),
(32, 19, 'Jhave', 'https://www.facebook.com/JhalRvin', 'Jhal Rvin Larios', '09667504783', 'ljhave24@gmail.com', 'sdasdasdasdasd', '2025-05-05 02:16:56', 'accepted'),
(33, 17, 'Jhave', 'https://www.facebook.com/JhalRvin', 'Jhal Rvin Larios', '09667504783', 'ljhave24@gmail.com', 'asdasdsdsadsad', '2025-05-05 02:27:40', 'accepted'),
(34, 19, 'Renz', 'https://www.facebook.com/JhalRvin', 'Renz Capitan', '09667504783', 'renz@gmail.com', 'sadsadsadsaasdasd', '2025-05-05 03:06:42', 'accepted'),
(35, 18, 'Renz', 'https://www.facebook.com/JhalRvin', 'Renz Capitan', '09667504783', 'renz@gmail.com', 'asdsadsadasd', '2025-05-05 03:36:18', 'accepted'),
(36, 17, 'Renz', NULL, '', '', '', NULL, '2025-05-05 03:42:28', 'pending'),
(37, 16, 'Renz', NULL, '', '', '', NULL, '2025-05-05 03:42:35', 'pending'),
(38, 15, 'Renz', NULL, '', '', '', NULL, '2025-05-05 03:44:51', 'pending'),
(39, 19, 'number', 'https://www.facebook.com/JhalRvin', 'number number', '1234312312', 'number@gmail.com', 'asdasdasdasdsa', '2025-05-05 03:45:36', 'pending'),
(40, 19, 'Becca', 'https://www.facebook.com/JhalRvin', 'Rebecca Faustino Larios', '09667504783', 'lariosbecca@gmail.com', 'asdsadsadsadsdsa', '2025-05-05 03:46:09', 'pending'),
(41, 15, 'number', 'https://www.facebook.com/JhalRvin', 'number number', '1234312312', 'number@gmail.com', 'asdadsadasd', '2025-05-05 03:46:47', 'pending'),
(42, 19, 'melvin', 'https://www.facebook.com/JhalRvin', 'Melvin Larios', '09667504783', 'melvin@gmail.com', 'asdadsadsadasd', '2025-05-05 03:48:19', 'pending'),
(43, 19, 'test', 'https://www.facebook.com/JhalRvin', 'Testing Testing', '123123213', 'test@gmail.com', 'adsadasdsadasdsad', '2025-05-05 03:48:47', 'pending'),
(44, 18, 'number', 'https://www.facebook.com/JhalRvin', 'number number', '1234312312', 'number@gmail.com', 'asdadsdasdsadasdsa', '2025-05-05 03:52:31', 'accepted'),
(45, 17, 'number', 'https://www.facebook.com/JhalRvin', 'number number', '1234312312', 'number@gmail.com', 'asdsadasdasd', '2025-05-05 03:57:13', 'pending'),
(46, 16, 'number', 'https://www.facebook.com/JhalRvin', 'number number', '1234312312', 'number@gmail.com', 'adsadasdsad', '2025-05-05 04:00:37', 'pending'),
(47, 20, 'melvin', 'https://www.facebook.com/JhalRvin', 'Melvin Larios', '09667504783', 'melvin@gmail.com', 'asdsadsadasdsadsa', '2025-05-05 04:04:23', 'pending'),
(48, 21, 'Becca', 'https://www.facebook.com/JhalRvin', 'Rebecca Faustino Larios', '09667504783', 'lariosbecca@gmail.com', 'sadasdsadasdasd', '2025-05-05 04:47:49', 'pending'),
(49, 17, 'Becca', 'https://www.facebook.com/JhalRvin', 'Rebecca Faustino Larios', '09667504783', 'lariosbecca@gmail.com', 'asdasdsad', '2025-05-05 08:04:08', 'pending'),
(50, 23, 'Jhave', 'https://www.facebook.com/JhalRvin', 'Jhal Rvin Larios', '09667504783', 'ljhave24@gmail.com', 'lorem ipsum dolor', '2025-05-05 08:53:00', 'accepted');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender` varchar(50) NOT NULL,
  `receiver` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender`, `receiver`, `message`, `timestamp`, `is_read`) VALUES
(16, 'Becca', 'John Rey', 'Hello', '2025-05-05 01:54:59', 1),
(17, 'John Rey', 'Becca', 'Testing', '2025-05-05 02:07:21', 1),
(18, 'John Rey', 'Becca', 'Testing again', '2025-05-05 02:07:56', 1),
(19, 'John Rey', 'Becca', '2', '2025-05-05 02:07:58', 1),
(20, 'John Rey', 'Becca', '3', '2025-05-05 02:07:59', 1),
(21, 'Becca', 'John Rey', 'Hello', '2025-05-05 02:11:09', 1),
(22, 'Becca', 'John Rey', '3', '2025-05-05 02:11:10', 1),
(23, 'Becca', 'John Rey', '2', '2025-05-05 02:11:11', 1),
(24, 'Becca', 'John Rey', '1', '2025-05-05 02:11:12', 1),
(25, 'John Rey', 'Becca', '1', '2025-05-05 02:12:24', 1),
(26, 'John Rey', 'Becca', '2', '2025-05-05 02:12:25', 1),
(27, 'John Rey', 'Becca', '3', '2025-05-05 02:12:26', 1),
(28, 'John Rey', 'Becca', '3', '2025-05-05 02:15:26', 1),
(29, 'John Rey', 'Becca', '2', '2025-05-05 02:15:28', 1),
(30, 'John Rey', 'Becca', '1', '2025-05-05 02:15:28', 1),
(31, 'employer', 'Jhave', 'Congratulations you are accepted to this job. \r\n\r\nI will send you link for a meeting interview', '2025-05-05 03:29:56', 1),
(32, 'employer', 'Renz', 'Congratulations \r\n\r\nlorem ipsum', '2025-05-05 03:37:19', 1),
(33, 'employer', 'Jhave', 'hello', '2025-05-05 03:47:12', 1),
(34, 'Jhave', 'employer', 'hiii', '2025-05-05 03:47:34', 1),
(35, 'employer', 'number', 'lorem ipsum congrats blah blah', '2025-05-05 03:52:57', 1),
(36, 'Becca', 'Reizsa', 'hello testing', '2025-05-05 04:48:01', 1),
(37, 'employer', 'Becca', 'hello', '2025-05-05 04:53:23', 1),
(38, 'melvin', 'Becca', 'hello babygurl', '2025-05-05 04:54:15', 1),
(39, 'employer', 'Becca', 'testing', '2025-05-05 05:00:42', 1),
(40, 'Becca', 'employer', 'hiii', '2025-05-05 05:26:19', 1),
(41, 'Renz', 'employer', 'hiii', '2025-05-05 05:26:28', 1),
(42, 'employer', 'Renz', 'Hello there', '2025-05-05 07:16:40', 1),
(43, 'employer', 'Renz', 'testing', '2025-05-05 07:37:33', 1),
(44, 'Renz', 'employer', 'testing if it works', '2025-05-05 07:53:14', 1),
(45, 'employer', 'Renz', 'edi wow', '2025-05-05 07:58:09', 0),
(46, 'Becca', 'employer', 'hello', '2025-05-05 08:03:52', 1),
(47, 'employer', 'John Rey', 'testing', '2025-05-05 08:10:03', 1),
(48, 'John Rey', 'employer', 'test', '2025-05-05 08:11:53', 1),
(49, 'John Rey', 'employer', 'test', '2025-05-05 08:11:55', 1),
(50, 'John Rey', 'employer', 'try', '2025-05-05 08:14:15', 1),
(51, 'John Rey', 'employer', 'try', '2025-05-05 08:14:18', 1),
(52, 'John Rey', 'employer', 'hello', '2025-05-05 08:14:31', 1),
(53, 'John Rey', 'employer', 'very good', '2025-05-05 08:14:35', 1),
(54, 'employer', 'John Rey', 'try', '2025-05-05 08:16:06', 1),
(55, 'employer', 'John Rey', 'test', '2025-05-05 08:16:07', 1),
(56, 'employer', 'John Rey', '123', '2025-05-05 08:16:08', 1),
(57, 'John Rey', 'employer', 'test', '2025-05-05 08:16:28', 1),
(58, 'John Rey', 'employer', 'test', '2025-05-05 08:16:29', 1),
(59, 'John Rey', 'employer', 'tes', '2025-05-05 08:16:30', 1),
(60, 'employer', 'John Rey', 'test', '2025-05-05 08:19:16', 1),
(61, 'employer', 'John Rey', 'test', '2025-05-05 08:19:17', 1),
(62, 'employer', 'John Rey', 'test', '2025-05-05 08:19:18', 1),
(63, 'John Rey', 'employer', 'test', '2025-05-05 08:19:38', 1),
(64, 'John Rey', 'employer', 'test', '2025-05-05 08:20:49', 1),
(65, 'John Rey', 'employer', 'test', '2025-05-05 08:20:50', 1),
(66, 'John Rey', 'employer', 'test', '2025-05-05 08:20:51', 1),
(67, 'employer', 'John Rey', 'tes', '2025-05-05 08:22:38', 1),
(68, 'employer', 'John Rey', 'test', '2025-05-05 08:24:12', 1),
(69, 'employer', 'John Rey', 'test', '2025-05-05 08:24:13', 1),
(70, 'John Rey', 'employer', 'test', '2025-05-05 08:24:34', 0),
(71, 'catdog', 'Jhave', 'Congratulations, welcome to cat dog', '2025-05-05 08:53:37', 1),
(72, 'Jhave', 'catdog', 'Thank you so much', '2025-05-05 08:53:59', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_username` varchar(50) DEFAULT NULL,
  `sender_username` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `type` enum('job_post','connection_request') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `job_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_username`, `sender_username`, `message`, `is_read`, `created_at`, `type`, `timestamp`, `job_id`) VALUES
(28, 'John Rey', 'Jhave', 'Jhave sent you a connection request.', 1, '2025-05-05 09:14:21', 'job_post', '2025-05-05 09:14:21', NULL),
(29, 'John Rey', 'Becca', 'Becca sent you a connection request', 1, '2025-05-05 09:15:42', 'job_post', '2025-05-05 09:15:42', NULL),
(30, 'employer', 'John Rey', 'John Rey sent you a connection request', 1, '2025-05-05 09:16:20', 'job_post', '2025-05-05 09:16:20', NULL),
(31, 'employer', 'Becca', 'Becca sent you a connection request', 1, '2025-05-05 09:16:38', 'job_post', '2025-05-05 09:16:38', NULL),
(32, 'employer', NULL, 'Rebecca Faustino Larios applied for your job post: Tripi tropi', 1, '2025-05-05 09:17:01', 'job_post', '2025-05-05 09:17:01', 18),
(33, 'employer', 'melvin', 'melvin sent you a connection request', 1, '2025-05-05 09:22:35', 'job_post', '2025-05-05 09:22:35', NULL),
(34, 'employer', NULL, 'Jhal Rvin Larios applied for your job post: Tripi tropi', 1, '2025-05-05 09:28:04', 'job_post', '2025-05-05 09:28:04', 18),
(35, 'employer', 'Jhave', 'Jhave sent you a connection request', 1, '2025-05-05 09:28:08', 'job_post', '2025-05-05 09:28:08', NULL),
(36, 'employer', 'Renz', 'Renz sent you a connection request', 1, '2025-05-05 09:31:11', 'job_post', '2025-05-05 09:31:11', NULL),
(37, 'employer', 'test', 'test sent you a connection request', 1, '2025-05-05 09:33:26', 'job_post', '2025-05-05 09:33:26', NULL),
(38, 'number', 'melvin', 'melvin sent you a connection request', 1, '2025-05-05 09:36:42', 'job_post', '2025-05-05 09:36:42', NULL),
(39, 'Jhave', 'Becca', 'Becca sent you a connection request', 1, '2025-05-05 09:47:42', 'job_post', '2025-05-05 09:47:42', NULL),
(40, 'Becca', 'Jhave', 'Jhave accepted your connection request', 1, '2025-05-05 09:47:50', 'job_post', '2025-05-05 09:47:50', NULL),
(41, 'John Rey', NULL, 'Jhal Rvin Larios applied for your job post: Web Developer', 1, '2025-05-05 10:16:56', 'job_post', '2025-05-05 10:16:56', 19),
(42, 'employer', NULL, 'Jhal Rvin Larios applied for your job post: Tralalelo Tralala', 1, '2025-05-05 10:27:40', 'job_post', '2025-05-05 10:27:40', 17),
(43, 'Jhave', 'employer', 'You have been accepted for the job: Tralalelo Tralala', 1, '2025-05-05 04:27:51', '', '2025-05-05 10:27:51', 17),
(44, 'Becca', 'employer', 'You have been rejected for the job: Tripi tropi', 1, '2025-05-05 05:06:24', '', '2025-05-05 11:06:24', 18),
(45, 'John Rey', NULL, 'Renz Capitan applied for your job post: Web Developer', 1, '2025-05-05 11:06:42', 'job_post', '2025-05-05 11:06:42', 19),
(46, 'Renz', 'John Rey', 'You have been accepted for the job: Web Developer', 1, '2025-05-05 05:07:12', '', '2025-05-05 11:07:12', 19),
(47, 'employer', NULL, 'Renz Capitan applied for your job post: Tripi tropi', 1, '2025-05-05 11:36:18', 'job_post', '2025-05-05 11:36:18', 18),
(48, 'Renz', NULL, 'You have been accepted for the job: Tripi tropi', 1, '2025-05-05 11:37:19', '', '2025-05-05 05:37:19', 18),
(49, 'Renz', 'employer', 'employer accepted your connection request', 1, '2025-05-05 11:38:04', 'job_post', '2025-05-05 11:38:04', NULL),
(50, 'John Rey', NULL, 'number number applied for your job post: Web Developer', 1, '2025-05-05 11:45:36', 'job_post', '2025-05-05 11:45:36', 19),
(51, 'John Rey', NULL, 'Rebecca Faustino Larios applied for your job post: Web Developer', 1, '2025-05-05 11:46:09', 'job_post', '2025-05-05 11:46:09', 19),
(52, 'employer', NULL, 'number number applied for your job post: Hotdog Seller', 1, '2025-05-05 11:46:47', 'job_post', '2025-05-05 11:46:47', 15),
(53, 'John Rey', NULL, 'Melvin Larios applied for your job post: Web Developer', 1, '2025-05-05 11:48:19', 'job_post', '2025-05-05 11:48:19', 19),
(54, 'John Rey', NULL, 'Testing Testing applied for your job post: Web Developer', 1, '2025-05-05 11:48:47', 'job_post', '2025-05-05 11:48:47', 19),
(55, 'employer', NULL, 'number number applied for your job post: Tripi tropi', 1, '2025-05-05 11:52:31', 'job_post', '2025-05-05 11:52:31', 18),
(56, 'number', NULL, 'You have been accepted for the job: Tripi tropi', 1, '2025-05-05 11:52:57', '', '2025-05-05 05:52:57', 18),
(57, 'employer', NULL, 'number number applied for your job post: Tralalelo Tralala', 1, '2025-05-05 11:57:13', 'job_post', '2025-05-05 11:57:13', 17),
(58, 'number', NULL, 'You are now connected to employer.', 1, '2025-05-05 11:57:13', 'job_post', '2025-05-05 11:57:13', NULL),
(59, 'employer', NULL, 'number number applied for your job post: Web Developer', 1, '2025-05-05 12:00:37', 'job_post', '2025-05-05 12:00:37', 16),
(60, 'Reizsa', NULL, 'Melvin Larios applied for your job post: Barbie doll', 1, '2025-05-05 12:04:23', 'job_post', '2025-05-05 12:04:23', 20),
(61, 'melvin', NULL, 'You are now connected to Reizsa.', 1, '2025-05-05 12:04:23', 'job_post', '2025-05-05 12:04:23', NULL),
(62, 'Reizsa', NULL, 'Rebecca Faustino Larios applied for your job post: Web Analyst', 1, '2025-05-05 12:47:49', 'job_post', '2025-05-05 12:47:49', 21),
(63, 'Becca', NULL, 'You are now connected to Reizsa.', 1, '2025-05-05 12:47:49', 'job_post', '2025-05-05 12:47:49', NULL),
(64, 'Becca', 'melvin', 'melvin sent you a connection request', 1, '2025-05-05 12:53:43', 'job_post', '2025-05-05 12:53:43', NULL),
(65, 'melvin', 'Becca', 'Becca accepted your connection request', 0, '2025-05-05 12:53:54', 'job_post', '2025-05-05 12:53:54', NULL),
(66, 'Renz', 'Becca', 'Becca sent you a connection request', 1, '2025-05-05 14:24:15', 'job_post', '2025-05-05 14:24:15', NULL),
(67, 'Renz', 'Jhave', 'Jhave sent you a connection request', 1, '2025-05-05 16:02:58', 'job_post', '2025-05-05 16:02:58', NULL),
(68, 'Jhave', 'Renz', 'Renz accepted your connection request', 1, '2025-05-05 16:03:09', 'job_post', '2025-05-05 16:03:09', NULL),
(69, 'employer', NULL, 'Rebecca Faustino Larios applied for your job post: Tralalelo Tralala', 1, '2025-05-05 16:04:08', 'job_post', '2025-05-05 16:04:08', 17),
(70, 'Jhave', 'catdog', 'catdog sent you a connection request', 1, '2025-05-05 16:52:09', 'job_post', '2025-05-05 16:52:09', NULL),
(71, 'catdog', NULL, 'Jhal Rvin Larios applied for your job post: Cat Dog Analyst', 1, '2025-05-05 16:53:00', 'job_post', '2025-05-05 16:53:00', 23),
(72, 'Jhave', NULL, 'You have been accepted for the job: Cat Dog Analyst', 1, '2025-05-05 16:53:37', '', '2025-05-05 10:53:37', 23);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('job_seeker','job_employer') NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `fullname`, `bio`, `location`, `company`, `phone`) VALUES
(1, 'Jhave', 'ljhave24@gmail.com', '$2y$10$nEJtQVrFsOz3gz1ObqSsDeYrJxvvRVg7ITOmDgJ6o4cJ0tgD93Zp.', 'job_seeker', 'Jhal Rvin Larios', 'Di marunong magcode', 'Bay Laguna', NULL, '09667504783'),
(3, 'John Rey', 'lariosjave20@yahoo.com', '$2y$10$2FiNM6Il.1I5tGMFBBlrzeATHn0evXuWZv0LQZZj5Z7PkhsbPrvDu', 'job_employer', 'John Rey Carag', 'lorem ipsum', 'Sta Cruz Laguna', 'CMDI ICT', NULL),
(4, 'Renz', 'renz@gmail.com', '$2y$10$pQc5UFubttxIBaL/1QoIBeEdFSEnHH5Fw/dqjVAZH.Rr0hQO4/udq', 'job_seeker', 'Renz Capitan', 'Tall Dark and Handsome', 'Los Baños, Laguna', NULL, '09667504783'),
(5, 'Reizsa', 'reizsa@gmail.com', '$2y$10$3UyTuCme8naDO90z7/wQ0.LX/Yo8tPRpcOJbNeAxAVV73UnXit7Qi', 'job_employer', 'Reigne Zsanella Larios', 'babygirl ni renz', 'Los Baños, Laguna', NULL, NULL),
(6, 'Becca', 'lariosbecca@gmail.com', '$2y$10$mmJ6LIMNtnbRYCUeBsawHOPvPea0.ljiNNngcqliKspqgyKCbuXw.', 'job_seeker', 'Rebecca Faustino Larios', 'Mama ko yan', 'Bay Laguna', NULL, '09667504783'),
(7, 'melvinppogi', 'melvin@gmail.com', '$2y$10$nKxsl/hoHiKEYw8NC2qAXuonBXuqJ70ZLGx2RIuTb7xEOw//EKGUi', 'job_seeker', 'Melvin Larios', NULL, NULL, NULL, '09667504783'),
(8, 'melvin', 'melvin@gmail.com', '$2y$10$2kY6Ybr4JVuNnNZSGxorfuh9BiL2WJ3wMtD2fe6Vkyq2to6TeN09u', 'job_seeker', 'Melvin Larios', NULL, NULL, NULL, '09667504783'),
(9, 'testing', 'testing@gmail.com', '$2y$10$aIbXH0JeVAiNpkrG2XXa.O0nrv7W49I9X5Ng8PF3LXY.GdwKyI.ti', 'job_seeker', 'Testing Testing', NULL, NULL, NULL, '0908403456'),
(10, 'test', 'test@gmail.com', '$2y$10$p/R6y8Qy4zdm/WKjE4qKguha2l7g31mGXBQcBvgRVZuXI5Pf9HDBG', 'job_seeker', 'Testing Testing', 'lorem ipsum', 'Bay Laguna', NULL, '123123213'),
(11, 'employer', 'employer@gmail.com', '$2y$10$ZHPcS4aUkM6VabGh2DFUCOoeopQ.vaQXle4X.AWSIg607TICuScAa', 'job_employer', 'employer', 'lorem ipsum', 'Florida USA', 'Edi wow corporation', '1231321321'),
(12, 'number', 'number@gmail.com', '$2y$10$VS.TkUZcX.J/Lko3b/7lveIs3Vy.cGvBwQmrzP7CKFUBRWBWiFbQG', 'job_seeker', 'number number', NULL, NULL, NULL, '1234312312'),
(13, 'johnrey', 'jr@gmail.com', '$2y$10$Z3xWizmP8SW8DscrPSXRoObp0izZ1/3QaAdjBDUh7D/OdbnU0tEJO', 'job_seeker', 'John Rey 2', NULL, NULL, NULL, '12312323'),
(14, 'catdog', 'catdog@gmail.com', '$2y$10$wkFem9pB8/HV/gVKkPW.WurRWZ61m5hmfJT2wuIY3OcI0sqvyWVB6', 'job_employer', 'Cat Dog', 'lorem ipsum', 'Bay Laguna', 'Cat Dog Corp.', '12345678');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `connections`
--
ALTER TABLE `connections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_applications_ibfk_1` (`job_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `connections`
--
ALTER TABLE `connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
