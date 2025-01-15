<?php
session_start();
require_once '../config/database.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Log logout activity in notification_logs
        $stmt = $conn->prepare("
            INSERT INTO notification_logs (student_id, notification_type, notification_method, status, message)
            VALUES (?, 'logout', 'system', 'sent', ?)
        ");
        $message = "User logged out from IP: " . $_SERVER['REMOTE_ADDR'];
        $stmt->execute([$_SESSION['user_id'], $message]);
    } catch (Exception $e) {
        error_log("Logout Error: " . $e->getMessage());
    }
}

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>
