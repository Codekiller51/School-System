<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $schoolName = trim($_POST['schoolName'] ?? '');
    $schoolAddress = trim($_POST['schoolAddress'] ?? '');
    $schoolPhone = trim($_POST['schoolPhone'] ?? '');
    $schoolEmail = trim($_POST['schoolEmail'] ?? '');
    $academicYear = trim($_POST['academicYear'] ?? '');

    if (empty($schoolName) || empty($schoolAddress) || empty($schoolPhone) || 
        empty($schoolEmail) || empty($academicYear)) {
        throw new Exception('All fields are required');
    }

    if (!filter_var($schoolEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if settings exist
    $query = "SELECT COUNT(*) FROM school_settings";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        // Update existing settings
        $query = "UPDATE school_settings SET 
                  school_name = ?, 
                  address = ?, 
                  phone = ?, 
                  email = ?, 
                  current_academic_year = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $schoolName, 
            $schoolAddress, 
            $schoolPhone, 
            $schoolEmail, 
            $academicYear
        ]);
    } else {
        // Insert new settings
        $query = "INSERT INTO school_settings 
                  (school_name, address, phone, email, current_academic_year) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $schoolName, 
            $schoolAddress, 
            $schoolPhone, 
            $schoolEmail, 
            $academicYear
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'School settings updated successfully'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
