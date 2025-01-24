<?php
session_start();
require_once '../config/database.php';
// require_once '../includes/functions.php';
// requireAdmin();

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
            (SELECT COUNT(*) FROM subjects cs WHERE cs.id = c.id) as subject_count,
            (SELECT CONCAT(t.first_name, ' ', t.last_name) FROM teachers t WHERE t.id = c.teacher_id) as teacher_name
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
            font-size: 18px;
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
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
        }

        .table {
            font-size: 0.95rem;
        }

        .btn {
            font-size: 0.875rem;
        }

        .badge {
            font-size: 0.875rem;
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

                <!-- Page Content -->
                <div class="page-content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Class Management</h5>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                                            <i class='bx bx-plus'></i> Add New Class
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped" id="classesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Class Name</th>
                                                        <th>Grade Level</th>
                                                        <th>Section</th>
                                                        <th>Class Teacher</th>
                                                        <th>Students</th>
                                                        <th>Subjects</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($classes as $class): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($class['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($class['grade_level']); ?></td>
                                                        <td><?php echo htmlspecialchars($class['section']); ?></td>
                                                        <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($class['student_count']); ?> students</td>
                                                        <td><?php echo htmlspecialchars($class['subject_count']); ?> subjects</td>
                                                        <td>
                                                            <span class="badge <?php echo $class['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                                <?php echo ucfirst(htmlspecialchars($class['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary" onclick="editClass(<?php echo $class['id']; ?>)">
                                                                <i class='bx bx-edit'></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteClass(<?php echo $class['id']; ?>)">
                                                                <i class='bx bx-trash'></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-info" onclick="viewClass(<?php echo $class['id']; ?>)">
                                                                <i class='bx bx-show'></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addClassForm">
                        <div class="mb-3">
                            <label class="form-label">Class Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade Level</label>
                            <select class="form-select" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section</label>
                            <input type="text" class="form-control" name="section" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class Teacher</label>
                            <select class="form-select" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php
                                $teachers_query = "SELECT id, first_name, last_name FROM teachers WHERE status = 'active'";
                                $teachers_result = $conn->query($teachers_query);
                                while($teacher = $teachers_result->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveClass()">Save Class</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editClassForm">
                        <input type="hidden" name="class_id" id="edit_class_id">
                        <div class="mb-3">
                            <label class="form-label">Class Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade Level</label>
                            <select class="form-select" name="grade_level" id="edit_grade_level" required>
                                <option value="">Select Grade Level</option>
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section</label>
                            <input type="text" class="form-control" name="section" id="edit_section" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class Teacher</label>
                            <select class="form-select" name="teacher_id" id="edit_teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php
                                $teachers_result->data_seek(0);
                                while($teacher = $teachers_result->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateClass()">Update Class</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Class Modal -->
    <div class="modal fade" id="viewClassModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Class Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Basic Information</h6>
                            <p><strong>Class Name:</strong> <span id="view_name"></span></p>
                            <p><strong>Grade Level:</strong> <span id="view_grade_level"></span></p>
                            <p><strong>Section:</strong> <span id="view_section"></span></p>
                            <p><strong>Class Teacher:</strong> <span id="view_teacher"></span></p>
                            <p><strong>Status:</strong> <span id="view_status"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Statistics</h6>
                            <p><strong>Total Students:</strong> <span id="view_students"></span></p>
                            <p><strong>Total Subjects:</strong> <span id="view_subjects"></span></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h6 class="mb-3">Students List</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="studentsListTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Student ID</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="view_students_list">
                                    <!-- Students list will be loaded dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to handle class deletion
        function deleteClass(id) {
            if (confirm('Are you sure you want to delete this class? This will affect all students in this class.')) {
                // Add your delete logic here
                fetch('api/delete_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to delete class: ' + data.message);
                    }
                });
            }
        }

        // Function to open edit modal
        function editClass(id) {
            fetch('api/get_class.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_class_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_grade_level').value = data.grade_level;
                    document.getElementById('edit_section').value = data.section;
                    document.getElementById('edit_teacher_id').value = data.teacher_id;
                    document.getElementById('edit_status').value = data.status;
                    
                    new bootstrap.Modal(document.getElementById('editClassModal')).show();
                });
        }

        // Function to view class details
        function viewClass(id) {
            fetch('api/get_class_details.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('view_name').textContent = data.name;
                    document.getElementById('view_grade_level').textContent = 'Grade ' + data.grade_level;
                    document.getElementById('view_section').textContent = data.section;
                    document.getElementById('view_teacher').textContent = data.teacher_name;
                    document.getElementById('view_status').textContent = data.status;
                    document.getElementById('view_students').textContent = data.student_count;
                    document.getElementById('view_subjects').textContent = data.subject_count;
                    
                    // Populate students list
                    const studentsList = document.getElementById('view_students_list');
                    studentsList.innerHTML = '';
                    data.students.forEach(student => {
                        studentsList.innerHTML += `
                            <tr>
                                <td>${student.name}</td>
                                <td>${student.student_id}</td>
                                <td><span class="badge ${student.status === 'active' ? 'bg-success' : 'bg-danger'}">${student.status}</span></td>
                            </tr>
                        `;
                    });
                    
                    new bootstrap.Modal(document.getElementById('viewClassModal')).show();
                });
        }

        // Function to save new class
        function saveClass() {
            const formData = new FormData(document.getElementById('addClassForm'));
            fetch('api/add_class.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to add class: ' + data.message);
                }
            });
        }

        // Function to update class
        function updateClass() {
            const formData = new FormData(document.getElementById('editClassForm'));
            fetch('api/update_class.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update class: ' + data.message);
                }
            });
        }

        $(document).ready(function() {
            $('#classesTable').DataTable();
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
