-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 07:16 PM
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
  `status` enum('pending','accepted') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `accepted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `connections`
--

INSERT INTO `connections` (`id`, `user1`, `user2`, `status`, `created_at`, `accepted_at`) VALUES
(56, 'JaneDoe', 'RonJayson', 'accepted', '2025-06-04 15:18:50', '2025-06-04 15:18:50'),
(57, 'JohnDoe', 'RonJayson', 'accepted', '2025-06-04 16:48:57', '2025-06-04 16:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `employer_username` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'active',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `job_type` varchar(100) DEFAULT NULL,
  `salary` varchar(100) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `description`, `employer_username`, `timestamp`, `status`, `is_featured`, `location`, `company`, `job_type`, `salary`, `rejection_reason`, `updated_at`) VALUES
(26, 'Service Crew', 'We are looking for enthusiastic, dedicated, and customer-focused individuals to join our team as Service Crew Members. As a Service Crew Member, you will be responsible for a variety of tasks, including interacting with customers, preparing food, maintaining restaurant cleanliness, and working collaboratively with team members to ensure smooth and efficient operations. This role, is fast-paced and requires a positive attitude and a willingness to learn.', 'RonJayson', '2025-06-04 14:44:21', 'active', 0, 'San Pablo City', 'Mcdonalds', 'Part-time', '$10.96', NULL, '2025-06-04 16:05:11'),
(31, 'Restaurant Manager', 'We are seeking a dynamic, results-oriented, and experienced leader to join our management team at McDonald\'s Los Baños. The [Selected Managerial Title] will be responsible for overseeing daily restaurant operations, ensuring outstanding customer service, managing and developing a high-performing team, optimizing profitability, and maintaining McDonald\'s high standards of quality, service, and cleanliness. This role requires strong leadership, excellent communication skills, and a passion for the food service industry.', 'RonJayson', '2025-06-04 17:12:10', 'active', 0, 'San Pablo City', 'Mcdonalds', 'Full-time', '$58,000 per year', NULL, '2025-06-04 17:12:31');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `applicant_username` varchar(255) NOT NULL,
  `resume_url` varchar(255) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `employer_feedback` text DEFAULT NULL,
  `applicant_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`id`, `job_id`, `applicant_username`, `resume_url`, `cover_letter`, `application_date`, `status`, `employer_feedback`, `applicant_feedback`) VALUES
(55, 26, 'JaneDoe', 'https://drive.google.com/file/d/10FSb01HBiNWyzVGTKJUW2VTmqK5UnTnu/view', 'I want to try to apply for the experience', '2025-06-04 15:18:50', 'accepted', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_username` varchar(50) DEFAULT NULL,
  `recipient_username` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `job_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_username`, `recipient_username`, `message`, `timestamp`, `is_read`, `job_id`) VALUES
(77, 'JaneDoe', 'RonJayson', 'Hello. I would like to apply for the job', '2025-06-04 15:19:26', 1, NULL),
(78, 'RonJayson', 'JaneDoe', 'I\'ll look in to your application', '2025-06-04 15:20:11', 1, NULL),
(79, 'RonJayson', 'JaneDoe', 'Congratulations! You are hired', '2025-06-04 15:23:57', 1, 26);

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
  `type` enum('job_post','connection_request','job_acceptance','application_rejected','job_application','connection_accepted','job_approved') NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_username`, `sender_username`, `message`, `is_read`, `created_at`, `type`, `job_id`, `application_id`) VALUES
