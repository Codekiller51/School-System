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
    
    if (!isset($data['id'])) {
        throw new Exception('Lesson ID is required');
    }
    
    // Validate required fields
    $required_fields = ['subject_id', 'teacher_id'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Get current lesson details
    $stmt = $conn->prepare("SELECT * FROM timetable WHERE id = ?");
    $stmt->execute([$data['id']]);
    $currentLesson = $stmt->fetch();
    
    if (!$currentLesson) {
        throw new Exception('Lesson not found');
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
    
    // Check if teacher is available at this time (excluding current lesson)
    $stmt = $conn->prepare("
        SELECT t.id, t.class_name, t.section 
        FROM timetable t
        WHERE t.teacher_id = ? 
        AND t.day = ? AND t.time_slot = ? 
        AND t.academic_year = ?
        AND t.id != ?
    ");
    $stmt->execute([
        $data['teacher_id'],
        $currentLesson['day'],
        $currentLesson['time_slot'],
        $currentLesson['academic_year'],
        $data['id']
    ]);
    if ($clash = $stmt->fetch()) {
        throw new Exception(
            'Teacher is already assigned to class ' . 
            $clash['class_name'] . ' ' . $clash['section'] . 
            ' during this time slot'
        );
    }
    
    // Update the lesson
    $stmt = $conn->prepare("
        UPDATE timetable SET 
            subject_id = ?,
            teacher_id = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['subject_id'],
        $data['teacher_id'],
        $data['id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Lesson updated successfully']);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
