<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    if (!isset($_GET['exam_id']) || !isset($_GET['class_info']) || !isset($_GET['subject_id'])) {
        throw new Exception('Exam ID, class info, and subject ID are required');
    }
    
    // Split class_info into name and section
    list($className, $section) = explode('_', $_GET['class_info']);
    
    // Get all students in the class
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.admission_number,
            s.first_name,
            s.last_name,
            er.marks,
            er.grade,
            er.remarks
        FROM students s
        LEFT JOIN exam_results er ON 
            er.student_id = s.id AND 
            er.exam_id = ? AND 
            er.subject_id = ?
        WHERE s.class_level = ? AND s.section = ?
        ORDER BY s.first_name, s.last_name
    ");
    
    $stmt->execute([
        $_GET['exam_id'],
        $_GET['subject_id'],
        $className,
        $section
    ]);
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $students]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
