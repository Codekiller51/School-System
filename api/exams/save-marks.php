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
    $examId = $_POST['exam_id'];
    $subjectId = $_POST['subject_id'];
    $marks = $_POST['marks'];
    $remarks = $_POST['remarks'] ?? [];
    $resultIds = $_POST['result_ids'] ?? [];

    // Verify teacher has access to this subject
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM teacher_subjects 
        WHERE teacher_id = ? AND subject_id = ?
    ");
    $stmt->execute([$teacherId, $subjectId]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('You do not have permission to record marks for this subject');
    }

    // Get exam details for validation
    $stmt = $conn->prepare("SELECT max_marks FROM exams WHERE id = ?");
    $stmt->execute([$examId]);
    $examDetails = $stmt->fetch();
    
    if (!$examDetails) {
        throw new Exception('Invalid exam');
    }

    // Begin transaction
    $conn->beginTransaction();

    // Prepare statements
    $insertStmt = $conn->prepare("
        INSERT INTO exam_results (
            exam_id, subject_id, student_id, 
            marks, remarks, recorded_by
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $updateStmt = $conn->prepare("
        UPDATE exam_results 
        SET marks = ?, remarks = ?
        WHERE id = ?
    ");

    // Process each student's marks
    foreach ($marks as $studentId => $mark) {
        // Validate marks
        if ($mark > $examDetails['max_marks']) {
            throw new Exception("Marks cannot exceed maximum marks ({$examDetails['max_marks']})");
        }

        $remark = $remarks[$studentId] ?? null;
        
        if (isset($resultIds[$studentId])) {
            // Update existing result
            $updateStmt->execute([
                $mark,
                $remark,
                $resultIds[$studentId]
            ]);
        } else {
            // Insert new result
            $insertStmt->execute([
                $examId,
                $subjectId,
                $studentId,
                $mark,
                $remark,
                $teacherId
            ]);
        }
    }

    // Update exam status if all marks are recorded
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT s.id) as total_students,
               COUNT(DISTINCT er.student_id) as marked_students
        FROM students s
        JOIN exam_classes ec ON ec.class_level = s.class_level AND ec.section = s.section
        LEFT JOIN exam_results er ON er.student_id = s.id AND er.exam_id = ?
        WHERE ec.exam_id = ?
    ");
    $stmt->execute([$examId, $examId]);
    $stats = $stmt->fetch();

    if ($stats['total_students'] === $stats['marked_students']) {
        $stmt = $conn->prepare("UPDATE exams SET status = 'completed' WHERE id = ?");
        $stmt->execute([$examId]);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Marks saved successfully']);
} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
