<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $data = $_POST;
    
    // Generate employee ID
    $year = date('Y');
    $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(employee_id, 6) AS UNSIGNED)) as max_id FROM teachers WHERE employee_id LIKE 'TCH-{$year}%'");
    $result = $stmt->fetch();
    $nextId = ($result['max_id'] ?? 0) + 1;
    $employeeId = sprintf("TCH-%d%04d", $year, $nextId);
    
    $stmt = $conn->prepare("
        INSERT INTO teachers (
            employee_id, first_name, last_name, date_of_birth, gender,
            department, joining_date, qualification, phone, email, address
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $employeeId,
        $data['first_name'],
        $data['last_name'],
        $data['date_of_birth'],
        $data['gender'],
        $data['department'],
        $data['joining_date'],
        $data['qualification'],
        $data['phone'],
        $data['email'],
        $data['address']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Teacher added successfully',
        'teacher_id' => $conn->lastInsertId()
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
