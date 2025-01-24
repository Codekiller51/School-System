<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Teacher ID is required']);
    exit;
}

try {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();
    
    if ($teacher) {
        echo json_encode($teacher);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
