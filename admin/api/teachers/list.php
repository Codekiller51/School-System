<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    $query = "
        SELECT 
            id,
            first_name,
            last_name,
            email,
            phone,
            subject_specialization,
            qualification,
            experience_years,
            status
        FROM teachers 
        WHERE status = 'Active' 
        ORDER BY first_name, last_name
    ";
    
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            'id' => (int)$row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'subject_specialization' => $row['subject_specialization'],
            'qualification' => $row['qualification'],
            'experience_years' => (int)$row['experience_years'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $teachers
    ]);

} catch (Exception $e) {
    error_log("Error in Teachers List API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching teachers list'
    ]);
}
