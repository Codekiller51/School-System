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
    $required_fields = ['name', 'term', 'start_date', 'end_date', 'classes', 'academic_year'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || 
            (is_array($data[$field]) && empty($data[$field])) || 
            (!is_array($data[$field]) && empty(trim($data[$field])))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Validate dates
    $start_date = new DateTime($data['start_date']);
    $end_date = new DateTime($data['end_date']);
    
    if ($end_date < $start_date) {
        throw new Exception('End date cannot be before start date');
    }
    
    // Validate term
    if (!in_array($data['term'], ['1', '2', '3'])) {
        throw new Exception('Invalid term');
    }
    
    // Check if exam name already exists for this term and academic year
    $stmt = $conn->prepare("
        SELECT id FROM exams 
        WHERE name = ? AND term = ? AND academic_year = ?
    ");
    $stmt->execute([$data['name'], $data['term'], $data['academic_year']]);
    if ($stmt->fetch()) {
        throw new Exception('An exam with this name already exists for this term');
    }
    
    // Format classes array to comma-separated string
    $classes = implode(',', $data['classes']);
    
    // Add the exam
    $stmt = $conn->prepare("
        INSERT INTO exams (
            name, term, start_date, end_date, classes,
            description, academic_year
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['name'],
        $data['term'],
        $data['start_date'],
        $data['end_date'],
        $classes,
        $data['description'] ?? null,
        $data['academic_year']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Exam added successfully',
        'exam_id' => $conn->lastInsertId()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
