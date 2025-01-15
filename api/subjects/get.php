<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Subject ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subject) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit;
    }
    
    // Get assigned teachers for this subject
    $stmt = $conn->prepare("
        SELECT t.id, t.first_name, t.last_name, t.department
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE ts.subject_id = ? AND ts.academic_year = ?
        ORDER BY t.first_name, t.last_name
    ");
    $stmt->execute([$_GET['id'], getAcademicYear()]);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $subject['assigned_teachers'] = $teachers;
    
    echo json_encode(['success' => true, 'data' => $subject]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
