<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $data = $_POST;
    
    $stmt = $conn->prepare("INSERT INTO students (name, class, section, parent_name, contact) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['fullName'],
        $data['class'],
        $data['section'],
        $data['parentName'],
        $data['contact']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Student added successfully']);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
