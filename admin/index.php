<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    // Get admin info
    $admin_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM administrators WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        throw new Exception('Admin not found');
    }

    // Get counts with prepared statements
    $counts = [
        'teachers' => 0,
        'students' => 0,
        'parents' => 0,
        'subjects' => 0
    ];

    // Get teachers count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'");
    $stmt->execute();
    $counts['teachers'] = $stmt->fetch()['count'] ?? 0;

    // Get students count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $stmt->execute();
    $counts['students'] = $stmt->fetch()['count'] ?? 0;

    // Get parents count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM parents WHERE status = 'active'");
    $stmt->execute();
    $counts['parents'] = $stmt->fetch()['count'] ?? 0;

    // Get subjects count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM subjects");
    $stmt->execute();
    $counts['subjects'] = $stmt->fetch()['count'] ?? 0;

    // Get recent activities with prepared statement
    $activities_query = "
        SELECT 
            nl.*,
            CASE 
                WHEN nl.notification_type LIKE '%teacher%' THEN CONCAT(t.first_name, ' ', t.last_name)
                WHEN nl.notification_type LIKE '%student%' THEN CONCAT(s.first_name, ' ', s.last_name)
                WHEN nl.notification_type LIKE '%parent%' THEN CONCAT(p.first_name, ' ', p.last_name)
                WHEN nl.notification_type LIKE '%admin%' THEN CONCAT(a.first_name, ' ', a.last_name)
                ELSE 'System'
            END as full_name,
            CASE 
                WHEN nl.notification_type LIKE '%teacher%' THEN 'teacher'
                WHEN nl.notification_type LIKE '%student%' THEN 'student'
                WHEN nl.notification_type LIKE '%parent%' THEN 'parent'
                WHEN nl.notification_type LIKE '%admin%' THEN 'admin'
                ELSE 'system'
            END as role
        FROM notification_logs nl
        LEFT JOIN teachers t ON nl.student_id = t.id AND nl.notification_type LIKE '%teacher%'
        LEFT JOIN students s ON nl.student_id = s.id AND nl.notification_type LIKE '%student%'
        LEFT JOIN parents p ON nl.student_id = p.id AND nl.notification_type LIKE '%parent%'
        LEFT JOIN administrators a ON nl.student_id = a.id AND nl.notification_type LIKE '%admin%'
        ORDER BY nl.created_at DESC 
        LIMIT 5
    ";
    
    $activities = $conn->query($activities_query)->fetchAll();

} catch (PDOException $e) {
    error_log("Database Error in Admin Dashboard: " . $e->getMessage());
    $error = "A database error occurred. Please try again later.";
} catch (Exception $e) {
    error_log("General Error in Admin Dashboard: " . $e->getMessage());
    $error = $e->getMessage();
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
                <div class="alert error">
                    <i class='bx bx-error-circle'></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
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
                        <div class="card-icon" style="background: rgba(247, 37, 133, 0.1);">
                            <i class='bx bxs-group' style="color: var(--warning-color);"></i>
                        </div>
                        <div class="card-title">Total Parents</div>
                        <div class="card-value"><?php echo number_format($counts['parents']); ?></div>
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
                                        <td><?php echo ucfirst($activity['notification_type']); ?></td>
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
