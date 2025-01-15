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
    
    if (!isset($data['exam_id']) || !isset($data['subject_id']) || !isset($data['marks'])) {
        throw new Exception('Exam ID, subject ID, and marks are required');
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Delete existing results for this exam, subject, and students
    $stmt = $conn->prepare("
        DELETE FROM exam_results 
        WHERE exam_id = ? AND subject_id = ? 
        AND student_id IN (" . implode(',', array_keys($data['marks'])) . ")
    ");
    $stmt->execute([$data['exam_id'], $data['subject_id']]);
    
    // Prepare insert statement
    $stmt = $conn->prepare("
        INSERT INTO exam_results (
            exam_id, subject_id, student_id, 
            marks, grade, remarks
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // Insert new results
    foreach ($data['marks'] as $studentId => $marks) {
        if (!empty($marks)) {
            $stmt->execute([
                $data['exam_id'],
                $data['subject_id'],
                $studentId,
                $marks,
                $data['grades'][$studentId] ?? null,
                $data['remarks'][$studentId] ?? null
            ]);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Results saved successfully']);
} catch(Exception $e) {
    // Rollback transaction if there was an error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
