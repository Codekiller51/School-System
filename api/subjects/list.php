<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    $stmt = $conn->prepare("
        SELECT s.*, 
            (SELECT COUNT(*) FROM teacher_subjects ts WHERE ts.subject_id = s.id AND ts.academic_year = ?) as teacher_count
        FROM subjects s
        ORDER BY s.department, s.class_level, s.name
    ");
    $stmt->execute([getAcademicYear()]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($subjects);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
