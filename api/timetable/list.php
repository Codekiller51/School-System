<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    if (!isset($_GET['class_name']) || !isset($_GET['section'])) {
        throw new Exception('Class name and section are required');
    }
    
    $className = $_GET['class_name'];
    $section = $_GET['section'];
    $academicYear = getAcademicYear();
    
    // Get class details
    $stmt = $conn->prepare("
        SELECT id FROM classes 
        WHERE name = ? AND section = ? AND academic_year = ?
    ");
    $stmt->execute([$className, $section, $academicYear]);
    $class = $stmt->fetch();
    
    if (!$class) {
        throw new Exception('Class not found');
    }
    
    // Get timetable entries
    $stmt = $conn->prepare("
        SELECT 
            t.id,
            t.day,
            t.time_slot,
            s.id as subject_id,
            s.name as subject_name,
            s.subject_code,
            CONCAT(tr.first_name, ' ', tr.last_name) as teacher_name,
            tr.id as teacher_id
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN teachers tr ON t.teacher_id = tr.id
        WHERE t.class_name = ? AND t.section = ? AND t.academic_year = ?
        ORDER BY t.day, t.time_slot
    ");
    
    $stmt->execute([$className, $section, $academicYear]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $lessons]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
