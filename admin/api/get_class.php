<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

try {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $class = $result->fetch_assoc();
    
    if ($class) {
        echo json_encode($class);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Class not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
