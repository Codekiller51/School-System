<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Lesson ID is required');
    }
    
    $stmt = $conn->prepare("
        SELECT 
            t.*,
            s.name as subject_name,
            s.subject_code,
            CONCAT(tr.first_name, ' ', tr.last_name) as teacher_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN teachers tr ON t.teacher_id = tr.id
        WHERE t.id = ?
    ");
    
    $stmt->execute([$_GET['id']]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lesson) {
        throw new Exception('Lesson not found');
    }
    
    echo json_encode(['success' => true, 'data' => $lesson]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
