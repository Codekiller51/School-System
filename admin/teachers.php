<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$error = null;
$teachers = [];
$dept_stats = [];
$total_pages = 1;
$page = 1;
$search = '';
$status_filter = 'all';

try {
    // Test database connection first
    $test_query = "SELECT 1 FROM teachers LIMIT 1";
    $conn->query($test_query);

    // Initialize variables for pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Search functionality
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

    // Simple query first to test
    $query = "SELECT * FROM teachers";
    $params = [];

    // Add search condition if search term exists
    if (!empty($search)) {
        $query .= " WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_id LIKE ? OR department LIKE ?)";
        $search_param = "%$search%";
        $params = array_fill(0, 5, $search_param);
    }

    // Add status filter if not 'all'
    if ($status_filter !== 'all') {
        $where_or_and = empty($params) ? " WHERE" : " AND";
        $query .= "$where_or_and status = ?";
        $params[] = $status_filter;
    }

    // Add pagination
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM teachers";
    if (!empty($params)) {
        $count_query = str_replace(" ORDER BY created_at DESC LIMIT ? OFFSET ?", "", $query);
    }
    
    $stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        // Remove the limit and offset parameters for the count query
        $count_params = array_slice($params, 0, -2);
        $stmt->execute($count_params);
    } else {
        $stmt->execute();
    }
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    // Execute main query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll();

    // Get department statistics with simplified query
    $dept_query = "SELECT department, COUNT(*) as count FROM teachers GROUP BY department";
    $dept_stats = $conn->query($dept_query)->fetchAll();

} catch (PDOException $e) {
    error_log("Database Error in Teachers Management: " . $e->getMessage());
    $error = "A database error occurred: " . $e->getMessage();
} catch (Exception $e) {
    error_log("General Error in Teachers Management: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Management - School System</title>
    
    <!-- CSS -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Additional styles for teachers page */
        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .edit-btn {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .delete-btn {
            background: rgba(247, 37, 133, 0.1);
            color: var(--warning-color);
        }

        .view-btn {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stats-card {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .stats-card h3 {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .stats-card p {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-form {
            flex: 1;
            min-width: 200px;
            display: flex;
            gap: 0.5rem;
        }

        .search-form input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--radius);
            outline: none;
        }

        .search-form button {
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--radius);
            outline: none;
        }

        .add-teacher {
            padding: 0.5rem 1rem;
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--text-primary);
        }

        .page-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge.active {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .badge.inactive {
            background: rgba(247, 37, 133, 0.1);
            color: var(--warning-color);
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
                <a href="teachers.php" class="nav-link active">
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
                    <input type="text" placeholder="Quick search...">
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
                <!-- Department Statistics -->
                <div class="stats-cards">
                    <?php foreach ($dept_stats as $stat): ?>
                        <div class="stats-card">
                            <h3><?php echo htmlspecialchars($stat['department']); ?></h3>
                            <p><?php echo $stat['count']; ?> Teachers</p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filters and Actions -->
                <div class="filters">
                    <form class="search-form" method="GET">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search teachers..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                        <select name="status" class="filter-select">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <button type="submit">
                            <i class='bx bx-search'></i>
                            Search
                        </button>
                    </form>
                    
                    <a href="add-teacher.php" class="add-teacher">
                        <i class='bx bx-plus'></i>
                        Add Teacher
                    </a>
                </div>

                <!-- Teachers Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">Teachers List</h2>
                    </div>
                    
                    <?php if (empty($teachers)): ?>
                        <div class="empty-state">
                            <i class='bx bx-user-x'></i>
                            <p>No teachers found</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Employee ID</th>
                                    <th>Department</th>
                                    <th>Phone</th>
                                    <th>Joining Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($teacher['first_name'] . ' ' . $teacher['last_name']); ?>&size=32" alt="">
                                                <div>
                                                    <div><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></div>
                                                    <div class="text-small"><?php echo htmlspecialchars($teacher['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($teacher['employee_id']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($teacher['joining_date'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $teacher['status']; ?>">
                                                <?php echo ucfirst($teacher['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions-cell">
                                                <a href="view-teacher.php?id=<?php echo $teacher['id']; ?>" class="action-btn view-btn">
                                                    <i class='bx bx-show'></i>
                                                </a>
                                                <a href="edit-teacher.php?id=<?php echo $teacher['id']; ?>" class="action-btn edit-btn">
                                                    <i class='bx bx-edit'></i>
                                                </a>
                                                <button 
                                                    class="action-btn delete-btn"
                                                    onclick="deleteTeacher(<?php echo $teacher['id']; ?>)"
                                                >
                                                    <i class='bx bx-trash'></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a 
                                        href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?>" 
                                        class="page-link <?php echo $page === $i ? 'active' : ''; ?>"
                                    >
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
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

        // Delete Teacher Function
        function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher?')) {
                window.location.href = `delete-teacher.php?id=${id}`;
            }
        }

        // Quick Search Functionality
        const quickSearch = document.querySelector('.search-box input');
        quickSearch.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                const searchTerm = e.target.value.trim();
                if (searchTerm) {
                    window.location.href = `teachers.php?search=${encodeURIComponent(searchTerm)}`;
                }
            }
        });
    </script>
</body>
</html>
