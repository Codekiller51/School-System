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
        throw new Exception('Class ID is required');
    }
    
    // Validate required fields
    $required_fields = ['name', 'section'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Get current class data
    $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$data['id']]);
    $currentClass = $stmt->fetch();
    
    if (!$currentClass) {
        throw new Exception('Class not found');
    }
    
    // Check if another class with same name and section exists for this academic year
    $stmt = $conn->prepare("
        SELECT id FROM classes 
        WHERE name = ? AND section = ? AND academic_year = ? AND id != ?
    ");
    $stmt->execute([
        $data['name'],
        $data['section'],
        $currentClass['academic_year'],
        $data['id']
    ]);
    if ($stmt->fetch()) {
        throw new Exception('Another class with this name and section already exists');
    }
    
    // If class teacher is specified, verify they exist
    if (!empty($data['class_teacher_id'])) {
        $stmt = $conn->prepare("SELECT id FROM teachers WHERE id = ?");
        $stmt->execute([$data['class_teacher_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Selected teacher does not exist');
        }
        
        // Check if teacher is already assigned to another class
        $stmt = $conn->prepare("
            SELECT id FROM classes 
            WHERE class_teacher_id = ? AND academic_year = ? AND id != ?
        ");
        $stmt->execute([
            $data['class_teacher_id'],
            $currentClass['academic_year'],
            $data['id']
        ]);
        if ($stmt->fetch()) {
            throw new Exception('Selected teacher is already assigned to another class');
        }
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Update class details
    $stmt = $conn->prepare("
        UPDATE classes SET 
            name = ?,
            section = ?,
            class_teacher_id = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['name'],
        $data['section'],
        $data['class_teacher_id'] ?: null,
        $data['id']
    ]);
    
    // Update student class levels if class name changed
    if ($currentClass['name'] !== $data['name']) {
        $stmt = $conn->prepare("
            UPDATE students 
            SET class_level = ? 
            WHERE class_level = ? AND section = ?
        ");
        $stmt->execute([
            $data['name'],
            $currentClass['name'],
            $data['section']
        ]);
    }
    
    // Update student sections if section changed
    if ($currentClass['section'] !== $data['section']) {
        $stmt = $conn->prepare("
            UPDATE students 
            SET section = ? 
            WHERE class_level = ? AND section = ?
        ");
        $stmt->execute([
            $data['section'],
            $data['name'],
            $currentClass['section']
        ]);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Class updated successfully']);
} catch(Exception $e) {
    // Rollback transaction if there was an error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
