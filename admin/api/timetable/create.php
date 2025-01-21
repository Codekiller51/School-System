<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $classId = intval($_POST['classId'] ?? 0);
    $subjectId = intval($_POST['subjectId'] ?? 0);
    $teacherId = intval($_POST['teacherId'] ?? 0);
    $day = trim($_POST['day'] ?? '');
    $startTime = trim($_POST['startTime'] ?? '');
    $endTime = trim($_POST['endTime'] ?? '');

    // Validate required fields
    if ($classId <= 0 || $subjectId <= 0 || $teacherId <= 0 || 
        empty($day) || empty($startTime) || empty($endTime)) {
        throw new Exception('All fields are required');
    }

    // Validate day
    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    if (!in_array($day, $validDays)) {
        throw new Exception('Invalid day selected');
    }

    // Validate time format and range
    $startDateTime = strtotime($startTime);
    $endDateTime = strtotime($endTime);
    if ($startDateTime === false || $endDateTime === false) {
        throw new Exception('Invalid time format');
    }
    if ($startDateTime >= $endDateTime) {
        throw new Exception('End time must be after start time');
    }

    // Check for schedule conflicts
    $query = "SELECT COUNT(*) FROM timetable 
              WHERE class_id = ? AND day = ? AND 
              ((start_time BETWEEN ? AND ?) OR 
               (end_time BETWEEN ? AND ?) OR
               (start_time <= ? AND end_time >= ?))";
    $stmt = $conn->prepare($query);
    $stmt->execute([$classId, $day, $startTime, $endTime, $startTime, $endTime, $startTime, $endTime]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Schedule conflict detected for this time slot');
    }

    // Insert new schedule
    $query = "INSERT INTO timetable (class_id, subject_id, teacher_id, day, start_time, end_time) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$classId, $subjectId, $teacherId, $day, $startTime, $endTime]);

    echo json_encode([
        'success' => true,
        'message' => 'Schedule created successfully',
        'id' => $conn->lastInsertId()
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
