<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Lesson ID is required');
    }
    
    // Check if lesson exists
    $stmt = $conn->prepare("SELECT id FROM timetable WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Lesson not found');
    }
    
    // Delete the lesson
    $stmt = $conn->prepare("DELETE FROM timetable WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Lesson deleted successfully']);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
