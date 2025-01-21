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
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email)) {
        throw new Exception('Required fields cannot be empty');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email is already taken by another user
    $query = "SELECT id FROM administrators WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$email, $adminId]);
    if ($stmt->fetch()) {
        throw new Exception('Email is already in use');
    }

    // Update profile
    $query = "UPDATE administrators SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$firstName, $lastName, $email, $phone, $adminId]);

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
