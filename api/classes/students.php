<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    if (!isset($_GET['class_id'])) {
        throw new Exception('Class ID is required');
    }
    
    $class_id = $_GET['class_id'];
    $academic_year = $_GET['academic_year'] ?? getAcademicYear();
    
    // Get class details first
    $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        throw new Exception('Class not found');
    }
    
    // Get students in this class
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.admission_number,
            s.first_name,
            s.last_name,
            s.gender,
            s.parent_name,
            s.parent_phone
        FROM students s
        WHERE s.class_level = ? 
        AND s.section = ?
        ORDER BY s.first_name, s.last_name
    ");
    
    $stmt->execute([$class['name'], $class['section']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $students
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
