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
        throw new Exception('Teacher ID is required');
    }
    
    $stmt = $conn->prepare("
        UPDATE teachers SET 
            first_name = ?,
            last_name = ?,
            date_of_birth = ?,
            gender = ?,
            department = ?,
            joining_date = ?,
            qualification = ?,
            phone = ?,
            email = ?,
            address = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['date_of_birth'],
        $data['gender'],
        $data['department'],
        $data['joining_date'],
        $data['qualification'],
        $data['phone'],
        $data['email'],
        $data['address'],
        $data['id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
