<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    $academic_year = $_GET['academic_year'] ?? getAcademicYear();
    $term = $_GET['term'] ?? null;
    
    $query = "
        SELECT * FROM exams 
        WHERE academic_year = ?
    ";
    $params = [$academic_year];
    
    if ($term) {
        $query .= " AND term = ?";
        $params[] = $term;
    }
    
    $query .= " ORDER BY start_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($exams);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
