<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $teacherId = $_SESSION['user_id'];
    $date = $_POST['date'];
    $classLevel = $_POST['class_level'];
    $section = $_POST['section'];
    $statuses = $_POST['status'];
    $reasons = $_POST['reason'] ?? [];
    $attendanceIds = $_POST['attendance_ids'] ?? [];

    // Verify teacher has access to this class
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM teacher_subjects 
        WHERE teacher_id = ? AND class_level = ? AND section = ?
    ");
    $stmt->execute([$teacherId, $classLevel, $section]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('You do not have permission to take attendance for this class');
    }

    // Begin transaction
    $conn->beginTransaction();

    // Prepare statements
    $insertStmt = $conn->prepare("
        INSERT INTO attendance (
            student_id, class_level, section, date, 
            status, reason, recorded_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $updateStmt = $conn->prepare("
        UPDATE attendance 
        SET status = ?, reason = ?
        WHERE id = ?
    ");

    // Process each student's attendance
    foreach ($statuses as $studentId => $status) {
        $reason = $reasons[$studentId] ?? null;
        
        if (isset($attendanceIds[$studentId])) {
            // Update existing attendance
            $updateStmt->execute([
                $status,
                $reason,
                $attendanceIds[$studentId]
            ]);
        } else {
            // Insert new attendance
            $insertStmt->execute([
                $studentId,
                $classLevel,
                $section,
                $date,
                $status,
                $reason,
                $teacherId
            ]);
        }
    }

    // Commit transaction
    $conn->commit();

    // Send notifications for absent students
    foreach ($statuses as $studentId => $status) {
        if ($status === 'absent') {
            $stmt = $conn->prepare("
                INSERT INTO attendance_notifications (
                    attendance_id, notification_type, status
                ) VALUES (
                    LAST_INSERT_ID(), 
                    'email', 
                    'pending'
                )
            ");
            $stmt->execute();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Attendance saved successfully']);
} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
