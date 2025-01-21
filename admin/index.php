<?php
session_start();
require_once('../config/database.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrator') {
    header('Location: ../auth/login.php');
    exit();
}

try {
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

    // Initialize counts array
    $counts = [
        'teachers' => 0,
        'students' => 0,
        'subjects' => 0,
        'classes' => 0
    ];

    // Function to safely get count from table
    function getTableCount($conn, $table, $status = true) {
        $count = 0;
        try {
            // Check if table exists
            $table_check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($table_check->num_rows === 0) {
                error_log("Table '$table' does not exist");
                return 0;
            }
            
            $query = $status ? 
                "SELECT COUNT(*) as count FROM $table WHERE status = 'Active'" :
                "SELECT COUNT(*) as count FROM $table";
                
            $result = $conn->query($query);
            if ($result) {
                $count = $result->fetch_assoc()['count'] ?? 0;
            } else {
                error_log("Error getting count from $table: " . $conn->error);
            }
        } catch (Exception $e) {
            error_log("Error in getTableCount for $table: " . $e->getMessage());
        }
        return $count;
    }

    // Get counts safely
    $counts['teachers'] = getTableCount($conn, 'teachers');
    $counts['students'] = getTableCount($conn, 'students');
    $counts['subjects'] = getTableCount($conn, 'subjects');
    $counts['classes'] = getTableCount($conn, 'classes');

    // Get recent activities
    $activities = [];
    try {
        // Check if notification_logs table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'notification_logs'");
        if ($table_check->num_rows > 0) {
            $stmt = $conn->prepare("
                SELECT 
                    nl.*,
                    CASE 
                        WHEN nl.user_type = 'teacher' THEN CONCAT(t.first_name, ' ', t.last_name)
                        WHEN nl.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                        WHEN nl.user_type = 'administrator' THEN CONCAT(a.first_name, ' ', a.last_name)
                        ELSE 'System'
                    END as full_name,
                    nl.user_type as role
                FROM notification_logs nl
                LEFT JOIN teachers t ON nl.user_id = t.id AND nl.user_type = 'teacher'
                LEFT JOIN students s ON nl.user_id = s.id AND nl.user_type = 'student'
                LEFT JOIN administrators a ON nl.user_id = a.id AND nl.user_type = 'administrator'
                WHERE nl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY nl.created_at DESC 
                LIMIT 5
            ");
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $activities[] = $row;
                }
            } else {
                error_log("Failed to execute activities query: " . $stmt->error);
            }
        } else {
            error_log("Table 'notification_logs' does not exist");
        }
    } catch (Exception $e) {
        error_log("Error fetching activities: " . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Error in Admin Dashboard: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Management System</title>
    
    <!-- CSS -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <a href="index.php" class="nav-link active">
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
                <a href="settings.php" class="nav-link">
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

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="margin: 20px; padding: 15px; border-radius: 5px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;">
                    <i class='bx bx-error-circle'></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($error)): ?>
                <!-- Dashboard Cards -->
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon" style="background: rgba(67, 97, 238, 0.1);">
                            <i class='bx bxs-user' style="color: var(--primary-color);"></i>
                        </div>
                        <div class="card-title">Total Teachers</div>
                        <div class="card-value"><?php echo number_format($counts['teachers']); ?></div>
                        <div class="card-change positive">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span>Active</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-icon" style="background: rgba(76, 201, 240, 0.1);">
                            <i class='bx bxs-graduation' style="color: var(--success-color);"></i>
                        </div>
                        <div class="card-title">Total Students</div>
                        <div class="card-value"><?php echo number_format($counts['students']); ?></div>
                        <div class="card-change positive">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span>Active</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-icon" style="background: rgba(63, 55, 201, 0.1);">
                            <i class='bx bxs-book' style="color: var(--secondary-color);"></i>
                        </div>
                        <div class="card-title">Total Subjects</div>
                        <div class="card-value"><?php echo number_format($counts['subjects']); ?></div>
                        <div class="card-change positive">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span>Active</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-icon" style="background: rgba(255, 159, 67, 0.1);">
                            <i class='bx bxs-school' style="color: #ff9f43;"></i>
                        </div>
                        <div class="card-title">Total Classes</div>
                        <div class="card-value"><?php echo number_format($counts['classes']); ?></div>
                        <div class="card-change positive">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span>Active</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">Recent Activities</h2>
                    </div>
                    
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">
                            <i class='bx bx-calendar-x'></i>
                            <p>No recent activities</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Activity</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($activity['full_name']); ?>&size=32" alt="">
                                                <span><?php echo htmlspecialchars($activity['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo ucfirst($activity['role']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['message']); ?></td>
                                        <td><?php echo date('M d, h:i A', strtotime($activity['created_at'])); ?></td>
                                        <td><span class="status success">Success</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add overlay div for mobile -->
    <div class="overlay"></div>

    <script>
        // DOM Elements
        const toggleBtn = document.querySelector('.toggle-sidebar');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const overlay = document.querySelector('.overlay');

        // Toggle Sidebar
        function toggleSidebar() {
            const isMobile = window.innerWidth <= 576;
            
            if (isMobile) {
                sidebar.classList.toggle('collapsed');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('collapsed') ? 'hidden' : '';
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('collapsed');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        toggleBtn.addEventListener('click', toggleSidebar);

        // Responsive Sidebar
        function handleResize() {
            const width = window.innerWidth;
            
            if (width <= 576) {
                // Mobile view
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            } else if (width <= 992) {
                // Tablet view - auto collapse sidebar
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            } else {
                // Desktop view - expanded sidebar
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            }
        }

        // Initial check and event listener for window resize
        handleResize();
        window.addEventListener('resize', handleResize);

        // Search Functionality
        const searchInput = document.querySelector('.search-box input');
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                const searchTerm = e.target.value.trim();
                if (searchTerm) {
                    window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
                }
            }
        });
    </script>
</body>
</html>
