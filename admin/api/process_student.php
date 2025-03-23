<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Handle student deletion
        $student_id = intval($_POST['student_id']);
        
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param('i', $student_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete student");
        }
        
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
        exit;
    }
    
    // Handle student addition
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $admission_number = trim($_POST['admission_number']);
    $class_id = intval($_POST['class_id']);
    $gender = trim($_POST['gender']);
    $roll_number = trim($_POST['admission_number']); // Using admission number as roll number
    $admission_date = date('Y-m-d'); // Current date as admission date
    $date_of_birth = trim($_POST['date_of_birth']);
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($admission_number) || 
        empty($class_id) || empty($gender) || empty($date_of_birth)) {
        throw new Exception('Required fields cannot be empty');
    }
    
    // Validate date of birth
    $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
    if (!$dob || $dob->format('Y-m-d') !== $date_of_birth) {
        throw new Exception('Invalid date of birth format');
    }
    
    // Check if admission number already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE admission_number = ?");
    $stmt->bind_param('s', $admission_number);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Admission number already exists');
    }
    
    // Insert new student
    $stmt = $conn->prepare("
        INSERT INTO students (
            first_name, last_name, email, phone, 
            admission_number, class_id, gender, roll_number,
            admission_date, date_of_birth, status
        ) VALUES (
            ?, ?, ?, ?, 
            ?, ?, ?, ?,
            ?, ?, 'Active'
        )
    ");
    
    $stmt->bind_param('sssssissss', 
        $first_name, $last_name, $email, $phone, 
        $admission_number, $class_id, $gender, $roll_number,
        $admission_date, $date_of_birth
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to add student: " . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Student added successfully',
        'studentId' => $conn->insert_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
