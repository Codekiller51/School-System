<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get preferences
    $enableNotifications = isset($_POST['enableNotifications']) ? 1 : 0;
    $enableSMS = isset($_POST['enableSMS']) ? 1 : 0;
    $maintenanceMode = isset($_POST['maintenanceMode']) ? 1 : 0;

    // Check if settings exist
    $query = "SELECT COUNT(*) FROM school_settings";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        // Update existing settings
        $query = "UPDATE school_settings SET 
                  enable_notifications = ?, 
                  enable_sms = ?, 
                  maintenance_mode = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $enableNotifications,
            $enableSMS,
            $maintenanceMode
        ]);
    } else {
        // Insert new settings
        $query = "INSERT INTO school_settings 
                  (enable_notifications, enable_sms, maintenance_mode) 
                  VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $enableNotifications,
            $enableSMS,
            $maintenanceMode
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'System preferences updated successfully'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
