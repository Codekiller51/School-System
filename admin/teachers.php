<?php
session_start();
require_once('../config/database.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is an administrator
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
    
    // Pagination settings
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Search functionality
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchCondition = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $searchCondition = " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        $types = 'ssss';
    }
    
    // Get total number of teachers
    $countQuery = "SELECT COUNT(*) as total FROM teachers WHERE 1=1" . $searchCondition;
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalTeachers = $stmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalTeachers / $limit);
    
    // Get teachers with pagination
    $query = "
        SELECT 
            id,
            first_name,
            last_name,
            email,
            phone,
            subject_specialization,
            qualification,
            experience_years,
            status,
            created_at
        FROM teachers 
        WHERE 1=1" . $searchCondition . "
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types . 'ii', ...[...$params, $limit, $offset]);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    
    // Get teacher statistics
    $statsQuery = "
        SELECT 
            status,
            COUNT(*) as count,
            subject_specialization
        FROM teachers 
        GROUP BY status, subject_specialization
    ";
    $statsResult = $conn->query($statsQuery);
    $statistics = [
        'total' => $totalTeachers,
        'active' => 0,
        'inactive' => 0,
        'departments' => []
    ];
    
    while ($row = $statsResult->fetch_assoc()) {
        if ($row['status'] === 'Active') {
            $statistics['active']++;
        } else {
            $statistics['inactive']++;
        }
        
        if (!empty($row['subject_specialization'])) {
            if (!isset($statistics['departments'][$row['subject_specialization']])) {
                $statistics['departments'][$row['subject_specialization']] = 0;
            }
            $statistics['departments'][$row['subject_specialization']] += $row['count'];
        }
    }
    
} catch (Exception $e) {
    error_log("Error in Teachers Page: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Management - School System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .teacher-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        .department-list {
            margin-top: 15px;
        }
        .department-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" class="logo">
                <h2>School System</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="index.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="teachers.php">
                            <i class='bx bxs-user'></i>
                            <span>Teachers</span>
                        </a>
                    </li>
                    <li>
                        <a href="students.php">
                            <i class='bx bxs-graduation'></i>
                            <span>Students</span>
                        </a>
                    </li>
                    <li>
                        <a href="classes.php">
                            <i class='bx bxs-school'></i>
                            <span>Classes</span>
                        </a>
                    </li>
                    <li>
                        <a href="subjects.php">
                            <i class='bx bxs-book'></i>
                            <span>Subjects</span>
                        </a>
                    </li>
                    <li>
                        <a href="exams.php">
                            <i class='bx bxs-notepad'></i>
                            <span>Exams</span>
                        </a>
                    </li>
                    <li>
                        <a href="timetable.php">
                            <i class='bx bxs-calendar'></i>
                            <span>Timetable</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class='bx bxs-cog'></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <button class="toggle-sidebar">
                    <i class='bx bx-menu'></i>
                </button>
                
                <div class="header-right">
                    <div class="user-info">
                        <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                        <small>Administrator</small>
                    </div>
                    <div class="user-avatar">
                        <img src="../assets/images/default-avatar.png" alt="User Avatar">
                    </div>
                    <div class="logout">
                        <a href="../auth/logout.php" class="btn-logout">
                            <i class='bx bx-log-out'></i>
                        </a>
                    </div>
                </div>
            </header>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="margin: 20px; padding: 15px; border-radius: 5px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;">
                    <i class='bx bx-error-circle'></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <!-- Teacher Statistics -->
                <div class="teacher-stats">
                    <div class="stat-card">
                        <h3>Total Teachers</h3>
                        <div class="stat-value"><?php echo number_format($statistics['total']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Active Teachers</h3>
                        <div class="stat-value"><?php echo number_format($statistics['active']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Inactive Teachers</h3>
                        <div class="stat-value"><?php echo number_format($statistics['inactive']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Departments</h3>
                        <div class="department-list">
                            <?php foreach ($statistics['departments'] as $dept => $count): ?>
                                <div class="department-item">
                                    <span><?php echo htmlspecialchars($dept); ?></span>
                                    <span><?php echo number_format($count); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Search and Add Teacher Button -->
                <div class="content-header">
                    <div class="search-box">
                        <form method="GET" action="">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search teachers...">
                            <button type="submit"><i class='bx bx-search'></i></button>
                        </form>
                    </div>
                    <button class="add-button" onclick="location.href='add_teacher.php'">
                        <i class='bx bx-plus'></i> Add Teacher
                    </button>
                </div>

                <!-- Teachers Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Specialization</th>
                                <th>Experience</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['subject_specialization']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['experience_years']); ?> years</td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($teacher['status']); ?>">
                                            <?php echo htmlspecialchars($teacher['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>" class="edit-btn">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <button class="delete-btn" onclick="deleteTeacher(<?php echo $teacher['id']; ?>)">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class='bx bx-chevron-left'></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class='bx bx-chevron-right'></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher?')) {
                fetch(`api/teachers/delete.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting teacher');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the teacher');
                });
            }
        }
    </script>
</body>
</html>
