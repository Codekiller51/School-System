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
    $required_fields = ['subject_code', 'name', 'class_level', 'department', 'credits'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Check if subject code already exists
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ?");
    $stmt->execute([$data['subject_code']]);
    if ($stmt->fetch()) {
        throw new Exception('Subject code already exists');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO subjects (
            subject_code, name, description, class_level, 
            credits, department
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['subject_code'],
        $data['name'],
        $data['description'] ?? null,
        $data['class_level'],
        $data['credits'],
        $data['department']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Subject added successfully',
        'subject_id' => $conn->lastInsertId()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
