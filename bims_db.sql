-- Adminer 4.8.1 MySQL 5.5.5-10.4.28-MariaDB dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `families`;
CREATE TABLE `families` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `family_name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `individuals`;
CREATE TABLE `individuals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `family_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `is_voter` tinyint(1) DEFAULT 0,
  `is_pwd` tinyint(1) DEFAULT 0,
  `is_senior_citizen` tinyint(1) DEFAULT 0,
  `is_4ps_member` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `family_id` (`family_id`),
  CONSTRAINT `individuals_ibfk_1` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','secretary','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert a default admin/secretary user
-- Password for 'admin' is 'adminpassword'
-- Password for 'secretary' is 'secretarypassword'
INSERT INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin', '$2y$10$N.xPZ1Y9.V3gZ3Z4hK5j9uO0U2MhG5b6jK8L9iP0oN1qS2rT3uV4W', 'Administrator', 'admin'),
('secretary', '$2y$10$A.bC1D2eF3gH4iJ5kL6mN7oP8qR9sT0uV1wX2yZ3aB4cDeFgHiJk', 'Barangay Secretary', 'secretary');

DROP TABLE IF EXISTS `blotter_records`;
CREATE TABLE `blotter_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `complainant_name` VARCHAR(255) NOT NULL,
    `respondent_name` VARCHAR(255) NOT NULL,
    `incident_type` VARCHAR(100),
    `incident_date` DATE,
    `incident_time` TIME,
    `incident_location` VARCHAR(255),
    `details` TEXT,
    `status` VARCHAR(50) DEFAULT 'Pending', -- e.g., Pending, Ongoing, Settled, Referred
    `recorded_by` INT, -- User ID of the recorder
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `certificates`;
CREATE TABLE `certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `resident_id` INT NOT NULL,
    `certificate_type` VARCHAR(100) NOT NULL, -- e.g., Barangay Clearance, Certificate of Indigency
    `purpose` TEXT,
    `issued_date` DATE NOT NULL,
    `issued_by` INT, -- User ID of the issuer
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`resident_id`) REFERENCES `individuals`(`id`),
    FOREIGN KEY (`issued_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `business_permits`;
CREATE TABLE `business_permits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT, -- Can be NULL if owner is not a registered resident, or link to individuals table
    `business_name` VARCHAR(255) NOT NULL,
    `business_type` VARCHAR(100),
    `address` VARCHAR(255),
    `application_date` DATE,
    `issued_date` DATE,
    `expiry_date` DATE,
    `status` VARCHAR(50) DEFAULT 'Pending', -- e.g., Pending, Approved, Expired, Revoked
    `issued_by` INT, -- User ID of the issuer
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `individuals`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`issued_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `description` VARCHAR(255),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('barangay_name', 'Barangay SUCCOL', 'Name of the Barangay'),
('municipality_name', 'Municipality of KALUMPIT', 'Name of the Municipality/City'),
('province_name', 'Province of BULACAN', 'Name of the Province'),
('barangay_logo_path', 'img/logo.png', 'Path to the barangay logo'),
('system_title', 'Barangay Information Management System', 'Title of the system');

DROP TABLE IF EXISTS `system_images`;
CREATE TABLE `system_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `image_name` VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'barangay_logo', 'event_banner_1'
    `image_path` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert the primary barangay logo
INSERT INTO `system_images` (`image_name`, `image_path`, `description`) VALUES
('barangay_logo', 'img/logo.png', 'The official barangay logo.');

-- You might want to add an audit trail table
DROP TABLE IF EXISTS `audit_trail`;
CREATE TABLE `audit_trail` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(255) NOT NULL, -- e.g., "Logged in", "Added new resident: John Doe", "Updated family ID: 5"
    `details` TEXT, -- Optional: more details about the action
    `ip_address` VARCHAR(45),
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;