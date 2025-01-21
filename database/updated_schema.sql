-- Create the database
CREATE DATABASE IF NOT EXISTS school_system;
USE school_system;

-- Create administrators table
CREATE TABLE IF NOT EXISTS `administrators` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `first_name` varchar(50) NOT NULL,
    `last_name` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `profile_image` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create teachers table
CREATE TABLE IF NOT EXISTS `teachers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `first_name` varchar(50) NOT NULL,
    `last_name` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `date_of_birth` date DEFAULT NULL,
    `gender` enum('Male','Female','Other') DEFAULT NULL,
    `subject_specialization` varchar(100) DEFAULT NULL,
    `qualification` varchar(100) DEFAULT NULL,
    `experience_years` int(11) DEFAULT NULL,
    `joining_date` date DEFAULT NULL,
    `profile_image` varchar(255) DEFAULT NULL,
    `status` enum('Active','Inactive') DEFAULT 'Active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create classes table
CREATE TABLE IF NOT EXISTS `classes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `section` varchar(10) DEFAULT NULL,
    `capacity` int(11) DEFAULT 30,
    `teacher_id` int(11) DEFAULT NULL,
    `academic_year` varchar(9) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `room_number` varchar(10) DEFAULT NULL,
    `status` enum('Active','Inactive') DEFAULT 'Active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `teacher_id` (`teacher_id`),
    CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create students table
CREATE TABLE IF NOT EXISTS `students` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `first_name` varchar(50) NOT NULL,
    `last_name` varchar(50) NOT NULL,
    `email` varchar(100) DEFAULT NULL,
    `password` varchar(255) DEFAULT NULL,
    `class_id` int(11) NOT NULL,
    `roll_number` varchar(20) NOT NULL,
    `admission_number` varchar(20) DEFAULT NULL,
    `admission_date` date DEFAULT NULL,
    `gender` enum('Male','Female','Other') NOT NULL,
    `date_of_birth` date NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `blood_group` varchar(5) DEFAULT NULL,
    `profile_image` varchar(255) DEFAULT NULL,
    `status` enum('Active','Inactive','Graduated','Suspended') DEFAULT 'Active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `admission_number` (`admission_number`),
    KEY `class_id` (`class_id`),
    CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create subjects table
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `code` varchar(20) NOT NULL,
    `description` text DEFAULT NULL,
    `credits` int(11) DEFAULT 1,
    `type` enum('Mandatory','Optional','Extra') DEFAULT 'Mandatory',
    `status` enum('Active','Inactive') DEFAULT 'Active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create teacher_subjects table
CREATE TABLE IF NOT EXISTS `teacher_subjects` (
    `teacher_id` int(11) NOT NULL,
    `subject_id` int(11) NOT NULL,
    `class_id` int(11) NOT NULL,
    `academic_year` varchar(9) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`teacher_id`, `subject_id`, `class_id`),
    KEY `subject_id` (`subject_id`),
    KEY `class_id` (`class_id`),
    CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `teacher_subjects_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create parents table
CREATE TABLE IF NOT EXISTS `parents` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `first_name` varchar(50) NOT NULL,
    `last_name` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `alternate_phone` varchar(20) DEFAULT NULL,
    `occupation` varchar(100) DEFAULT NULL,
    `address` text NOT NULL,
    `status` enum('Active','Inactive') DEFAULT 'Active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create student_parent table
CREATE TABLE IF NOT EXISTS `student_parent` (
    `student_id` int(11) NOT NULL,
    `parent_id` int(11) NOT NULL,
    `relationship` varchar(50) NOT NULL,
    `is_primary` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`student_id`, `parent_id`),
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `student_parent_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `student_parent_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create attendance table
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `class_id` int(11) NOT NULL,
    `date` date NOT NULL,
    `status` enum('present','absent','late') NOT NULL,
    `remarks` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `student_id` (`student_id`),
    KEY `class_id` (`class_id`),
    CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create exams table
CREATE TABLE IF NOT EXISTS `exams` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `class_id` int(11) NOT NULL,
    `subject_id` int(11) NOT NULL,
    `exam_date` date NOT NULL,
    `start_time` time NOT NULL,
    `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
    `total_marks` int(11) NOT NULL DEFAULT 100,
    `passing_marks` int(11) NOT NULL DEFAULT 40,
    `instructions` text DEFAULT NULL,
    `status` enum('Scheduled','Ongoing','Completed','Cancelled') DEFAULT 'Scheduled',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `class_id` (`class_id`),
    KEY `subject_id` (`subject_id`),
    CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create exam_results table
CREATE TABLE IF NOT EXISTS `exam_results` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `exam_id` int(11) NOT NULL,
    `student_id` int(11) NOT NULL,
    `marks_obtained` decimal(5,2) NOT NULL,
    `grade` varchar(2) DEFAULT NULL,
    `remarks` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `exam_id` (`exam_id`),
    KEY `student_id` (`student_id`),
    CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
    CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create fees table
CREATE TABLE IF NOT EXISTS `fees` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `fee_type` varchar(50) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `due_date` date NOT NULL,
    `status` enum('paid','pending','overdue') NOT NULL,
    `payment_date` date DEFAULT NULL,
    `payment_method` varchar(50) DEFAULT NULL,
    `transaction_id` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `student_id` (`student_id`),
    CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create notification_queue table
CREATE TABLE IF NOT EXISTS `notification_queue` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `recipient_id` int(11) NOT NULL,
    `recipient_type` enum('student','parent','teacher','administrator') NOT NULL,
    `type` enum('absence','exam_result','discipline','announcement','fee') NOT NULL,
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `status` enum('pending','processing','processed','failed') NOT NULL DEFAULT 'pending',
    `error_message` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `processed_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create school_settings table
CREATE TABLE IF NOT EXISTS `school_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `school_name` varchar(255) NOT NULL,
    `school_code` varchar(50) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `current_academic_year` varchar(9) DEFAULT NULL,
    `enable_notifications` tinyint(1) DEFAULT 0,
    `enable_sms` tinyint(1) DEFAULT 0,
    `maintenance_mode` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default school settings if not exists
INSERT INTO school_settings (school_name, school_code, current_academic_year) 
SELECT 'School Name', 'SCH001', '2024-2025'
WHERE NOT EXISTS (SELECT 1 FROM school_settings);
