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
    $title = $_POST['title'];
    $subjectId = $_POST['subject_id'];
    $dueDate = $_POST['due_date'];
    $maxMarks = $_POST['max_marks'];
    $description = $_POST['description'];

    // Verify teacher has access to this subject
    $stmt = $conn->prepare("
        SELECT class_level, section 
        FROM teacher_subjects 
        WHERE teacher_id = ? AND subject_id = ?
    ");
    $stmt->execute([$teacherId, $subjectId]);
    $classInfo = $stmt->fetch();

    if (!$classInfo) {
        throw new Exception('You do not have permission to create assignments for this subject');
    }

    // Handle file upload
    $attachmentPath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/assignments/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['attachment']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            $attachmentPath = 'uploads/assignments/' . $fileName;
        }
    }

    // Create assignment
    $stmt = $conn->prepare("
        INSERT INTO assignments (
            teacher_id, subject_id, class_level, 
            section, title, description, 
            due_date, max_marks, attachment_path,
            status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, 'active', 
            CURRENT_TIMESTAMP
        )
    ");
    $stmt->execute([
        $teacherId,
        $subjectId,
        $classInfo['class_level'],
        $classInfo['section'],
        $title,
        $description,
        $dueDate,
        $maxMarks,
        $attachmentPath
    ]);

    $assignmentId = $conn->lastInsertId();

    // Notify students about new assignment
    $stmt = $conn->prepare("
        INSERT INTO notification_queue (
            student_id, type, reference_id, status
        )
        SELECT 
            s.id, 'assignment', ?, 'pending'
        FROM students s
        WHERE s.class_level = ? AND s.section = ?
    ");
    $stmt->execute([
        $assignmentId,
        $classInfo['class_level'],
        $classInfo['section']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Assignment created successfully',
        'id' => $assignmentId
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
