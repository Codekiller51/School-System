<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $adminId = $_SESSION['admin_id'];
    
    // Validate input
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('All fields are required');
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception('New passwords do not match');
    }

    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Verify current password
    $query = "SELECT password FROM administrators WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password'])) {
        throw new Exception('Current password is incorrect');
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $query = "UPDATE administrators SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$hashedPassword, $adminId]);

    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
