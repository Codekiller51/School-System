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
        throw new Exception('Class ID is required');
    }
    
    $id = $_GET['id'];
    
    // Check if class exists
    $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        throw new Exception('Class not found');
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Remove class teacher assignment
    $stmt = $conn->prepare("
        UPDATE classes 
        SET class_teacher_id = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    
    // Remove student assignments to this class
    $stmt = $conn->prepare("
        UPDATE students 
        SET class_level = NULL, section = NULL 
        WHERE class_level = ? AND section = ?
    ");
    $stmt->execute([$class['name'], $class['section']]);
    
    // Delete the class
    $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Class deleted successfully']);
} catch(Exception $e) {
    // Rollback transaction if there was an error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
