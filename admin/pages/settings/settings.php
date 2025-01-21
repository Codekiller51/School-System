<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update settings
        $stmt = $conn->prepare("
            INSERT INTO school_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        $settings = [
            // General Settings
            'school_name' => $_POST['school_name'],
            'school_address' => $_POST['school_address'],
            'school_phone' => $_POST['school_phone'],
            'school_email' => $_POST['school_email'],
            'school_website' => $_POST['school_website'],
            
            // Academic Settings
            'current_academic_year' => $_POST['current_academic_year'],
            'academic_terms' => $_POST['academic_terms'],
            'class_duration' => $_POST['class_duration'],
            'attendance_threshold' => $_POST['attendance_threshold'],
            
            // System Settings
            'timezone' => $_POST['timezone'],
            'date_format' => $_POST['date_format'],
            'time_format' => $_POST['time_format'],
            'language' => $_POST['language'],
            
            // Notification Settings
            'enable_email_notifications' => $_POST['enable_email_notifications'] ?? '0',
            'enable_sms_notifications' => $_POST['enable_sms_notifications'] ?? '0',
            'notification_email' => $_POST['notification_email'],
            
            // Grade Settings
            'grade_scale' => $_POST['grade_scale'],
            'passing_grade' => $_POST['passing_grade']
        ];

        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        setFlashMessage('success', 'Settings updated successfully!');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error updating settings: ' . $e->getMessage());
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

// Default values for settings
$defaults = [
    'class_duration' => '40',
    'attendance_threshold' => '75',
    'timezone' => 'UTC',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
    'language' => 'en',
    'grade_scale' => 'letter',
    'passing_grade' => '50'
];

// Merge defaults with actual settings
$settings = array_merge($defaults, $settings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - School Management System</title>
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
            <span class="text">System Settings</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header">
                    <h4 class="mb-0">System Settings</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <!-- General Settings -->
                        <h5 class="border-bottom pb-2 mb-4">General Settings</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="school_name" class="form-label">School Name</label>
                                    <input type="text" class="form-control" id="school_name" name="school_name" 
                                           value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="school_email" class="form-label">School Email</label>
                                    <input type="email" class="form-control" id="school_email" name="school_email" 
                                           value="<?php echo htmlspecialchars($settings['school_email'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="school_phone" class="form-label">School Phone</label>
                                    <input type="tel" class="form-control" id="school_phone" name="school_phone" 
                                           value="<?php echo htmlspecialchars($settings['school_phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="school_address" class="form-label">School Address</label>
                                    <textarea class="form-control" id="school_address" name="school_address" rows="3" required><?php 
                                        echo htmlspecialchars($settings['school_address'] ?? ''); 
                                    ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="school_website" class="form-label">School Website</label>
                                    <input type="url" class="form-control" id="school_website" name="school_website" 
                                           value="<?php echo htmlspecialchars($settings['school_website'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Academic Settings -->
                        <h5 class="border-bottom pb-2 mb-4">Academic Settings</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="current_academic_year" class="form-label">Current Academic Year</label>
                                    <input type="text" class="form-control" id="current_academic_year" name="current_academic_year" 
                                           value="<?php echo htmlspecialchars($settings['current_academic_year'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="academic_terms" class="form-label">Academic Terms</label>
                                    <input type="number" class="form-control" id="academic_terms" name="academic_terms" 
                                           value="<?php echo htmlspecialchars($settings['academic_terms'] ?? '3'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="class_duration" class="form-label">Class Duration (minutes)</label>
                                    <input type="number" class="form-control" id="class_duration" name="class_duration" 
                                           value="<?php echo htmlspecialchars($settings['class_duration']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="attendance_threshold" class="form-label">Attendance Threshold (%)</label>
                                    <input type="number" class="form-control" id="attendance_threshold" name="attendance_threshold" 
                                           value="<?php echo htmlspecialchars($settings['attendance_threshold']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings -->
                        <h5 class="border-bottom pb-2 mb-4">System Settings</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers();
                                        foreach ($timezones as $tz) {
                                            $selected = ($tz === $settings['timezone']) ? 'selected' : '';
                                            echo "<option value=\"$tz\" $selected>$tz</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="date_format" class="form-label">Date Format</label>
                                    <select class="form-select" id="date_format" name="date_format">
                                        <option value="Y-m-d" <?php echo $settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>
                                            YYYY-MM-DD
                                        </option>
                                        <option value="d/m/Y" <?php echo $settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>
                                            DD/MM/YYYY
                                        </option>
                                        <option value="m/d/Y" <?php echo $settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>
                                            MM/DD/YYYY
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="time_format" class="form-label">Time Format</label>
                                    <select class="form-select" id="time_format" name="time_format">
                                        <option value="H:i" <?php echo $settings['time_format'] === 'H:i' ? 'selected' : ''; ?>>
                                            24 Hour (14:30)
                                        </option>
                                        <option value="h:i A" <?php echo $settings['time_format'] === 'h:i A' ? 'selected' : ''; ?>>
                                            12 Hour (02:30 PM)
                                        </option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="language" class="form-label">System Language</label>
                                    <select class="form-select" id="language" name="language">
                                        <option value="en" <?php echo $settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                        <option value="es" <?php echo $settings['language'] === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                        <option value="fr" <?php echo $settings['language'] === 'fr' ? 'selected' : ''; ?>>French</option>
                                        <option value="ar" <?php echo $settings['language'] === 'ar' ? 'selected' : ''; ?>>Arabic</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <h5 class="border-bottom pb-2 mb-4">Notification Settings</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_email_notifications" 
                                               name="enable_email_notifications" value="1" 
                                               <?php echo ($settings['enable_email_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_email_notifications">
                                            Enable Email Notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_sms_notifications" 
                                               name="enable_sms_notifications" value="1"
                                               <?php echo ($settings['enable_sms_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_sms_notifications">
                                            Enable SMS Notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notification_email" class="form-label">Notification Email</label>
                                    <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                           value="<?php echo htmlspecialchars($settings['notification_email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Grade Settings -->
                        <h5 class="border-bottom pb-2 mb-4">Grade Settings</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="grade_scale" class="form-label">Grade Scale</label>
                                    <select class="form-select" id="grade_scale" name="grade_scale">
                                        <option value="letter" <?php echo $settings['grade_scale'] === 'letter' ? 'selected' : ''; ?>>
                                            Letter Grade (A, B, C, D, F)
                                        </option>
                                        <option value="number" <?php echo $settings['grade_scale'] === 'number' ? 'selected' : ''; ?>>
                                            Number Grade (1-100)
                                        </option>
                                        <option value="gpa" <?php echo $settings['grade_scale'] === 'gpa' ? 'selected' : ''; ?>>
                                            GPA Scale (0.0-4.0)
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="passing_grade" class="form-label">Passing Grade (%)</label>
                                    <input type="number" class="form-control" id="passing_grade" name="passing_grade" 
                                           value="<?php echo htmlspecialchars($settings['passing_grade']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
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
