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
    
    // Pagination settings
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Search functionality
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $class_filter = isset($_GET['class']) ? trim($_GET['class']) : '';
    $searchCondition = '';
    $params = [];
    $types = '';
    
    if (!empty($search) || !empty($class_filter)) {
        $conditions = [];
        if (!empty($search)) {
            $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR s.registration_number LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= 'ssss';
        }
        if (!empty($class_filter)) {
            $conditions[] = "s.class_id = ?";
            $params[] = $class_filter;
            $types .= 'i';
        }
        $searchCondition = " AND " . implode(" AND ", $conditions);
    }
    
    // Get total number of students
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE 1=1" . $searchCondition;
    
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalStudents = $stmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalStudents / $limit);
    
    // Get students with pagination
    $query = "
        SELECT 
            s.*,
            c.name as class_name,
            c.grade_level
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE 1=1" . $searchCondition . "
        ORDER BY s.created_at DESC 
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
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    // Get student statistics
    $statsQuery = "
        SELECT 
            status,
            COUNT(*) as count,
            c.grade_level
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        GROUP BY status, c.grade_level
    ";
    $statsResult = $conn->query($statsQuery);
    $statistics = [
        'total' => $totalStudents,
        'active' => 0,
        'inactive' => 0,
        'by_grade' => []
    ];
    
    while ($row = $statsResult->fetch_assoc()) {
        if ($row['status'] === 'Active') {
            $statistics['active']++;
        } else {
            $statistics['inactive']++;
        }
        
        if (!empty($row['grade_level'])) {
            if (!isset($statistics['by_grade'][$row['grade_level']])) {
                $statistics['by_grade'][$row['grade_level']] = 0;
            }
            $statistics['by_grade'][$row['grade_level']] += $row['count'];
        }
    }
    
    // Get available classes for filter
    $classesQuery = "SELECT id, name, grade_level FROM classes WHERE status = 'Active' ORDER BY grade_level, name";
    $classesResult = $conn->query($classesQuery);
    $classes = [];
    while ($row = $classesResult->fetch_assoc()) {
        $classes[] = $row;
    }
    
} catch (Exception $e) {
    error_log("Error in Students Page: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - School System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .student-stats {
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
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="margin: 20px; padding: 15px; border-radius: 5px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;">
                    <i class='bx bx-error-circle'></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <!-- Student Statistics -->
                <div class="student-stats">
                    <div class="stat-card">
                        <h3>Total Students</h3>
                        <div class="stat-value"><?php echo number_format($statistics['total']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Active Students</h3>
                        <div class="stat-value"><?php echo number_format($statistics['active']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Inactive Students</h3>
                        <div class="stat-value"><?php echo number_format($statistics['inactive']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>By Grade Level</h3>
                        <div class="grade-list">
                            <?php foreach ($statistics['by_grade'] as $grade => $count): ?>
                                <div class="grade-item">
                                    <span>Grade <?php echo htmlspecialchars($grade); ?></span>
                                    <span><?php echo number_format($count); ?></span>
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
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search students...">
                                <select name="class" class="filter-select">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['name'] . ' (Grade ' . $class['grade_level'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit"><i class='bx bx-search'></i></button>
                            </form>
                        </div>
                    </div>
                    <button class="add-button" onclick="location.href='add_student.php'">
                        <i class='bx bx-plus'></i> Add Student
                    </button>
                </div>

                <!-- Students Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Registration No.</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['registration_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class_name'] . ' (Grade ' . $student['grade_level'] . ')'); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($student['status']); ?>">
                                            <?php echo htmlspecialchars($student['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="edit-btn">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <button class="delete-btn" onclick="deleteStudent(<?php echo $student['id']; ?>)">
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
                                <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class_filter) ? '&class=' . urlencode($class_filter) : ''; ?>" class="page-link">
                                    <i class='bx bx-chevron-left'></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class_filter) ? '&class=' . urlencode($class_filter) : ''; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class_filter) ? '&class=' . urlencode($class_filter) : ''; ?>" class="page-link">
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
        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                fetch(`api/students/delete.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting student');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the student');
                });
            }
        }
    </script>
</body>
</html>
