-- Create the database
CREATE DATABASE IF NOT EXISTS school_system;
USE school_system;

-- Create administrators table
CREATE TABLE IF NOT EXISTS `administrators` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `employee_id` VARCHAR(20) UNIQUE NOT NULL,
    `phone` VARCHAR(20),
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create teachers table
CREATE TABLE IF NOT EXISTS `teachers` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `employee_id` VARCHAR(20) UNIQUE NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('male', 'female', 'other') NOT NULL,
    `department` VARCHAR(50) NOT NULL,
    `qualification` TEXT NOT NULL,
    `joining_date` DATE NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create students table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `admission_number` VARCHAR(20) UNIQUE NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('male', 'female', 'other') NOT NULL,
    `class_level` VARCHAR(20) NOT NULL,
    `section` VARCHAR(10) NOT NULL,
    `admission_date` DATE NOT NULL,
    `address` TEXT NOT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create parents table
CREATE TABLE IF NOT EXISTS `parents` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create student_parent relationship table
CREATE TABLE IF NOT EXISTS `student_parent` (
    `student_id` INT NOT NULL,
    `parent_id` INT NOT NULL,
    `relationship` VARCHAR(50) NOT NULL,
    `is_primary` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`student_id`, `parent_id`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `parents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create subjects table
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `subject_code` VARCHAR(20) UNIQUE NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `class_level` VARCHAR(20) NOT NULL,
    `credits` INT NOT NULL,
    `department` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create teacher_subjects table
CREATE TABLE IF NOT EXISTS `teacher_subjects` (
    `teacher_id` INT,
    `subject_id` INT,
    `academic_year` VARCHAR(9) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`teacher_id`, `subject_id`, `academic_year`),
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create classes table
CREATE TABLE IF NOT EXISTS `classes` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `section` VARCHAR(10) NOT NULL,
    `class_teacher_id` INT,
    `academic_year` VARCHAR(9) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create attendance table
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `class_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `status` ENUM('present', 'absent', 'late') NOT NULL,
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create exams table
CREATE TABLE IF NOT EXISTS `exams` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `exam_type` VARCHAR(50) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `academic_year` VARCHAR(9) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create exam_results table
CREATE TABLE IF NOT EXISTS `exam_results` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `exam_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `marks_obtained` DECIMAL(5,2) NOT NULL,
    `total_marks` DECIMAL(5,2) NOT NULL,
    `grade` VARCHAR(2),
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create fees table
CREATE TABLE IF NOT EXISTS `fees` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `fee_type` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `due_date` DATE NOT NULL,
    `status` ENUM('paid', 'pending', 'overdue') NOT NULL,
    `payment_date` DATE,
    `payment_method` VARCHAR(50),
    `transaction_id` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create notification_queue table
CREATE TABLE IF NOT EXISTS `notification_queue` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `type` ENUM('absence', 'exam_result', 'discipline', 'announcement') NOT NULL,
    `reference_id` INT,
    `reference_date` DATE,
    `status` ENUM('pending', 'processing', 'processed', 'failed') NOT NULL DEFAULT 'pending',
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create notification_logs table
CREATE TABLE IF NOT EXISTS `notification_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `notification_type` VARCHAR(50) NOT NULL,
    `notification_method` ENUM('email', 'sms', 'push') NOT NULL,
    `status` ENUM('sent', 'failed') NOT NULL,
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    INDEX `idx_student` (`student_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create notification_templates table
CREATE TABLE IF NOT EXISTS `notification_templates` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `type` VARCHAR(50) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `variables` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create login_logs table
CREATE TABLE IF NOT EXISTS `login_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `role` VARCHAR(20) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create password_resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `role` VARCHAR(20) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expiry` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default notification templates
INSERT INTO notification_templates (type, subject, body, variables) VALUES
('absence', 
 'Student Absence Notification - {student_name}',
 '<h2>Student Absence Notification</h2>
  <p>Dear {parent_name},</p>
  <p>This is to inform you that your child, {student_name}, was marked absent today in Class {class_level}-{section}.</p>
  {reason_text}
  <p>If you have not already informed the school about this absence, please contact us.</p>
  <p>Best regards,<br>{school_name}</p>',
 '["student_name", "parent_name", "class_level", "section", "reason_text", "school_name"]'
),
('exam_result',
 'Exam Results - {exam_name} - {student_name}',
 '<h2>Exam Results Notification</h2>
  <p>Dear {parent_name},</p>
  <p>The results for {exam_name} have been published for your child, {student_name} of Class {class_level}-{section}.</p>
  <h3>Results:</h3>
  <pre style="background-color: #f5f5f5; padding: 15px; border-radius: 5px;">
  {results_table}
  </pre>
  <p>You can view the detailed report card by logging into the parent portal.</p>
  <p>Best regards,<br>{school_name}</p>',
 '["student_name", "parent_name", "exam_name", "class_level", "section", "results_table", "school_name"]'
);

-- Create default admin account
INSERT INTO administrators (
    email, 
    password, 
    first_name, 
    last_name, 
    employee_id, 
    status
) VALUES (
    'admin@school.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password is 'password'
    'System',
    'Administrator',
    'ADMIN001',
    'active'
);

-- Create database user
CREATE USER IF NOT EXISTS 'school_user'@'localhost' IDENTIFIED BY 'school_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON school_system.* TO 'school_user'@'localhost';
FLUSH PRIVILEGES;
