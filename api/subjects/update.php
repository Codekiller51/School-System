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
        throw new Exception('Subject ID is required');
    }
    
    // Validate required fields
    $required_fields = ['subject_code', 'name', 'class_level', 'department', 'credits'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Check if subject code already exists for other subjects
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
    $stmt->execute([$data['subject_code'], $data['id']]);
    if ($stmt->fetch()) {
        throw new Exception('Subject code already exists');
    }
    
    $stmt = $conn->prepare("
        UPDATE subjects SET 
            subject_code = ?,
            name = ?,
            description = ?,
            class_level = ?,
            credits = ?,
            department = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['subject_code'],
        $data['name'],
        $data['description'] ?? null,
        $data['class_level'],
        $data['credits'],
        $data['department'],
        $data['id']
    ]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Subject not found or no changes made');
    }
    
    echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
