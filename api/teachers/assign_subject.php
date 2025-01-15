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
    
    // Check if assignment already exists
    $stmt = $conn->prepare("
        SELECT id FROM teacher_subjects 
        WHERE teacher_id = ? AND subject_id = ? AND academic_year = ?
    ");
    $stmt->execute([$data['teacher_id'], $data['subject_id'], getAcademicYear()]);
    
    if ($stmt->fetch()) {
        throw new Exception('Subject is already assigned to this teacher');
    }
    
    // Add new assignment
    $stmt = $conn->prepare("
        INSERT INTO teacher_subjects (teacher_id, subject_id, academic_year) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $data['teacher_id'],
        $data['subject_id'],
        getAcademicYear()
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Subject assigned successfully']);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
