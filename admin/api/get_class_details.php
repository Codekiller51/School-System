<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

try {
    $id = $_GET['id'];
    
    // Get class details
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) as student_count,
            (SELECT COUNT(*) FROM subjects cs WHERE cs.id = c.id) as subject_count
        FROM classes c
        LEFT JOIN teachers t ON c.teacher_id = t.id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $class = $result->fetch_assoc();
    
    if (!$class) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        exit;
    }
    
    // Get students in this class
    $stmt = $conn->prepare("
        SELECT 
            id,
            student_id,
            CONCAT(first_name, ' ', last_name) as name,
            status
        FROM students 
        WHERE class_id = ?
        ORDER BY first_name, last_name
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    $class['students'] = $students;
    echo json_encode($class);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
