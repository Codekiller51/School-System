<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
requireAdmin();

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
    
    // Pagination settings
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Search functionality
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $grade_filter = isset($_GET['grade']) ? trim($_GET['grade']) : '';
    $searchCondition = '';
    $params = [];
    $types = '';
    
    if (!empty($search) || !empty($grade_filter)) {
        $conditions = [];
        if (!empty($search)) {
            $conditions[] = "(name LIKE ? OR section LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam]);
            $types .= 'ss';
        }
        if (!empty($grade_filter)) {
            $conditions[] = "grade_level = ?";
            $params[] = $grade_filter;
            $types .= 'i';
        }
        $searchCondition = " AND " . implode(" AND ", $conditions);
    }
    
    // Get total number of classes
    $countQuery = "SELECT COUNT(*) as total FROM classes WHERE 1=1" . $searchCondition;
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalClasses = $stmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalClasses / $limit);
    
    // Get classes with pagination
    $query = "
        SELECT 
            c.*,
            (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.status = 'Active') as student_count,
            (SELECT COUNT(*) FROM class_subjects cs WHERE cs.class_id = c.id) as subject_count
        FROM classes c
        WHERE 1=1" . $searchCondition . "
        ORDER BY c.grade_level ASC, c.name ASC 
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
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    // Get class statistics
    $statsQuery = "
        SELECT 
            c.grade_level,
            COUNT(*) as class_count,
            SUM(CASE WHEN c.status = 'Active' THEN 1 ELSE 0 END) as active_count,
            COUNT(DISTINCT s.id) as total_students
        FROM classes c
        LEFT JOIN students s ON s.class_id = c.id AND s.status = 'Active'
        GROUP BY c.grade_level
        ORDER BY c.grade_level
    ";
    $statsResult = $conn->query($statsQuery);
    $statistics = [
        'total' => $totalClasses,
        'active' => 0,
        'inactive' => 0,
        'by_grade' => [],
        'total_students' => 0
    ];
    
    while ($row = $statsResult->fetch_assoc()) {
        $statistics['by_grade'][$row['grade_level']] = [
            'classes' => $row['class_count'],
            'active_classes' => $row['active_count'],
            'students' => $row['total_students']
        ];
        $statistics['active'] += $row['active_count'];
        $statistics['total_students'] += $row['total_students'];
    }
    $statistics['inactive'] = $totalClasses - $statistics['active'];
    
    // Get grade levels for filter
    $gradesQuery = "SELECT DISTINCT grade_level FROM classes ORDER BY grade_level";
    $gradesResult = $conn->query($gradesQuery);
    $grades = [];
    while ($row = $gradesResult->fetch_assoc()) {
        $grades[] = $row['grade_level'];
    }
    
} catch (Exception $e) {
    error_log("Error in Classes Page: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management - School Management System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .class-stats {
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
        .grade-list {
            margin-top: 15px;
        }
        .grade-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .filter-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 150px;
        }
        .class-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .class-name {
            font-weight: 600;
            color: var(--primary-color);
        }
        .class-section {
            font-size: 0.9em;
            color: #666;
        }
        .counts {
            display: flex;
            gap: 15px;
            font-size: 0.9em;
        }
        .count-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .count-item i {
            font-size: 1.1em;
        }
    </style>
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
                <a href="classes.php" class="nav-link active">
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
            <?php else: ?>
                <!-- Class Statistics -->
                <div class="class-stats">
                    <div class="stat-card">
                        <h3>Total Classes</h3>
                        <div class="stat-value"><?php echo number_format($statistics['total']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Active Classes</h3>
                        <div class="stat-value"><?php echo number_format($statistics['active']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Students</h3>
                        <div class="stat-value"><?php echo number_format($statistics['total_students']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>By Grade Level</h3>
                        <div class="grade-list">
                            <?php foreach ($statistics['by_grade'] as $grade => $stats): ?>
                                <div class="grade-item">
                                    <span>Grade <?php echo htmlspecialchars($grade); ?></span>
                                    <span>
                                        <?php echo number_format($stats['classes']); ?> classes
                                        (<?php echo number_format($stats['students']); ?> students)
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="content-header">
                    <div class="filters">
                        <div class="search-box">
                            <form method="GET" action="">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search classes...">
                                <select name="grade" class="filter-select">
                                    <option value="">All Grades</option>
                                    <?php foreach ($grades as $grade): ?>
                                        <option value="<?php echo $grade; ?>" <?php echo $grade_filter == $grade ? 'selected' : ''; ?>>
                                            Grade <?php echo htmlspecialchars($grade); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit"><i class='bx bx-search'></i></button>
                            </form>
                        </div>
                    </div>
                    <button class="add-button" onclick="location.href='add_class.php'">
                        <i class='bx bx-plus'></i> Add Class
                    </button>
                </div>

                <!-- Classes Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Class Info</th>
                                <th>Grade</th>
                                <th>Students</th>
                                <th>Subjects</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td>
                                        <div class="class-info">
                                            <span class="class-name"><?php echo htmlspecialchars($class['name']); ?></span>
                                            <span class="class-section"><?php echo htmlspecialchars($class['section']); ?></span>
                                        </div>
                                    </td>
                                    <td>Grade <?php echo htmlspecialchars($class['grade_level']); ?></td>
                                    <td>
                                        <div class="counts">
                                            <span class="count-item">
                                                <i class='bx bxs-graduation'></i>
                                                <?php echo number_format($class['student_count']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="counts">
                                            <span class="count-item">
                                                <i class='bx bxs-book'></i>
                                                <?php echo number_format($class['subject_count']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($class['status']); ?>">
                                            <?php echo htmlspecialchars($class['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_class.php?id=<?php echo $class['id']; ?>" class="edit-btn">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <button class="delete-btn" onclick="deleteClass(<?php echo $class['id']; ?>)">
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
                                <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($grade_filter) ? '&grade=' . urlencode($grade_filter) : ''; ?>" class="page-link">
                                    <i class='bx bx-chevron-left'></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($grade_filter) ? '&grade=' . urlencode($grade_filter) : ''; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($grade_filter) ? '&grade=' . urlencode($grade_filter) : ''; ?>" class="page-link">
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
        function deleteClass(id) {
            if (confirm('Are you sure you want to delete this class? This will affect all students in this class.')) {
                fetch(`api/classes/delete.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting class');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the class');
                });
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
