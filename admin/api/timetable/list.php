<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    $classId = isset($_GET['classId']) ? intval($_GET['classId']) : 0;
    
    if ($classId <= 0) {
        throw new Exception('Invalid class ID');
    }

    $query = "SELECT t.*, s.name as subject_name, tc.first_name, tc.last_name 
              FROM timetable t
              LEFT JOIN subjects s ON t.subject_id = s.id
              LEFT JOIN teachers tc ON t.teacher_id = tc.id
              WHERE t.class_id = ?
              ORDER BY t.day, t.start_time";
              
    $stmt = $conn->prepare($query);
    $stmt->execute([$classId]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the schedule data
    $formattedSchedule = [];
    foreach ($schedule as $slot) {
        $slot['teacher_name'] = $slot['first_name'] . ' ' . $slot['last_name'];
        $slot['start_time'] = date('H:i', strtotime($slot['start_time']));
        $slot['end_time'] = date('H:i', strtotime($slot['end_time']));
        $formattedSchedule[] = $slot;
    }
    
    echo json_encode($formattedSchedule);
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
