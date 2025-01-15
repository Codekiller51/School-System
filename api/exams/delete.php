<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Exam ID is required');
    }
    
    // Check if exam exists
    $stmt = $conn->prepare("SELECT id FROM exams WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Exam not found');
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Delete exam results first
    $stmt = $conn->prepare("DELETE FROM exam_results WHERE exam_id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Delete the exam
    $stmt = $conn->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Exam deleted successfully']);
} catch(Exception $e) {
    // Rollback transaction if there was an error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
