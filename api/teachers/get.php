<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Teacher ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $teacher]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
