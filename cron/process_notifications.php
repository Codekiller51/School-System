<?php
require_once '../config/database.php';
require_once '../includes/notifications.php';

try {
    // Initialize notification system
    $notificationSystem = new NotificationSystem($conn);
    
    // Process pending notifications
    $notificationSystem->processNotificationQueue();
    
    // Queue new absence notifications
    $stmt = $conn->prepare("
        INSERT INTO notification_queue (
            student_id, type, reference_date, status
        )
        SELECT DISTINCT 
            a.student_id,
            'absence',
            a.date,
            'pending'
        FROM attendance a
        LEFT JOIN notification_queue nq ON 
            nq.student_id = a.student_id AND 
            nq.reference_date = a.date AND 
            nq.type = 'absence'
        WHERE a.status = 'absent'
        AND a.date = CURRENT_DATE
        AND nq.id IS NULL
    ");
    $stmt->execute();
    
    // Queue new exam result notifications
    $stmt = $conn->prepare("
        INSERT INTO notification_queue (
            student_id, type, reference_id, status
        )
        SELECT DISTINCT 
            er.student_id,
            'exam_result',
            e.id,
            'pending'
        FROM exam_results er
        JOIN exams e ON e.id = er.exam_id
        LEFT JOIN notification_queue nq ON 
            nq.student_id = er.student_id AND 
            nq.reference_id = e.id AND 
            nq.type = 'exam_result'
        WHERE e.status = 'completed'
        AND e.results_published = 1
        AND nq.id IS NULL
    ");
    $stmt->execute();
    
    echo "Notification processing completed successfully\n";
} catch (Exception $e) {
    echo "Error processing notifications: " . $e->getMessage() . "\n";
    exit(1);
}
