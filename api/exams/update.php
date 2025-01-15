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
        throw new Exception('Exam ID is required');
    }
    
    // Get current exam data
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$data['id']]);
    $currentExam = $stmt->fetch();
    
    if (!$currentExam) {
        throw new Exception('Exam not found');
    }
    
    // Validate required fields
    $required_fields = ['name', 'term', 'start_date', 'end_date', 'classes'];
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
    
    // Check if exam name already exists for this term and academic year (excluding current exam)
    $stmt = $conn->prepare("
        SELECT id FROM exams 
        WHERE name = ? AND term = ? AND academic_year = ? AND id != ?
    ");
    $stmt->execute([
        $data['name'],
        $data['term'],
        $currentExam['academic_year'],
        $data['id']
    ]);
    if ($stmt->fetch()) {
        throw new Exception('An exam with this name already exists for this term');
    }
    
    // Format classes array to comma-separated string
    $classes = implode(',', $data['classes']);
    
    // Update the exam
    $stmt = $conn->prepare("
        UPDATE exams SET
            name = ?,
            term = ?,
            start_date = ?,
            end_date = ?,
            classes = ?,
            description = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['name'],
        $data['term'],
        $data['start_date'],
        $data['end_date'],
        $classes,
        $data['description'] ?? null,
        $data['id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Exam updated successfully']);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
