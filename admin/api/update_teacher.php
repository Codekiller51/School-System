<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $teacher_id = $_POST['teacher_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $subject_specialization = $_POST['subject_specialization'] ?? null;
    $qualification = $_POST['qualification'] ?? null;
    $experience_years = $_POST['experience_years'] ?? null;
    $joining_date = $_POST['joining_date'] ?? null;
    $status = $_POST['status'] ?? 'Active';

    // Check if email exists for another teacher
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $teacher_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }

    // Update password only if provided
    $passwordUpdate = "";
    $types = "ssssssssssssi"; // Default types without password
    $params = [
        $first_name, $last_name, $email, $phone, $address,
        $date_of_birth, $gender, $subject_specialization, $qualification,
        $experience_years, $joining_date, $status, $teacher_id
    ];

    if (!empty($_POST['password'])) {
        $passwordUpdate = ", password = ?";
        $types = "sssssssssssssi"; // Add 's' for password
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        array_splice($params, -1, 0, [$password]); // Insert password before teacher_id
    }

    $stmt = $conn->prepare("
        UPDATE teachers SET
            first_name = ?,
            last_name = ?,
            email = ?,
            phone = ?,
            address = ?,
            date_of_birth = ?,
            gender = ?,
            subject_specialization = ?,
            qualification = ?,
            experience_years = ?,
            joining_date = ?,
            status = ?
            $passwordUpdate
        WHERE id = ?
    ");

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
