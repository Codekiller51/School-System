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
    $required_fields = ['name', 'section', 'academic_year'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Check if class already exists for the academic year
    $stmt = $conn->prepare("
        SELECT id FROM classes 
        WHERE name = ? AND section = ? AND academic_year = ?
    ");
    $stmt->execute([$data['name'], $data['section'], $data['academic_year']]);
    if ($stmt->fetch()) {
        throw new Exception('Class already exists for this academic year');
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
            $data['academic_year'],
            $data['id'] ?? 0
        ]);
        if ($stmt->fetch()) {
            throw new Exception('Selected teacher is already assigned to another class');
        }
    }
    
    $stmt = $conn->prepare("
        INSERT INTO classes (
            name, section, class_teacher_id, academic_year
        ) VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['name'],
        $data['section'],
        $data['class_teacher_id'] ?: null,
        $data['academic_year']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Class added successfully',
        'class_id' => $conn->lastInsertId()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
