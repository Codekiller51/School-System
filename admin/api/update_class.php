<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $class_id = $_POST['class_id'];
    $name = $_POST['name'];
    $grade_level = $_POST['grade_level'];
    $section = $_POST['section'];
    $teacher_id = $_POST['teacher_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("
        UPDATE classes 
        SET name = ?, 
            grade_level = ?, 
            section = ?, 
            teacher_id = ?, 
            status = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("sisssi", $name, $grade_level, $section, $teacher_id, $status, $class_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
