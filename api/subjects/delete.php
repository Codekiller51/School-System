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
        throw new Exception('Subject ID is required');
    }
    
    $id = $_GET['id'];
    
    // Check if subject exists
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Subject not found');
    }
    
    // Check if subject is assigned to any teachers in current academic year
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM teacher_subjects 
        WHERE subject_id = ? AND academic_year = ?
    ");
    $stmt->execute([$id, getAcademicYear()]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        throw new Exception('Cannot delete subject: It is currently assigned to teachers');
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Delete from teacher_subjects first (historical records)
    $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE subject_id = ?");
    $stmt->execute([$id]);
    
    // Delete the subject
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
} catch(Exception $e) {
    // Rollback transaction if there was an error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
