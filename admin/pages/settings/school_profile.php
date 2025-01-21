<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update school profile settings
        $stmt = $conn->prepare("
            INSERT INTO school_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        $settings = [
            'school_name' => $_POST['school_name'],
            'school_address' => $_POST['school_address'],
            'school_phone' => $_POST['school_phone'],
            'school_email' => $_POST['school_email'],
            'school_website' => $_POST['school_website'],
            'academic_year' => $_POST['academic_year']
        ];

        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        setFlashMessage('success', 'School profile updated successfully!');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error updating school profile: ' . $e->getMessage());
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get current settings
$settings = [];
try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM school_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching settings: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Profile Settings - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar close">
        <!-- Sidebar content will be loaded via JavaScript -->
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">School Profile Settings</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header">
                    <h4 class="mb-0">School Profile Settings</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="school_name" class="form-label">School Name</label>
                                <input type="text" class="form-control" id="school_name" name="school_name" 
                                       value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="school_email" class="form-label">School Email</label>
                                <input type="email" class="form-control" id="school_email" name="school_email" 
                                       value="<?php echo htmlspecialchars($settings['school_email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="school_phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="school_phone" name="school_phone" 
                                       value="<?php echo htmlspecialchars($settings['school_phone'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="school_website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="school_website" name="school_website" 
                                       value="<?php echo htmlspecialchars($settings['school_website'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="school_address" class="form-label">School Address</label>
                            <textarea class="form-control" id="school_address" name="school_address" rows="3" required><?php 
                                echo htmlspecialchars($settings['school_address'] ?? ''); 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Current Academic Year</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                   value="<?php echo htmlspecialchars($settings['academic_year'] ?? ''); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/script.js"></script>
</body>
</html>
