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
    
    // Validate required fields
    $required_fields = ['class_name', 'subject_id', 'teacher_id', 'day', 'time_slot'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Split class_name into name and section
    list($className, $section) = explode('_', $data['class_name']);
    $academicYear = getAcademicYear();
    
    // Verify class exists
    $stmt = $conn->prepare("
        SELECT id FROM classes 
        WHERE name = ? AND section = ? AND academic_year = ?
    ");
    $stmt->execute([$className, $section, $academicYear]);
    if (!$stmt->fetch()) {
        throw new Exception('Class not found');
    }
    
    // Verify subject exists
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE id = ?");
    $stmt->execute([$data['subject_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Subject not found');
    }
    
    // Verify teacher exists
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE id = ?");
    $stmt->execute([$data['teacher_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Teacher not found');
    }
    
    // Check if time slot is already taken for this class
    $stmt = $conn->prepare("
        SELECT id FROM timetable 
        WHERE class_name = ? AND section = ? 
        AND day = ? AND time_slot = ? 
        AND academic_year = ?
    ");
    $stmt->execute([$className, $section, $data['day'], $data['time_slot'], $academicYear]);
    if ($stmt->fetch()) {
        throw new Exception('This time slot is already taken for this class');
    }
    
    // Check if teacher is available at this time
    $stmt = $conn->prepare("
        SELECT t.id, t.class_name, t.section 
        FROM timetable t
        WHERE t.teacher_id = ? 
        AND t.day = ? AND t.time_slot = ? 
        AND t.academic_year = ?
    ");
    $stmt->execute([$data['teacher_id'], $data['day'], $data['time_slot'], $academicYear]);
    if ($clash = $stmt->fetch()) {
        throw new Exception(
            'Teacher is already assigned to class ' . 
            $clash['class_name'] . ' ' . $clash['section'] . 
            ' during this time slot'
        );
    }
    
    // Add the lesson
    $stmt = $conn->prepare("
        INSERT INTO timetable (
            class_name, section, subject_id, teacher_id,
            day, time_slot, academic_year
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $className,
        $section,
        $data['subject_id'],
        $data['teacher_id'],
        $data['day'],
        $data['time_slot'],
        $academicYear
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Lesson added successfully',
        'lesson_id' => $conn->lastInsertId()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
