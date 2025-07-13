-- BIMS Database Backup
-- Generated on: 2025-07-13 08:01:16
-- Database: bims_db

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


-- Table structure for table `audit_trail`
DROP TABLE IF EXISTS `audit_trail`;
CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`user_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_action` (`action`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_audit_trail_recent` (`timestamp`,`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `audit_trail`
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('1', '1', 'Profile Updated', '{\"old_username\":\"secretary\",\"new_username\":\"secretary\",\"old_first_name\":\"Barangay\",\"new_first_name\":\"Jennifer\",\"old_last_name\":\"Secretary\",\"new_last_name\":\"De Leon\",\"old_email\":\"secretary@barangay.local\",\"new_email\":\"gmmxxbiz@gmail.com\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 10:40:44', '2025-07-13 10:40:44');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('2', '1', 'Resident Added', '{\"resident_id\":1,\"resident_name\":\"James Ivan Deric Marcillan Dacles\",\"timestamp\":\"2025-07-13 04:43:41\",\"changes\":{\"gender\":\"Male\",\"purok_id\":2,\"birthdate\":\"2004-09-11\",\"civil_status\":\"Single\",\"is_pwd\":0,\"is_voter\":1,\"is_4ps\":0,\"is_solo_parent\":0,\"is_senior_citizen\":0}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 10:43:41', '2025-07-13 10:43:41');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('3', '1', 'Resident Deleted', '{\"resident_id\":1,\"resident_name\":\"James Ivan Deric Marcillan Dacles\",\"timestamp\":\"2025-07-13 04:44:35\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 10:44:35', '2025-07-13 10:44:35');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('4', '1', 'Resident Added', '{\"resident_id\":2,\"resident_name\":\"James Ivan Deric Marcillan Dacles\",\"timestamp\":\"2025-07-13 05:27:48\",\"changes\":{\"gender\":\"Male\",\"purok_id\":2,\"birthdate\":\"2004-09-11\",\"civil_status\":\"Single\",\"is_pwd\":0,\"is_voter\":1,\"is_4ps\":0,\"is_solo_parent\":0,\"is_senior_citizen\":0}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:27:48', '2025-07-13 11:27:48');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('5', '1', 'Resident Added', '{\"resident_id\":3,\"resident_name\":\"Estrellita Marcillan Dacles\",\"timestamp\":\"2025-07-13 05:28:32\",\"changes\":{\"gender\":\"Female\",\"purok_id\":2,\"birthdate\":\"1983-11-06\",\"civil_status\":\"Married\",\"is_pwd\":0,\"is_voter\":1,\"is_4ps\":0,\"is_solo_parent\":0,\"is_senior_citizen\":0}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:28:32', '2025-07-13 11:28:32');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('6', '1', 'Resident Added', '{\"resident_id\":4,\"resident_name\":\"Dennis De Jesus Dacles\",\"timestamp\":\"2025-07-13 05:29:12\",\"changes\":{\"gender\":\"Male\",\"purok_id\":2,\"birthdate\":\"1980-05-14\",\"civil_status\":\"Married\",\"is_pwd\":0,\"is_voter\":1,\"is_4ps\":0,\"is_solo_parent\":0,\"is_senior_citizen\":0}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:29:12', '2025-07-13 11:29:12');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('7', '1', 'Resident Updated', '{\"resident_id\":4,\"resident_name\":\"Dennis De Jesus Dacles\",\"timestamp\":\"2025-07-13 05:29:46\",\"changes\":{\"first_name\":\"Dennis\",\"middle_name\":\"De Jesus\",\"last_name\":\"Dacles\",\"suffix\":\"\",\"gender\":\"Male\",\"birthdate\":\"1980-05-14\",\"civil_status\":\"Married\",\"blood_type\":\"O+\",\"religion\":\"Roman Catholic\",\"purok_id\":2,\"is_pwd\":0,\"is_voter\":1,\"is_4ps\":0,\"is_pregnant\":0,\"is_solo_parent\":0,\"is_senior_citizen\":0,\"email\":\"daclesjamesivan.va@gmail.com\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:29:46', '2025-07-13 11:29:46');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('8', '1', 'Resident Added', '{\"resident_id\":5,\"resident_name\":\"Juan Cruz Dela Cruz\",\"timestamp\":\"2025-07-13 05:30:31\",\"changes\":{\"gender\":\"Male\",\"purok_id\":1,\"birthdate\":\"1965-01-19\",\"civil_status\":\"Separated\",\"is_pwd\":1,\"is_voter\":1,\"is_4ps\":1,\"is_solo_parent\":1,\"is_senior_citizen\":1}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:30:31', '2025-07-13 11:30:31');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('9', '1', 'Resident Updated', '{\"resident_id\":2,\"resident_name\":\"James Ivan Deric Marcillan Dacles\",\"timestamp\":\"2025-07-13 05:33:55\",\"changes\":{\"first_name\":\"James Ivan Deric\",\"middle_name\":\"Marcillan\",\"last_name\":\"Dacles\",\"suffix\":\"\",\"gender\":\"Male\",\"birthdate\":\"2004-09-11\",\"civil_status\":\"Single\",\"blood_type\":\"O+\",\"religion\":\"Roman Catholic\",\"purok_id\":2,\"is_pwd\":0,\"is_voter\":1,\"is_4ps\":0,\"is_pregnant\":0,\"is_solo_parent\":0,\"is_senior_citizen\":0,\"email\":\"gmmxxbiz@gmail.com\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:33:55', '2025-07-13 11:33:55');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('10', '1', 'Resident Deleted', '{\"resident_id\":5,\"resident_name\":\"Juan Cruz Dela Cruz III\",\"timestamp\":\"2025-07-13 05:34:18\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:34:18', '2025-07-13 11:34:18');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('11', '1', 'Resident Added', '{\"resident_id\":6,\"resident_name\":\"Juan Cruz Dela Cruz\",\"timestamp\":\"2025-07-13 05:35:02\",\"changes\":{\"gender\":\"Male\",\"purok_id\":3,\"birthdate\":\"1986-11-09\",\"civil_status\":\"Widowed\",\"is_pwd\":1,\"is_voter\":1,\"is_4ps\":1,\"is_solo_parent\":1,\"is_senior_citizen\":0}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:35:02', '2025-07-13 11:35:02');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('12', '1', 'Resident Updated', '{\"resident_id\":6,\"resident_name\":\"Juan Cruz Dela Cruz Jr.\",\"timestamp\":\"2025-07-13 05:35:45\",\"changes\":{\"first_name\":\"Juan\",\"middle_name\":\"Cruz\",\"last_name\":\"Dela Cruz\",\"suffix\":\"Jr.\",\"gender\":\"Male\",\"birthdate\":\"1986-11-09\",\"civil_status\":\"Widowed\",\"blood_type\":\"AB+\",\"religion\":\"Jehovah\'\'s Witness\",\"purok_id\":3,\"is_pwd\":1,\"is_voter\":1,\"is_4ps\":1,\"is_pregnant\":1,\"is_solo_parent\":1,\"is_senior_citizen\":0,\"email\":\"juandc@gmail.com\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:35:45', '2025-07-13 11:35:45');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('13', '1', 'Resident Updated', '{\"resident_id\":6,\"resident_name\":\"Juan Cruz Dela Cruz Jr.\",\"timestamp\":\"2025-07-13 05:36:14\",\"changes\":{\"first_name\":\"Juan\",\"middle_name\":\"Cruz\",\"last_name\":\"Dela Cruz\",\"suffix\":\"Jr.\",\"gender\":\"Male\",\"birthdate\":\"1986-11-09\",\"civil_status\":\"Widowed\",\"blood_type\":\"AB+\",\"religion\":\"Roman Catholic\",\"purok_id\":3,\"is_pwd\":1,\"is_voter\":1,\"is_4ps\":1,\"is_pregnant\":0,\"is_solo_parent\":1,\"is_senior_citizen\":0,\"email\":\"juandc@gmail.com\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:36:14', '2025-07-13 11:36:14');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('14', '1', 'Announcement Sent', '{\"subject\":\"Test123\",\"sent_count\":4,\"failed_count\":0,\"total_recipients\":4}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:46:20', '2025-07-13 11:46:20');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('15', '1', 'Failed Login Attempt', '{\"username\":\"secretary\",\"success\":false,\"timestamp\":\"2025-07-13 05:51:50\",\"session_id\":\"jusp9k8nk754g377ebput2ofi4\",\"reason\":\"invalid_credentials\",\"attempted_username\":\"secretary\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:51:50', '2025-07-13 11:51:50');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('16', '1', 'Failed Login Attempt', '{\"username\":\"secretary\",\"success\":false,\"timestamp\":\"2025-07-13 05:52:01\",\"session_id\":\"jusp9k8nk754g377ebput2ofi4\",\"reason\":\"invalid_credentials\",\"attempted_username\":\"secretary\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:52:01', '2025-07-13 11:52:01');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('17', '1', 'Failed Login Attempt', '{\"username\":\"secretary\",\"success\":false,\"timestamp\":\"2025-07-13 05:52:11\",\"session_id\":\"jusp9k8nk754g377ebput2ofi4\",\"reason\":\"invalid_credentials\",\"attempted_username\":\"secretary\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:52:11', '2025-07-13 11:52:11');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('18', '1', 'Password Reset Requested', 'OTP code requested for password reset via email: gmmxxbiz@gmail.com', '127.0.0.1', NULL, '2025-07-13 11:53:16', '2025-07-13 11:53:16');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('19', '1', 'Password Reset OTP Verification', '{\"action\":\"password_reset_otp_verification\",\"credential\":\"secretary\",\"result\":\"successful\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/138.0.0.0 Safari\\/537.36\",\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2025-07-13 11:53:31', '2025-07-13 11:53:31');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('20', '1', 'Password Reset Completed', '{\"action\":\"password_reset_completed\",\"username\":\"secretary\",\"email\":\"gmmxxbiz@gmail.com\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/138.0.0.0 Safari\\/537.36\",\"ip\":\"127.0.0.1\"}', '127.0.0.1', NULL, '2025-07-13 11:53:40', '2025-07-13 11:53:40');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('21', '1', 'User Login', '{\"username\":\"secretary\",\"success\":true,\"timestamp\":\"2025-07-13 05:53:46\",\"session_id\":\"jusp9k8nk754g377ebput2ofi4\",\"login_method\":\"username_password\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/138.0.0.0 Safari\\/537.36\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 11:53:46', '2025-07-13 11:53:46');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('22', '1', 'Official Added', '{\"official_id\":9,\"name\":\"Jennifer I. De Leon\",\"position\":\"Barangay Secretary\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 12:15:41', '2025-07-13 12:15:41');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('23', '1', 'Official Updated', '{\"official_id\":4,\"name\":\"Erlito A. Bacosta\",\"position\":\"Sangguniang Barangay Member\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 12:15:53', '2025-07-13 12:15:53');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('24', '1', 'Official Updated', '{\"official_id\":4,\"name\":\"Erlito A. Acosta\",\"position\":\"Sangguniang Barangay Member\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 12:15:59', '2025-07-13 12:15:59');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('25', '1', 'System Setting Changed', '{\"setting\":\"system_title\",\"old_value\":\"Previous Value\",\"new_value\":\"Digitals Identity and Certification Management System\",\"timestamp\":\"2025-07-13 07:49:38\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:38', '2025-07-13 13:49:38');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('26', '1', 'System Setting Changed', '{\"setting\":\"barangay_name\",\"old_value\":\"Previous Value\",\"new_value\":\"Sucol\",\"timestamp\":\"2025-07-13 07:49:38\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:38', '2025-07-13 13:49:38');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('27', '1', 'System Setting Changed', '{\"setting\":\"municipality\",\"old_value\":\"Previous Value\",\"new_value\":\"Calumpit\",\"timestamp\":\"2025-07-13 07:49:38\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:38', '2025-07-13 13:49:38');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('28', '1', 'System Setting Changed', '{\"setting\":\"province\",\"old_value\":\"Previous Value\",\"new_value\":\"Bulacan\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('29', '1', 'System Setting Changed', '{\"setting\":\"barangay_address\",\"old_value\":\"Previous Value\",\"new_value\":\"Sucol, Calumpit, Bulacan\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('30', '1', 'System Setting Changed', '{\"setting\":\"barangay_logo_path\",\"old_value\":\"Previous Value\",\"new_value\":\"img\\/logo.png\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('31', '1', 'System Setting Changed', '{\"setting\":\"records_per_page\",\"old_value\":\"Previous Value\",\"new_value\":\"25\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('32', '1', 'System Setting Changed', '{\"setting\":\"session_timeout\",\"old_value\":\"Previous Value\",\"new_value\":\"30\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('33', '1', 'System Setting Changed', '{\"setting\":\"primary_color\",\"old_value\":\"Previous Value\",\"new_value\":\"#2563eb\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('34', '1', 'System Setting Changed', '{\"setting\":\"dashboard_cards_enabled\",\"old_value\":\"Previous Value\",\"new_value\":\"total_population,male_residents,female_residents\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('35', '1', 'System Setting Changed', '{\"setting\":\"smtp_host\",\"old_value\":\"Previous Value\",\"new_value\":\"smtp.gmail.com\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('36', '1', 'System Setting Changed', '{\"setting\":\"smtp_port\",\"old_value\":\"Previous Value\",\"new_value\":\"587\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('37', '1', 'System Setting Changed', '{\"setting\":\"smtp_username\",\"old_value\":\"Previous Value\",\"new_value\":\"\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('38', '1', 'System Setting Changed', '{\"setting\":\"smtp_password\",\"old_value\":\"Previous Value\",\"new_value\":\"\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('39', '1', 'System Setting Changed', '{\"setting\":\"smtp_secure\",\"old_value\":\"Previous Value\",\"new_value\":\"tls\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('40', '1', 'System Setting Changed', '{\"setting\":\"smtp_from_email\",\"old_value\":\"Previous Value\",\"new_value\":\"noreply@barangay.local\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('41', '1', 'System Setting Changed', '{\"setting\":\"smtp_from_name\",\"old_value\":\"Previous Value\",\"new_value\":\"Barangay System\",\"timestamp\":\"2025-07-13 07:49:39\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:39', '2025-07-13 13:49:39');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('42', '1', 'System Setting Changed', '{\"setting\":\"system_title\",\"old_value\":\"Previous Value\",\"new_value\":\"Digital Identity and Certification Management System\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('43', '1', 'System Setting Changed', '{\"setting\":\"barangay_name\",\"old_value\":\"Previous Value\",\"new_value\":\"Sucol\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('44', '1', 'System Setting Changed', '{\"setting\":\"municipality\",\"old_value\":\"Previous Value\",\"new_value\":\"Calumpit\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('45', '1', 'System Setting Changed', '{\"setting\":\"province\",\"old_value\":\"Previous Value\",\"new_value\":\"Bulacan\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('46', '1', 'System Setting Changed', '{\"setting\":\"barangay_address\",\"old_value\":\"Previous Value\",\"new_value\":\"Sucol, Calumpit, Bulacan\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('47', '1', 'System Setting Changed', '{\"setting\":\"barangay_logo_path\",\"old_value\":\"Previous Value\",\"new_value\":\"img\\/logo.png\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('48', '1', 'System Setting Changed', '{\"setting\":\"records_per_page\",\"old_value\":\"Previous Value\",\"new_value\":\"25\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('49', '1', 'System Setting Changed', '{\"setting\":\"session_timeout\",\"old_value\":\"Previous Value\",\"new_value\":\"30\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('50', '1', 'System Setting Changed', '{\"setting\":\"primary_color\",\"old_value\":\"Previous Value\",\"new_value\":\"#2563eb\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('51', '1', 'System Setting Changed', '{\"setting\":\"dashboard_cards_enabled\",\"old_value\":\"Previous Value\",\"new_value\":\"total_population,male_residents,female_residents\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('52', '1', 'System Setting Changed', '{\"setting\":\"smtp_host\",\"old_value\":\"Previous Value\",\"new_value\":\"smtp.gmail.com\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('53', '1', 'System Setting Changed', '{\"setting\":\"smtp_port\",\"old_value\":\"Previous Value\",\"new_value\":\"587\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('54', '1', 'System Setting Changed', '{\"setting\":\"smtp_username\",\"old_value\":\"Previous Value\",\"new_value\":\"\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('55', '1', 'System Setting Changed', '{\"setting\":\"smtp_password\",\"old_value\":\"Previous Value\",\"new_value\":\"\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('56', '1', 'System Setting Changed', '{\"setting\":\"smtp_secure\",\"old_value\":\"Previous Value\",\"new_value\":\"tls\",\"timestamp\":\"2025-07-13 07:49:47\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:47', '2025-07-13 13:49:47');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('57', '1', 'System Setting Changed', '{\"setting\":\"smtp_from_email\",\"old_value\":\"Previous Value\",\"new_value\":\"noreply@barangay.local\",\"timestamp\":\"2025-07-13 07:49:48\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:48', '2025-07-13 13:49:48');
INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`, `created_at`) VALUES ('58', '1', 'System Setting Changed', '{\"setting\":\"smtp_from_name\",\"old_value\":\"Previous Value\",\"new_value\":\"Barangay System\",\"timestamp\":\"2025-07-13 07:49:48\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-13 13:49:48', '2025-07-13 13:49:48');


-- Table structure for table `barangay_officials`
DROP TABLE IF EXISTS `barangay_officials`;
CREATE TABLE `barangay_officials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `term_start` date DEFAULT NULL,
  `term_end` date DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_position` (`position`),
  KEY `idx_status` (`status`),
  KEY `idx_full_name` (`last_name`,`first_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `barangay_officials`
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Nelson', 'C', 'Mallari', NULL, 'Punong Barangay', NULL, NULL, NULL, 'Active', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Israel', 'R', 'Galang', NULL, 'Sangguniang Barangay Member', NULL, NULL, NULL, 'Active', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Yorlan', 'G', 'Talampas', NULL, 'Sangguniang Barangay Member', NULL, NULL, NULL, 'Active', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('4', 'Erlito', 'A.', 'Acosta', NULL, 'Sangguniang Barangay Member', NULL, NULL, NULL, 'Active', '2025-07-13 10:39:23', '2025-07-13 12:15:59');
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('5', 'Virgilio', 'M', 'Cruz', NULL, 'Sangguniang Barangay Member', NULL, NULL, NULL, 'Active', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('6', 'Dennis', 'S', 'Aguilar', NULL, 'Sangguniang Barangay Member', NULL, NULL, NULL, 'Active', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('7', 'Jeremy Roland', 'G', 'Regalado', NULL, 'Sangguniang Barangay Member', NULL, NULL, NULL, 'Active', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('8', 'Marissa', 'B', 'Cristobal', NULL, 'Sangguniang Barangay Member', NULL, NULL, NULL, 'Active', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `barangay_officials` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `position`, `term_start`, `term_end`, `contact_number`, `status`, `created_at`, `updated_at`) VALUES ('9', 'Jennifer', 'I', 'De Leon', NULL, 'Barangay Secretary', NULL, NULL, NULL, 'Active', '2025-07-13 12:15:41', '2025-07-13 12:15:41');


-- Table structure for table `certificate_requests`
DROP TABLE IF EXISTS `certificate_requests`;
CREATE TABLE `certificate_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `individual_id` int(11) DEFAULT NULL,
  `certificate_type` varchar(50) NOT NULL,
  `purpose` text DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Issued') NOT NULL DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cert_individual` (`individual_id`),
  KEY `fk_cert_requested_by` (`requested_by`),
  KEY `fk_cert_processed_by` (`processed_by`),
  KEY `idx_certificate_type` (`certificate_type`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_at` (`requested_at`),
  KEY `idx_certificate_number` (`certificate_number`),
  KEY `idx_certificate_requests_type_status` (`certificate_type`,`status`,`requested_at`),
  CONSTRAINT `fk_cert_individual` FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cert_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cert_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table structure for table `certificate_tracking`
DROP TABLE IF EXISTS `certificate_tracking`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `certificate_tracking` AS select `cr`.`id` AS `id`,concat('CERT-',ucase(`cr`.`certificate_type`),'-',year(`cr`.`requested_at`),'-',lpad(`cr`.`id`,4,'0')) AS `certificate_number`,`cr`.`certificate_type` AS `certificate_type`,concat(`i`.`first_name`,' ',coalesce(`i`.`middle_name`,''),' ',`i`.`last_name`) AS `resident_name`,`cr`.`status` AS `status`,`cr`.`requested_at` AS `requested_at`,`cr`.`processed_at` AS `processed_at`,concat(`u`.`first_name`,' ',`u`.`last_name`) AS `processed_by_name` from ((`certificate_requests` `cr` left join `individuals` `i` on(`cr`.`individual_id` = `i`.`id`)) left join `users` `u` on(`cr`.`processed_by` = `u`.`id`));


-- Table structure for table `dashboard_stats`
DROP TABLE IF EXISTS `dashboard_stats`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dashboard_stats` AS select count(0) AS `total_population`,sum(case when `individuals`.`gender` = 'Male' then 1 else 0 end) AS `male_residents`,sum(case when `individuals`.`gender` = 'Female' then 1 else 0 end) AS `female_residents`,sum(`individuals`.`is_voter`) AS `registered_voters`,sum(`individuals`.`is_4ps`) AS `fourps_beneficiaries`,sum(case when `individuals`.`birthdate` is not null and timestampdiff(YEAR,`individuals`.`birthdate`,curdate()) >= 60 then 1 else 0 end) AS `senior_citizens`,sum(`individuals`.`is_pwd`) AS `registered_pwds`,sum(`individuals`.`is_solo_parent`) AS `solo_parents`,sum(case when `individuals`.`birthdate` is not null and timestampdiff(YEAR,`individuals`.`birthdate`,curdate()) <= 17 then 1 else 0 end) AS `children_youth` from `individuals`;

-- Dumping data for table `dashboard_stats`
INSERT INTO `dashboard_stats` (`total_population`, `male_residents`, `female_residents`, `registered_voters`, `fourps_beneficiaries`, `senior_citizens`, `registered_pwds`, `solo_parents`, `children_youth`) VALUES ('4', '3', '1', '4', '1', '0', '1', '1', '0');


-- Table structure for table `individuals`
DROP TABLE IF EXISTS `individuals`;
CREATE TABLE `individuals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `birthdate` date DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Divorced') DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `religion` enum('Roman Catholic','Protestant','Islam','Iglesia ni Cristo','Born Again','Baptist','Methodist','Seventh-day Adventist','Jehovah''s Witness','Buddhism','Other','None') DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `purok_id` int(11) NOT NULL,
  `is_pwd` tinyint(1) NOT NULL DEFAULT 0,
  `is_voter` tinyint(1) NOT NULL DEFAULT 0,
  `is_4ps` tinyint(1) NOT NULL DEFAULT 0,
  `is_pregnant` tinyint(1) NOT NULL DEFAULT 0,
  `is_solo_parent` tinyint(1) NOT NULL DEFAULT 0,
  `is_senior_citizen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_individuals_purok` (`purok_id`),
  KEY `idx_full_name` (`last_name`,`first_name`),
  KEY `idx_gender` (`gender`),
  KEY `idx_birthdate` (`birthdate`),
  KEY `idx_civil_status` (`civil_status`),
  KEY `idx_email` (`email`),
  KEY `idx_status_fields` (`is_pwd`,`is_voter`,`is_4ps`,`is_solo_parent`,`is_senior_citizen`),
  KEY `idx_individuals_dashboard_stats` (`gender`,`is_voter`,`is_pwd`,`is_4ps`,`is_solo_parent`),
  KEY `idx_individuals_age_calculation` (`birthdate`),
  KEY `idx_individuals_name_search` (`first_name`,`last_name`,`middle_name`),
  CONSTRAINT `fk_individuals_purok` FOREIGN KEY (`purok_id`) REFERENCES `purok` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `individuals`
INSERT INTO `individuals` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthdate`, `civil_status`, `blood_type`, `religion`, `email`, `contact_no`, `purok_id`, `is_pwd`, `is_voter`, `is_4ps`, `is_pregnant`, `is_solo_parent`, `is_senior_citizen`, `created_at`, `updated_at`) VALUES ('2', 'James Ivan Deric', 'Marcillan', 'Dacles', '', 'Male', '2004-09-11', 'Single', 'O+', 'Roman Catholic', 'gmmxxbiz@gmail.com', NULL, '2', '0', '1', '0', '0', '0', '0', '2025-07-13 11:27:48', '2025-07-13 11:27:48');
INSERT INTO `individuals` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthdate`, `civil_status`, `blood_type`, `religion`, `email`, `contact_no`, `purok_id`, `is_pwd`, `is_voter`, `is_4ps`, `is_pregnant`, `is_solo_parent`, `is_senior_citizen`, `created_at`, `updated_at`) VALUES ('3', 'Estrellita', 'Marcillan', 'Dacles', '', 'Female', '1983-11-06', 'Married', 'O+', 'Roman Catholic', 'daclesivan11aclc@gmail.com', NULL, '2', '0', '1', '0', '0', '0', '0', '2025-07-13 11:28:32', '2025-07-13 11:28:32');
INSERT INTO `individuals` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthdate`, `civil_status`, `blood_type`, `religion`, `email`, `contact_no`, `purok_id`, `is_pwd`, `is_voter`, `is_4ps`, `is_pregnant`, `is_solo_parent`, `is_senior_citizen`, `created_at`, `updated_at`) VALUES ('4', 'Dennis', 'De Jesus', 'Dacles', '', 'Male', '1980-05-14', 'Married', 'O+', 'Roman Catholic', 'daclesjamesivan.va@gmail.com', NULL, '2', '0', '1', '0', '0', '0', '0', '2025-07-13 11:29:12', '2025-07-13 11:29:46');
INSERT INTO `individuals` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthdate`, `civil_status`, `blood_type`, `religion`, `email`, `contact_no`, `purok_id`, `is_pwd`, `is_voter`, `is_4ps`, `is_pregnant`, `is_solo_parent`, `is_senior_citizen`, `created_at`, `updated_at`) VALUES ('6', 'Juan', 'Cruz', 'Dela Cruz', 'Jr.', 'Male', '1986-11-09', 'Widowed', 'AB+', 'Roman Catholic', 'juandc@gmail.com', NULL, '3', '1', '1', '1', '0', '1', '0', '2025-07-13 11:35:02', '2025-07-13 11:36:14');


-- Table structure for table `issued_documents`
DROP TABLE IF EXISTS `issued_documents`;
CREATE TABLE `issued_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_request_id` int(11) DEFAULT NULL,
  `individual_id` int(11) DEFAULT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_number` varchar(100) NOT NULL,
  `issued_by` int(11) NOT NULL,
  `issued_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `purpose` text DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('Active','Revoked','Expired') NOT NULL DEFAULT 'Active',
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_number` (`document_number`),
  UNIQUE KEY `uk_document_number` (`document_number`),
  KEY `fk_issued_cert_request` (`certificate_request_id`),
  KEY `fk_issued_individual` (`individual_id`),
  KEY `fk_issued_by` (`issued_by`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_issued_date` (`issued_date`),
  KEY `idx_status` (`status`),
  KEY `idx_issued_documents_active` (`status`,`issued_date`),
  CONSTRAINT `fk_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_issued_cert_request` FOREIGN KEY (`certificate_request_id`) REFERENCES `certificate_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_issued_individual` FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table structure for table `purok`
DROP TABLE IF EXISTS `purok`;
CREATE TABLE `purok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_purok_name` (`name`),
  KEY `idx_purok_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `purok`
INSERT INTO `purok` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES ('1', 'Purok 1 (Pulongtingga)', 'Purok 1 - Pulongtingga', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `purok` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES ('2', 'Purok 2 (Looban)', 'Purok 2 - Looban', '2025-07-13 10:39:23', '2025-07-13 10:39:23');
INSERT INTO `purok` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES ('3', 'Purok 3 (Proper)', 'Purok 3 - Proper', '2025-07-13 10:39:23', '2025-07-13 10:39:23');


-- Table structure for table `purok_population`
DROP TABLE IF EXISTS `purok_population`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `purok_population` AS select `p`.`id` AS `id`,`p`.`name` AS `purok_name`,count(`i`.`id`) AS `total_residents`,sum(case when `i`.`gender` = 'Male' then 1 else 0 end) AS `male_count`,sum(case when `i`.`gender` = 'Female' then 1 else 0 end) AS `female_count`,sum(`i`.`is_voter`) AS `voter_count`,sum(`i`.`is_pwd`) AS `pwd_count`,sum(`i`.`is_4ps`) AS `fourps_count`,sum(`i`.`is_solo_parent`) AS `solo_parent_count` from (`purok` `p` left join `individuals` `i` on(`p`.`id` = `i`.`purok_id`)) group by `p`.`id`,`p`.`name` order by `p`.`id`;

-- Dumping data for table `purok_population`
INSERT INTO `purok_population` (`id`, `purok_name`, `total_residents`, `male_count`, `female_count`, `voter_count`, `pwd_count`, `fourps_count`, `solo_parent_count`) VALUES ('1', 'Purok 1 (Pulongtingga)', '0', '0', '0', NULL, NULL, NULL, NULL);
INSERT INTO `purok_population` (`id`, `purok_name`, `total_residents`, `male_count`, `female_count`, `voter_count`, `pwd_count`, `fourps_count`, `solo_parent_count`) VALUES ('2', 'Purok 2 (Looban)', '3', '2', '1', '3', '0', '0', '0');
INSERT INTO `purok_population` (`id`, `purok_name`, `total_residents`, `male_count`, `female_count`, `voter_count`, `pwd_count`, `fourps_count`, `solo_parent_count`) VALUES ('3', 'Purok 3 (Proper)', '1', '1', '0', '1', '1', '1', '1');


-- Table structure for table `resident_summary`
DROP TABLE IF EXISTS `resident_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `resident_summary` AS select `i`.`id` AS `id`,concat(`i`.`first_name`,' ',coalesce(`i`.`middle_name`,''),' ',`i`.`last_name`,' ',coalesce(`i`.`suffix`,'')) AS `full_name`,`i`.`gender` AS `gender`,timestampdiff(YEAR,`i`.`birthdate`,curdate()) AS `age`,`i`.`civil_status` AS `civil_status`,`p`.`name` AS `purok_name`,`i`.`is_pwd` AS `is_pwd`,`i`.`is_voter` AS `is_voter`,`i`.`is_4ps` AS `is_4ps`,`i`.`is_solo_parent` AS `is_solo_parent`,`i`.`is_senior_citizen` AS `is_senior_citizen`,case when `i`.`birthdate` is not null and timestampdiff(YEAR,`i`.`birthdate`,curdate()) <= 17 then 1 else 0 end AS `is_minor`,case when `i`.`birthdate` is not null and timestampdiff(YEAR,`i`.`birthdate`,curdate()) >= 60 then 1 else 0 end AS `is_calculated_senior`,`i`.`created_at` AS `created_at` from (`individuals` `i` left join `purok` `p` on(`i`.`purok_id` = `p`.`id`));

-- Dumping data for table `resident_summary`
INSERT INTO `resident_summary` (`id`, `full_name`, `gender`, `age`, `civil_status`, `purok_name`, `is_pwd`, `is_voter`, `is_4ps`, `is_solo_parent`, `is_senior_citizen`, `is_minor`, `is_calculated_senior`, `created_at`) VALUES ('2', 'James Ivan Deric Marcillan Dacles ', 'Male', '20', 'Single', 'Purok 2 (Looban)', '0', '1', '0', '0', '0', '0', '0', '2025-07-13 11:27:48');
INSERT INTO `resident_summary` (`id`, `full_name`, `gender`, `age`, `civil_status`, `purok_name`, `is_pwd`, `is_voter`, `is_4ps`, `is_solo_parent`, `is_senior_citizen`, `is_minor`, `is_calculated_senior`, `created_at`) VALUES ('3', 'Estrellita Marcillan Dacles ', 'Female', '41', 'Married', 'Purok 2 (Looban)', '0', '1', '0', '0', '0', '0', '0', '2025-07-13 11:28:32');
INSERT INTO `resident_summary` (`id`, `full_name`, `gender`, `age`, `civil_status`, `purok_name`, `is_pwd`, `is_voter`, `is_4ps`, `is_solo_parent`, `is_senior_citizen`, `is_minor`, `is_calculated_senior`, `created_at`) VALUES ('4', 'Dennis De Jesus Dacles ', 'Male', '45', 'Married', 'Purok 2 (Looban)', '0', '1', '0', '0', '0', '0', '0', '2025-07-13 11:29:12');
INSERT INTO `resident_summary` (`id`, `full_name`, `gender`, `age`, `civil_status`, `purok_name`, `is_pwd`, `is_voter`, `is_4ps`, `is_solo_parent`, `is_senior_citizen`, `is_minor`, `is_calculated_senior`, `created_at`) VALUES ('6', 'Juan Cruz Dela Cruz Jr.', 'Male', '38', 'Widowed', 'Purok 3 (Proper)', '1', '1', '1', '1', '0', '0', '0', '2025-07-13 11:35:02');


-- Table structure for table `system_settings`
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  UNIQUE KEY `uk_setting_key` (`setting_key`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `system_settings`
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('1', 'system_title', 'Digital Identity and Certification Management System', 'System title displayed in header', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('2', 'barangay_name', 'Sucol', 'Name of the barangay', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('3', 'municipality', 'Calumpit', 'Municipality or city name', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('4', 'province', 'Bulacan', 'Province name', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('5', 'barangay_address', 'Sucol, Calumpit, Bulacan', 'Complete barangay address', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('6', 'barangay_logo_path', 'img/logo.png', 'Path to barangay logo', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('7', 'records_per_page', '25', 'Number of records per page', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('8', 'session_timeout', '30', 'Session timeout in minutes', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('9', 'primary_color', '#2563eb', 'Primary theme color', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('10', 'dashboard_cards_enabled', 'total_population,male_residents,female_residents', 'Comma-separated list of enabled dashboard cards', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('11', 'smtp_host', 'smtp.gmail.com', 'SMTP server host', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('12', 'smtp_port', '587', 'SMTP server port', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('13', 'smtp_username', '', 'SMTP username', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('14', 'smtp_password', '', 'SMTP password', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('15', 'smtp_secure', 'tls', 'SMTP encryption type', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('16', 'smtp_from_email', 'noreply@barangay.local', 'From email address', '2025-07-13 10:39:23', '2025-07-13 13:49:47');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES ('17', 'smtp_from_name', 'Barangay System', 'From name', '2025-07-13 10:39:23', '2025-07-13 13:49:47');


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'secretary',
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_reset_token` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `role`, `reset_token`, `reset_token_expires_at`, `created_at`, `updated_at`) VALUES ('1', 'secretary', 'gmmxxbiz@gmail.com', '$2y$10$XxQizYZNKu8UYAxFonk9PeWFVdNBX/BEOWYEjOOAo7E/K1mVGmfLu', 'Jennifer', 'De Leon', 'secretary', NULL, NULL, '2025-07-13 10:39:23', '2025-07-13 11:53:40');

COMMIT;
