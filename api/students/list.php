<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $stmt = $conn->prepare("SELECT * FROM students");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($students);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
