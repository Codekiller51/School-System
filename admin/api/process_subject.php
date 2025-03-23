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
    // Get and sanitize input data
    $subjectCode = trim($_POST['subjectCode']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $credits = intval($_POST['credits']);
    $department = trim($_POST['department']); // This will be stored in description
    $classLevel = trim($_POST['classLevel']); // This will be stored in description

    // Validate required fields
    if (empty($subjectCode) || empty($name) || empty($credits)) {
        throw new Exception('Required fields cannot be empty');
    }

    // Combine department and class level into description
    $fullDescription = "Department: $department\nClass Level: $classLevel\n\n$description";

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO subjects (code, name, description, credits, type, status) VALUES (?, ?, ?, ?, 'Mandatory', 'Active')");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("sssi", $subjectCode, $name, $fullDescription, $credits);

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Subject added successfully',
        'subjectId' => $conn->insert_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
