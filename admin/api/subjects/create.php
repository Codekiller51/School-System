<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $subjectCode = trim($_POST['subjectCode'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $classLevel = trim($_POST['classLevel'] ?? '');
    $credits = intval($_POST['credits'] ?? 0);
    $department = trim($_POST['department'] ?? '');

    if (empty($subjectCode) || empty($name) || empty($classLevel) || $credits <= 0 || empty($department)) {
        throw new Exception('Required fields are missing or invalid');
    }

    // Check if subject code already exists
    $checkQuery = "SELECT id FROM subjects WHERE subject_code = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$subjectCode]);
    if ($checkStmt->fetch()) {
        throw new Exception('Subject code already exists');
    }

    // Insert new subject
    $query = "INSERT INTO subjects (subject_code, name, description, class_level, credits, department) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$subjectCode, $name, $description, $classLevel, $credits, $department]);

    echo json_encode([
        'success' => true,
        'message' => 'Subject created successfully',
        'id' => $conn->lastInsertId()
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
