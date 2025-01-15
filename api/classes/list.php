<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    $academic_year = $_GET['academic_year'] ?? getAcademicYear();
    
    $stmt = $conn->prepare("
        SELECT c.*,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            (SELECT COUNT(*) FROM students s WHERE s.class_level = c.name AND s.section = c.section) as student_count
        FROM classes c
        LEFT JOIN teachers t ON c.class_teacher_id = t.id
        WHERE c.academic_year = ?
        ORDER BY c.name, c.section
    ");
    
    $stmt->execute([$academic_year]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($classes);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
