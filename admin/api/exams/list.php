<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    $query = "SELECT e.*, s.name as subject_name, c.name as class_name 
              FROM exams e
              LEFT JOIN subjects s ON e.subject_id = s.id
              LEFT JOIN classes c ON e.class_id = c.id
              ORDER BY e.exam_date DESC, e.start_time ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and times for display
    foreach ($exams as &$exam) {
        $exam['exam_date'] = date('Y-m-d', strtotime($exam['exam_date']));
        $exam['start_time'] = date('H:i', strtotime($exam['start_time']));
        $exam['status'] = determineExamStatus($exam['exam_date'], $exam['start_time'], $exam['duration']);
    }
    
    echo json_encode($exams);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

function determineExamStatus($examDate, $startTime, $duration) {
    $examDateTime = strtotime("$examDate $startTime");
    $examEndTime = $examDateTime + ($duration * 60);
    $currentTime = time();
    
    if ($currentTime < $examDateTime) {
        return 'Upcoming';
    } elseif ($currentTime >= $examDateTime && $currentTime <= $examEndTime) {
        return 'In Progress';
    } else {
        return 'Completed';
    }
}
