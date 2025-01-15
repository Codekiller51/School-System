<?php
require_once 'config/database.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationSystem {
    private $conn;
    private $mail;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->initializeMailer();
    }
    
    private function initializeMailer() {
        $this->mail = new PHPMailer(true);
        
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = $_ENV['SMTP_HOST'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $_ENV['SMTP_USERNAME'];
        $this->mail->Password = $_ENV['SMTP_PASSWORD'];
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = $_ENV['SMTP_PORT'];
        
        // Default sender
        $this->mail->setFrom($_ENV['SCHOOL_EMAIL'], $_ENV['SCHOOL_NAME']);
    }
    
    public function sendAbsenceNotification($studentId, $date) {
        try {
            // Get student and parent details
            $stmt = $this->conn->prepare("
                SELECT 
                    s.first_name as student_first_name,
                    s.last_name as student_last_name,
                    s.class_level,
                    s.section,
                    p.email as parent_email,
                    p.first_name as parent_first_name,
                    a.reason
                FROM students s
                JOIN parents p ON p.id = s.parent_id
                JOIN attendance a ON a.student_id = s.id
                WHERE s.id = ? AND a.date = ?
            ");
            $stmt->execute([$studentId, $date]);
            $data = $stmt->fetch();
            
            if (!$data || !$data['parent_email']) {
                throw new Exception("Parent email not found for student ID: $studentId");
            }
            
            // Prepare email content
            $subject = "Absence Notification - {$data['student_first_name']} {$data['student_last_name']}";
            $body = $this->getAbsenceEmailTemplate($data);
            
            // Send email
            $this->mail->clearAddresses();
            $this->mail->addAddress($data['parent_email']);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            
            $this->mail->send();
            
            // Log notification
            $this->logNotification($studentId, 'absence', 'email', 'sent');
            
            return true;
        } catch (Exception $e) {
            $this->logNotification($studentId, 'absence', 'email', 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    public function sendExamResultNotification($studentId, $examId) {
        try {
            // Get exam results and parent details
            $stmt = $this->conn->prepare("
                SELECT 
                    s.first_name as student_first_name,
                    s.last_name as student_last_name,
                    s.class_level,
                    s.section,
                    p.email as parent_email,
                    p.first_name as parent_first_name,
                    e.name as exam_name,
                    GROUP_CONCAT(
                        CONCAT(
                            sub.name, ': ', 
                            er.marks, '/', 
                            e.max_marks,
                            CASE 
                                WHEN er.marks >= e.pass_marks THEN ' (Passed)'
                                ELSE ' (Failed)'
                            END
                        )
                        ORDER BY sub.name
                        SEPARATOR '\n'
                    ) as results
                FROM students s
                JOIN parents p ON p.id = s.parent_id
                JOIN exam_results er ON er.student_id = s.id
                JOIN exams e ON e.id = er.exam_id
                JOIN subjects sub ON sub.id = er.subject_id
                WHERE s.id = ? AND e.id = ?
                GROUP BY s.id
            ");
            $stmt->execute([$studentId, $examId]);
            $data = $stmt->fetch();
            
            if (!$data || !$data['parent_email']) {
                throw new Exception("Parent email not found for student ID: $studentId");
            }
            
            // Prepare email content
            $subject = "Exam Results - {$data['exam_name']} - {$data['student_first_name']} {$data['student_last_name']}";
            $body = $this->getExamResultEmailTemplate($data);
            
            // Send email
            $this->mail->clearAddresses();
            $this->mail->addAddress($data['parent_email']);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            
            $this->mail->send();
            
            // Log notification
            $this->logNotification($studentId, 'exam_result', 'email', 'sent');
            
            return true;
        } catch (Exception $e) {
            $this->logNotification($studentId, 'exam_result', 'email', 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    private function getAbsenceEmailTemplate($data) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Student Absence Notification</h2>
                <p>Dear {$data['parent_first_name']},</p>
                
                <p>This is to inform you that your child, {$data['student_first_name']} {$data['student_last_name']}, 
                was marked absent today in Class {$data['class_level']}-{$data['section']}.</p>
                
                " . ($data['reason'] ? "<p>Reason provided: {$data['reason']}</p>" : "") . "
                
                <p>If you have not already informed the school about this absence, please contact us.</p>
                
                <p>Best regards,<br>{$_ENV['SCHOOL_NAME']}</p>
            </body>
            </html>
        ";
    }
    
    private function getExamResultEmailTemplate($data) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Exam Results Notification</h2>
                <p>Dear {$data['parent_first_name']},</p>
                
                <p>The results for {$data['exam_name']} have been published for your child, 
                {$data['student_first_name']} {$data['student_last_name']} of Class {$data['class_level']}-{$data['section']}.</p>
                
                <h3>Results:</h3>
                <pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px;'>
{$data['results']}
                </pre>
                
                <p>You can view the detailed report card by logging into the parent portal.</p>
                
                <p>Best regards,<br>{$_ENV['SCHOOL_NAME']}</p>
            </body>
            </html>
        ";
    }
    
    private function logNotification($studentId, $type, $method, $status, $error = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO notification_logs (
                student_id, notification_type, 
                notification_method, status, error_message
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$studentId, $type, $method, $status, $error]);
    }
    
    public function processNotificationQueue() {
        try {
            // Get pending notifications
            $stmt = $this->conn->prepare("
                SELECT * FROM notification_queue
                WHERE status = 'pending'
                ORDER BY created_at ASC
                LIMIT 50
            ");
            $stmt->execute();
            $notifications = $stmt->fetchAll();
            
            foreach ($notifications as $notification) {
                try {
                    switch ($notification['type']) {
                        case 'absence':
                            $this->sendAbsenceNotification(
                                $notification['student_id'],
                                $notification['reference_date']
                            );
                            break;
                            
                        case 'exam_result':
                            $this->sendExamResultNotification(
                                $notification['student_id'],
                                $notification['reference_id']
                            );
                            break;
                    }
                    
                    // Mark as processed
                    $this->updateNotificationStatus(
                        $notification['id'],
                        'processed'
                    );
                } catch (Exception $e) {
                    // Mark as failed
                    $this->updateNotificationStatus(
                        $notification['id'],
                        'failed',
                        $e->getMessage()
                    );
                }
            }
        } catch (Exception $e) {
            error_log("Error processing notification queue: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function updateNotificationStatus($id, $status, $error = null) {
        $stmt = $this->conn->prepare("
            UPDATE notification_queue
            SET status = ?, 
                error_message = ?,
                processed_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$status, $error, $id]);
    }
}
