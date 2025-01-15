<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = $_POST;
    
    if (!isset($data['teacher_id']) || !isset($data['subject_id'])) {
        throw new Exception('Teacher ID and Subject ID are required');
    }
    
    $stmt = $conn->prepare("
        DELETE FROM teacher_subjects 
        WHERE teacher_id = ? AND subject_id = ? AND academic_year = ?
    ");
    
    $stmt->execute([
        $data['teacher_id'],
        $data['subject_id'],
        getAcademicYear()
    ]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Subject assignment not found');
    }
    
    echo json_encode(['success' => true, 'message' => 'Subject assignment removed successfully']);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
