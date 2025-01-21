<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $examName = trim($_POST['examName'] ?? '');
    $subjectId = intval($_POST['subjectId'] ?? 0);
    $classId = intval($_POST['classId'] ?? 0);
    $examDate = trim($_POST['examDate'] ?? '');
    $startTime = trim($_POST['startTime'] ?? '');
    $duration = intval($_POST['duration'] ?? 0);
    $maxMarks = intval($_POST['maxMarks'] ?? 0);
    $passingMarks = intval($_POST['passingMarks'] ?? 0);

    // Validate required fields
    if (empty($examName) || $subjectId <= 0 || $classId <= 0 || 
        empty($examDate) || empty($startTime) || $duration <= 0 || 
        $maxMarks <= 0 || $passingMarks <= 0) {
        throw new Exception('All fields are required and must be valid');
    }

    // Validate passing marks not greater than max marks
    if ($passingMarks > $maxMarks) {
        throw new Exception('Passing marks cannot be greater than maximum marks');
    }

    // Insert new exam
    $query = "INSERT INTO exams (exam_name, subject_id, class_id, exam_date, start_time, 
              duration, max_marks, passing_marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $examName, $subjectId, $classId, $examDate, $startTime, 
        $duration, $maxMarks, $passingMarks
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Exam created successfully',
        'id' => $conn->lastInsertId()
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
