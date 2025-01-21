<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $className = trim($_POST['className'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $teacherId = trim($_POST['teacherId'] ?? '');

    if (empty($className) || empty($section) || empty($teacherId)) {
        throw new Exception('All fields are required');
    }

    // Insert new class
    $query = "INSERT INTO classes (name, section, teacher_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$className, $section, $teacherId]);

    echo json_encode([
        'success' => true,
        'message' => 'Class created successfully',
        'id' => $conn->lastInsertId()
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
