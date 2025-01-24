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
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update classes to remove this teacher
        $stmt = $conn->prepare("UPDATE classes SET teacher_id = NULL WHERE teacher_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Delete teacher's subject assignments
        $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Delete the teacher
        $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
