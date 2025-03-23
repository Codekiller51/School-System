<?php
session_start();
require_once '../config/database.php';


// Get admin info
    $admin_id = $_SESSION['user_id'];
    
// Check if administrators table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'administrators'");
    if ($table_check->num_rows === 0) {
        throw new Exception('Database table "administrators" not found');
    }
        
    $stmt = $conn->prepare("SELECT * FROM administrators WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare admin query: " . $conn->error);
    }
    $stmt->bind_param('i', $admin_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute admin query: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if (!$admin) {
        throw new Exception('Admin not found with ID: ' . $admin_id);
    }





// Fetch school settings
$query = "SELECT * FROM school_settings LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$schoolSettings = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - School Management System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class='bx bxs-school'></i>
                <span>School System</span>
            </div>
            
            <nav class="nav-links">
                <a href="index.php" class="nav-link">
                    <i class='bx bxs-dashboard'></i>
                    <span>Dashboard</span>
                </a>
                <a href="teachers.php" class="nav-link">
                    <i class='bx bxs-user'></i>
                    <span>Teachers</span>
                </a>
                <a href="students.php" class="nav-link">
                    <i class='bx bxs-graduation'></i>
                    <span>Students</span>
                </a>
                <a href="classes.php" class="nav-link">
                    <i class='bx bxs-school'></i>
                    <span>Classes</span>
                </a>
                <a href="subjects.php" class="nav-link">
                    <i class='bx bxs-book'></i>
                    <span>Subjects</span>
                </a>
                <a href="timetable.php" class="nav-link">
                    <i class='bx bxs-calendar'></i>
                    <span>Timetable</span>
                </a>
                <a href="exams.php" class="nav-link">
                    <i class='bx bxs-notepad'></i>
                    <span>Exams</span>
                </a>
                <a href="settings.php" class="nav-link active">
                    <i class='bx bxs-cog'></i>
                    <span>Settings</span>
                </a>
                <a href="../auth/logout.php" class="nav-link">
                    <i class='bx bxs-log-out'></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <button class="toggle-sidebar">
                    <i class='bx bx-menu'></i>
                </button>
                
                <div class="search-box">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search...">
                </div>
                
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['first_name'] . ' ' . $admin['last_name']); ?>&background=4361ee&color=fff" alt="Profile">
                    <span><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></span>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <!-- Profile Settings -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Profile Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form id="profileForm">
                                        <div class="mb-3">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" name="firstName" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" name="lastName" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Change Password -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form id="passwordForm">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="currentPassword" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="newPassword" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirmPassword" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- School Settings -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">School Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form id="schoolSettingsForm">
                                        <div class="mb-3">
                                            <label class="form-label">School Name</label>
                                            <input type="text" class="form-control" name="schoolName" value="<?php echo htmlspecialchars($schoolSettings['school_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">School Address</label>
                                            <textarea class="form-control" name="schoolAddress" rows="3" required><?php echo htmlspecialchars($schoolSettings['address'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">School Phone</label>
                                            <input type="tel" class="form-control" name="schoolPhone" value="<?php echo htmlspecialchars($schoolSettings['phone'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">School Email</label>
                                            <input type="email" class="form-control" name="schoolEmail" value="<?php echo htmlspecialchars($schoolSettings['email'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Current Academic Year</label>
                                            <input type="text" class="form-control" name="academicYear" value="<?php echo htmlspecialchars($schoolSettings['current_academic_year'] ?? ''); ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Save Settings</button>
                                    </form>
                                </div>
                            </div>

                            <!-- System Preferences -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">System Preferences</h5>
                                </div>
                                <div class="card-body">
                                    <form id="preferencesForm">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enableNotifications" id="enableNotifications" <?php echo ($schoolSettings['enable_notifications'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enableNotifications">Enable Email Notifications</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enableSMS" id="enableSMS" <?php echo ($schoolSettings['enable_sms'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enableSMS">Enable SMS Notifications</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="maintenanceMode" id="maintenanceMode" <?php echo ($schoolSettings['maintenance_mode'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="maintenanceMode">Maintenance Mode</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
