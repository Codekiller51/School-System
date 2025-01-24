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
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $subject_specialization = $_POST['subject_specialization'] ?? null;
    $qualification = $_POST['qualification'] ?? null;
    $experience_years = $_POST['experience_years'] ?? null;
    $joining_date = $_POST['joining_date'] ?? null;
    $status = $_POST['status'] ?? 'Active';

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO teachers (
            first_name, last_name, email, password, phone, address,
            date_of_birth, gender, subject_specialization, qualification,
            experience_years, joining_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssssssssss",
        $first_name, $last_name, $email, $password, $phone, $address,
        $date_of_birth, $gender, $subject_specialization, $qualification,
        $experience_years, $joining_date, $status
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