(12, 'RonJayson', 'JaneDoe', 'Jane Doe applied for your job post: Service Crew', 1, '2025-06-04 23:18:50', 'job_application', 26, NULL),
(13, 'JaneDoe', 'RonJayson', 'You are now connected with RonJayson.', 1, '2025-06-04 23:18:50', 'connection_accepted', NULL, NULL),
(14, 'JaneDoe', 'RonJayson', 'Congratulations! You have been accepted for the job: Service Crew. The employer sent you a message.', 1, '2025-06-04 23:23:57', 'job_acceptance', 26, NULL),
(19, 'JohnDoe', 'RonJayson', 'You are now connected with RonJayson.', 1, '2025-06-05 00:48:57', 'connection_accepted', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_content`
--

CREATE TABLE `site_content` (
  `id` int(11) NOT NULL,
  `page_key` varchar(100) NOT NULL,
  `page_title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `last_updated_by` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_content`
--

INSERT INTO `site_content` (`id`, `page_key`, `page_title`, `content`, `last_updated_by`, `updated_at`) VALUES
(1, 'terms-of-service', 'Terms of Service', '<h1 style=\"font-size: 2em; margin-bottom: 0.5em; color: #333;\">Terms of Service</h1>\r\n\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Welcome to CareerLynk! These Terms of Service (\"Terms\") govern your use of our job portal services, including our website, mobile applications, and any related services (collectively, the \"Services\"). By accessing or using our Services, you agree to be bound by these Terms. If you do not agree to these Terms, please do not use our Services.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">1. Acceptance of Terms</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">By creating an account, applying for jobs, posting jobs, or otherwise using the Services, you confirm that you are at least 18 years old and legally capable of entering into a binding contract, or, if you are under 18, you have obtained parental or guardian consent to use the Services and they agree to these Terms on your behalf.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">2. User Accounts</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">To access certain features of our Services, you may need to create an account. You agree to:</p>\r\n    <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n        <li style=\"margin-bottom: 0.5em;\">Provide accurate, current, and complete information during the registration process.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Maintain and promptly update your account information.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Maintain the security of your password and accept all risks of unauthorized access to your account.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Notify us immediately if you discover or otherwise suspect any security breaches related to the Services or your account.</li>\r\n    </ul>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">You are responsible for all activities that occur under your account.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">3. Use of Services</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">You agree to use the Services only for lawful purposes and in accordance with these Terms. You agree not to use the Services:</p>\r\n    <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n        <li style=\"margin-bottom: 0.5em;\">In any way that violates any applicable national or international law or regulation.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">For the purpose of exploiting, harming, or attempting to exploit or harm minors in any way.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To transmit, or procure the sending of, any advertising or promotional material, including any \"junk mail,\" \"chain letter,\" \"spam,\" or any other similar solicitation.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To impersonate or attempt to impersonate CareerLynk, a CareerLynk employee, another user, or any other person or entity.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To engage in any other conduct that restricts or inhibits anyone\'s use or enjoyment of the Services, or which, as determined by us, may harm CareerLynk or users of the Services or expose them to liability.</li>\r\n    </ul>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">4. Job Postings and Applications</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\"><strong>For Employers:</strong> You are responsible for the accuracy, content, and legality of the job postings you submit. You agree not to post jobs that:\r\n        <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n            <li style=\"margin-bottom: 0.5em;\">Are misleading, fraudulent, or deceptive.</li>\r\n            <li style=\"margin-bottom: 0.5em;\">Violate any applicable laws, including but not limited to labor and employment, equal opportunity, and anti-discrimination laws.</li>\r\n            <li style=\"margin-bottom: 0.5em;\">Require applicants to pay fees or make purchases as a condition of employment.</li>\r\n            <li style=\"margin-bottom: 0.5em;\">Contain any material that is defamatory, obscene, indecent, abusive, offensive, harassing, violent, hateful, inflammatory, or otherwise objectionable.</li>\r\n        </ul>\r\n    </p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\"><strong>For Job Seekers:</strong> You acknowledge that CareerLynk does not verify the accuracy or legitimacy of job postings. You are responsible for conducting your own due diligence before applying for any job or sharing personal information. CareerLynk is not responsible for any employment decisions made by employers using the Services.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">5. Intellectual Property Rights</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">The Services and their entire contents, features, and functionality (including but not limited to all information, software, text, displays, images, video, and audio, and the design, selection, and arrangement thereof) are owned by CareerLynk, its licensors, or other providers of such material and are protected by copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">You are permitted to use the Services for your personal, non-commercial use, or legitimate business purposes related to seeking employment or recruiting candidates. You must not reproduce, distribute, modify, create derivative works of, publicly display, publicly perform, republish, download, store, or transmit any of the material on our Services, except as generally and ordinarily permitted through the Services according to these Terms.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">6. User Content</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">You may be able to submit, post, or share content such as your resume, profile information, messages, and job applications (\"User Content\"). You retain ownership of your User Content, but you grant CareerLynk a worldwide, non-exclusive, royalty-free, sublicensable, and transferable license to use, reproduce, distribute, prepare derivative works of, display, and perform your User Content in connection with the Services and CareerLynk\'s (and its successors\' and affiliates\') business, including for promoting and redistributing part or all of the Services.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">You represent and warrant that you own or control all rights in and to the User Content and have the right to grant the license granted above to us.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">7. Disclaimers</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">THE SERVICES ARE PROVIDED ON AN \"AS IS\" AND \"AS AVAILABLE\" BASIS, WITHOUT ANY WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED. NEITHER CAREERLYNK NOR ANY PERSON ASSOCIATED WITH CAREERLYNK MAKES ANY WARRANTY OR REPRESENTATION WITH RESPECT TO THE COMPLETENESS, SECURITY, RELIABILITY, QUALITY, ACCURACY, OR AVAILABILITY OF THE SERVICES.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">WE DO NOT WARRANT THAT THE SERVICES WILL BE ERROR-FREE OR UNINTERRUPTED, THAT DEFECTS WILL BE CORRECTED, OR THAT OUR SITE OR THE SERVER THAT MAKES IT AVAILABLE ARE FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">8. Limitation of Liability</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">TO THE FULLEST EXTENT PERMITTED BY LAW, IN NO EVENT WILL CAREERLYNK, ITS AFFILIATES, OR THEIR LICENSORS, SERVICE PROVIDERS, EMPLOYEES, AGENTS, OFFICERS, OR DIRECTORS BE LIABLE FOR DAMAGES OF ANY KIND, UNDER ANY LEGAL THEORY, ARISING OUT OF OR IN CONNECTION WITH YOUR USE, OR INABILITY TO USE, THE SERVICES, ANY WEBSITES LINKED TO IT, ANY CONTENT ON THE SERVICES OR SUCH OTHER WEBSITES, INCLUDING ANY DIRECT, INDIRECT, SPECIAL, INCIDENTAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING BUT NOT LIMITED TO, PERSONAL INJURY, PAIN AND SUFFERING, EMOTIONAL DISTRESS, LOSS OF REVENUE, LOSS OF PROFITS, LOSS OF BUSINESS OR ANTICIPATED SAVINGS, LOSS OF USE, LOSS OF GOODWILL, LOSS OF DATA, AND WHETHER CAUSED BY TORT (INCLUDING NEGLIGENCE), BREACH OF CONTRACT, OR OTHERWISE, EVEN IF FORESEEABLE.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">9. Indemnification</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">You agree to defend, indemnify, and hold harmless CareerLynk, its affiliates, licensors, and service providers, and its and their respective officers, directors, employees, contractors, agents, licensors, suppliers, successors, and assigns from and against any claims, liabilities, damages, judgments, awards, losses, costs, expenses, or fees (including reasonable attorneys\' fees) arising out of or relating to your violation of these Terms or your use of the Services, including, but not limited to, your User Content, any use of the Service\'s content, services, and products other than as expressly authorized in these Terms.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">10. Termination</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We may terminate or suspend your access to all or part of the Services, without prior notice or liability, for any reason or no reason, including if you breach these Terms. Upon termination, your right to use the Services will immediately cease.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">11. Governing Law and Jurisdiction</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">All matters relating to the Services and these Terms, and any dispute or claim arising therefrom or related thereto (in each case, including non-contractual disputes or claims), shall be governed by and construed in accordance with the internal laws of [Your Jurisdiction/Country, e.g., the State of California] without giving effect to any choice or conflict of law provision or rule.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Any legal suit, action, or proceeding arising out of, or related to, these Terms or the Services shall be instituted exclusively in the federal courts of [Your Jurisdiction/Country] or the courts of the [State/Region of Your Jurisdiction].</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">12. Changes to Terms</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days\' notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">By continuing to access or use our Services after any revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, you are no longer authorized to use the Services.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">13. Contact Information</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">If you have any questions about these Terms, please contact us at [Your Contact Email/Link to Contact Page].</p>', 'admin', '2025-06-03 16:42:35'),
(2, 'privacy-policy', 'Privacy Policy', '<h1 style=\"font-size: 2em; margin-bottom: 0.5em; color: #333;\">Privacy Policy</h1>\r\n\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">CareerLynk (\"us\", \"we\", or \"our\") operates the CareerLynk job portal (the \"Service\"). This page informs you of our policies regarding the collection, use, and disclosure of personal data when you use our Service and the choices you have associated with that data.</p>\r\n\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We use your data to provide and improve the Service. By using the Service, you agree to the collection and use of information in accordance with this policy. Unless otherwise defined in this Privacy Policy, terms used in this Privacy Policy have the same meanings as in our Terms of Service.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">1. Information Collection and Use</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We collect several different types of information for various purposes to provide and improve our Service to you.</p>\r\n\r\n    <h3 style=\"font-size: 1.25em; margin-top: 0.75em; margin-bottom: 0.25em; color: #555;\">Types of Data Collected</h3>\r\n    <h4 style=\"font-size: 1.1em; margin-top: 0.5em; margin-bottom: 0.25em; color: #555;\">Personal Data</h4>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">While using our Service, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you (\"Personal Data\"). Personally identifiable information may include, but is not limited to:</p>\r\n    <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n        <li style=\"margin-bottom: 0.5em;\">Email address</li>\r\n        <li style=\"margin-bottom: 0.5em;\">First name and last name</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Phone number</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Address, State, Province, ZIP/Postal code, City</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Resume/CV and cover letter information</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Employment history and education details</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Profile picture</li>\r\n        <li style=\"margin-bottom: 0.5em;\">Cookies and Usage Data</li>\r\n    </ul>\r\n\r\n    <h4 style=\"font-size: 1.1em; margin-top: 0.5em; margin-bottom: 0.25em; color: #555;\">Usage Data</h4>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We may also collect information on how the Service is accessed and used (\"Usage Data\"). This Usage Data may include information such as your computer\'s Internet Protocol address (e.g. IP address), browser type, browser version, the pages of our Service that you visit, the time and date of your visit, the time spent on those pages, unique device identifiers and other diagnostic data.</p>\r\n\r\n    <h4 style=\"font-size: 1.1em; margin-top: 0.5em; margin-bottom: 0.25em; color: #555;\">Tracking & Cookies Data</h4>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We use cookies and similar tracking technologies to track the activity on our Service and hold certain information.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Cookies are files with a small amount of data which may include an anonymous unique identifier. Cookies are sent to your browser from a website and stored on your device. Tracking technologies also used are beacons, tags, and scripts to collect and track information and to improve and analyze our Service.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our Service.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Examples of Cookies we use:</p>\r\n    <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n        <li style=\"margin-bottom: 0.5em;\"><strong>Session Cookies:</strong> We use Session Cookies to operate our Service.</li>\r\n        <li style=\"margin-bottom: 0.5em;\"><strong>Preference Cookies:</strong> We use Preference Cookies to remember your preferences and various settings.</li>\r\n        <li style=\"margin-bottom: 0.5em;\"><strong>Security Cookies:</strong> We use Security Cookies for security purposes.</li>\r\n    </ul>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">2. Use of Data</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">CareerLynk uses the collected data for various purposes:</p>\r\n    <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n        <li style=\"margin-bottom: 0.5em;\">To provide and maintain the Service</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To notify you about changes to our Service</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To allow you to participate in interactive features of our Service when you choose to do so</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To provide customer care and support</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To provide analysis or valuable information so that we can improve the Service</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To monitor the usage of the Service</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To detect, prevent and address technical issues</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To match job seekers with potential employers</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To facilitate communication between users (e.g., job seekers and employers)</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To personalize your experience on our Service</li>\r\n    </ul>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">3. Transfer of Data</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Your information, including Personal Data, may be transferred to — and maintained on — computers located outside of your state, province, country or other governmental jurisdiction where the data protection laws may differ from those from your jurisdiction.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">If you are located outside [Your Country] and choose to provide information to us, please note that we transfer the data, including Personal Data, to [Your Country] and process it there.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Your consent to this Privacy Policy followed by your submission of such information represents your agreement to that transfer.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">CareerLynk will take all steps reasonably necessary to ensure that your data is treated securely and in accordance with this Privacy Policy and no transfer of your Personal Data will take place to an organization or a country unless there are adequate controls in place including the security of your data and other personal information.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">4. Disclosure of Data</h2>\r\n    <h3 style=\"font-size: 1.25em; margin-top: 0.75em; margin-bottom: 0.25em; color: #555;\">Legal Requirements</h3>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">CareerLynk may disclose your Personal Data in the good faith belief that such action is necessary to:</p>\r\n    <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n        <li style=\"margin-bottom: 0.5em;\">To comply with a legal obligation</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To protect and defend the rights or property of CareerLynk</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To prevent or investigate possible wrongdoing in connection with the Service</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To protect the personal safety of users of the Service or the public</li>\r\n        <li style=\"margin-bottom: 0.5em;\">To protect against legal liability</li>\r\n    </ul>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">5. Security of Data</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">The security of your data is important to us, but remember that no method of transmission over the Internet, or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">6. Service Providers</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We may employ third party companies and individuals to facilitate our Service (\"Service Providers\"), to provide the Service on our behalf, to perform Service-related services or to assist us in analyzing how our Service is used.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">These third parties have access to your Personal Data only to perform these tasks on our behalf and are obligated not to disclose or use it for any other purpose.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">7. Links to Other Sites</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Our Service may contain links to other sites that are not operated by us. If you click on a third party link, you will be directed to that third party\'s site. We strongly advise you to review the Privacy Policy of every site you visit.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We have no control over and assume no responsibility for the content, privacy policies or practices of any third party sites or services.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">8. Children\'s Privacy</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Our Service does not address anyone under the age of 18 (\"Children\"). In the context of our platform, \"Children\" refers to individuals under the age where they can legally consent to the processing of their personal data, which may be higher than 13 in some jurisdictions (e.g., 16 in the EU under GDPR unless a member state provides for a lower age).</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We do not knowingly collect personally identifiable information from anyone under the age of 18. If you are a parent or guardian and you are aware that your Children has provided us with Personal Data, please contact us. If we become aware that we have collected Personal Data from children without verification of parental consent, we take steps to remove that information from our servers.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">9. Changes to This Privacy Policy</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">We will let you know via email and/or a prominent notice on our Service, prior to the change becoming effective and update the \"last updated\" date at the top of this Privacy Policy.</p>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">10. Your Data Protection Rights</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">Depending on your location and applicable laws, you may have certain data protection rights, including:</p>\r\n    <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n        <li style=\"margin-bottom: 0.5em;\">The right to access, update or delete the information we have on you.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">The right of rectification.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">The right to object.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">The right of restriction.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">The right to data portability.</li>\r\n        <li style=\"margin-bottom: 0.5em;\">The right to withdraw consent.</li>\r\n    </ul>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">If you wish to exercise any of these rights, please contact us.</p>\r\n\r\n    <h2 style=\"font-size: 1.5em; margin-top: 1em; margin-bottom: 0.5em; color: #555;\">11. Contact Us</h2>\r\n    <p style=\"margin-bottom: 1em; line-height: 1.6; color: #666;\">If you have any questions about this Privacy Policy, please contact us:</p>\r\n    <ul style=\"margin-bottom: 1em; line-height: 1.6; color: #666; list-style-position: inside; padding-left: 20px;\">\r\n        <li style=\"margin-bottom: 0.5em;\">By email: [Your Contact Email]</li>\r\n        <li style=\"margin-bottom: 0.5em;\">By visiting this page on our website: [Link to Your Contact Page]</li>\r\n    </ul>', 'admin', '2025-06-03 16:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('job_seeker','job_employer','admin') NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `contact_number`, `bio`, `profile_picture_url`, `created_at`, `updated_at`, `location`, `company`) VALUES
(19, 'admin', 'admin@example.com', '$2y$10$8r4XuIhn3aJDZeTK.sK6A./lzZkwMUBRhwjQaf6yGzRdEip1AJyjW', 'admin', 'Admin User', NULL, NULL, NULL, '2025-06-02 18:09:26', '2025-06-02 18:09:26', NULL, NULL),
(21, 'RonJayson', 'Ronjaysongallardo@gmail.com', '$2y$10$YytIuaGaDxu3mAnvfsM49eR9MlYaSD7KoTxjVYAmrQ72RO6Uq0im.', 'job_employer', 'Ron Jayson', '09287526331', 'Hello!', NULL, '2025-06-04 14:25:19', '2025-06-04 15:26:35', 'San Pablo City', 'Mcdonalds'),
(22, 'JaneDoe', 'Janedoe@gmail.com', '$2y$10$A/KIbEbkchFB2iioWTGY8OJGokDj0f8l/xHIkqLOBJH5ZFqit1KNu', 'job_seeker', 'Jane Doe', '09287526332', 'Hi!', NULL, '2025-06-04 15:01:11', '2025-06-04 15:25:48', 'San Pablo City', NULL),
(23, 'JohnDoe', 'Johndoe@gmail.com', '$2y$10$9lW57yjFTuQywss5rEZzg.jCbbvZT7TlsNerenYPEhf6DdnWZHCre', 'job_seeker', 'John Doe', '09287526333', NULL, NULL, '2025-06-04 16:48:26', '2025-06-04 16:48:26', NULL, NULL);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `user1` (`user1`),
  ADD KEY `user2` (`user2`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_username` (`employer_username`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `applicant_username` (`applicant_username`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_username` (`sender_username`),
  ADD KEY `recipient_username` (`recipient_username`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_username` (`recipient_username`),
  ADD KEY `sender_username` (`sender_username`),
  ADD KEY `type` (`type`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `site_content`
--
ALTER TABLE `site_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_key` (`page_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_content`
--
ALTER TABLE `site_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `connections`
--
ALTER TABLE `connections`
  ADD CONSTRAINT `connections_ibfk_1` FOREIGN KEY (`user1`) REFERENCES `users` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `connections_ibfk_2` FOREIGN KEY (`user2`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`applicant_username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_username`) REFERENCES `users` (`username`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`recipient_username`) REFERENCES `users` (`username`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_username`) REFERENCES `users` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_username`) REFERENCES `users` (`username`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
