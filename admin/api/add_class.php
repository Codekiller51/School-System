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
    $name = $_POST['name'];
    $grade_level = $_POST['grade_level'];
    $section = $_POST['section'];
    $teacher_id = $_POST['teacher_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO classes (name, grade_level, section, teacher_id, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $name, $grade_level, $section, $teacher_id, $status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
