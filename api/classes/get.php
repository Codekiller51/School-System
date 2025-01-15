<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT c.*,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            (SELECT COUNT(*) FROM students s WHERE s.class_level = c.name AND s.section = c.section) as student_count
        FROM classes c
        LEFT JOIN teachers t ON c.class_teacher_id = t.id
        WHERE c.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $class]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
