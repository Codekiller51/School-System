<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Exam ID is required');
    }
    
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam) {
        throw new Exception('Exam not found');
    }
    
    echo json_encode(['success' => true, 'data' => $exam]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
