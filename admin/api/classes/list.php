<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    $query = "SELECT c.*, 
              t.first_name as teacher_first_name, 
              t.last_name as teacher_last_name,
              (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) as total_students
              FROM classes c
              LEFT JOIN teachers t ON c.teacher_id = t.id
              ORDER BY c.name";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($classes);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
