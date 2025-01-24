<?php
session_start();
require_once('../config/database.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrator') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $admin_id = $_SESSION['user_id'];

    
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
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Search 
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchCondition = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $searchCondition = " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR admission_number LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        $types = 'ssss';
    }
    
    // Get total  of students
    $countQuery = "SELECT COUNT(*) as total FROM students WHERE 1=1" . $searchCondition;
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
            id,
            first_name,
            last_name,
            email,
            phone,
            admission_number,
            class_id,
            gender,
            status,
            created_at
        FROM students 
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
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    // Get student statistics
    $statsQuery = "
        SELECT 
            status,
            COUNT(*) as count
        FROM students 
        GROUP BY status
    ";
    $statsResult = $conn->query($statsQuery);
    $statistics = [
        'total' => $totalStudents,
        'active' => 0,
        'inactive' => 0
    ];
    
    while ($row = $statsResult->fetch_assoc()) {
        if ($row['status'] === 'Active') {
            $statistics['active'] += $row['count'];
        } else {
            $statistics['inactive'] += $row['count'];
        }
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
        .card {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.125);
            padding: 1rem 1.5rem;
        }

        .card-header .btn-primary {
            padding: 0.5rem 1rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-header .btn-primary i {
            font-size: 1.25rem;
        }

        .modal-content {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid #dee2e6;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .btn {
            padding: 0.5rem 1rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            border-radius: 0.375rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
        }

        .btn-primary:hover {
            background-color: var(--primary-color-hover);
        }

        .btn-secondary {
            background-color: #fff;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background-color: var(--primary-color-hover);
            color: #fff;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .student-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0 0 0.75rem 0;
            color: #333;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .table-responsive {
            margin-top: 1rem;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
        }

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
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
                <a href="students.php" class="nav-link active">
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

        <main class="main-content">
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
                <div class="page-content">
                    <div class="container-fluid">
                        <div class="student-stats mb-4">
                            <div class="stat-card">
                                <h3>Total Students</h3>
                                <p class="stat-value"><?php echo $statistics['total']; ?></p>
                            </div>
                            <div class="stat-card">
                                <h3>Active Students</h3>
                                <p class="stat-value"><?php echo $statistics['active']; ?></p>
                            </div>
                            <div class="stat-card">
                                <h3>Inactive Students</h3>
                                <p class="stat-value"><?php echo $statistics['inactive']; ?></p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Student Management</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                    <i class='bx bx-plus'></i> Add New Student
                                </button>
                            </div>



                           
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="studentsTable">
                                        <thead>
                                            <tr>
                                                <th>Admission #</th>
                                                <th>Name</th>
                                                <th>Class</th>
                                                <th>Gender</th>
                                                <th>Phone</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class_id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo strtolower($student['status']) === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ucfirst(htmlspecialchars($student['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="editStudent(<?php echo $student['id']; ?>)">
                                                        <i class='bx bx-edit'></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)">
                                                        <i class='bx bx-trash'></i>
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
            <?php endif; ?>
        </main>
    </div>
    <div class="overlay"></div>

    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm">
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="admissionNumber" class="form-label">Admission Number</label>
                            <input type="text" class="form-control" id="admissionNumber" name="admission_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class_id" required>
                                <?php
                                // Fetch classes from database
                                $classQuery = "SELECT id, name FROM classes WHERE status = 'Active'";
                                $classResult = $conn->query($classQuery);
                                while ($class = $classResult->fetch_assoc()) {
                                    echo "<option value='" . $class['id'] . "'>" . htmlspecialchars($class['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addStudentForm" class="btn btn-primary">Add Student</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" name="id">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class</label>
                            <select class="form-select" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php
                                $classResult->data_seek(0);
                                while ($class = $classResult->fetch_assoc()) {
                                    echo "<option value='" . $class['id'] . "'>" . htmlspecialchars($class['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parent/Guardian Name</label>
                            <input type="text" class="form-control" name="guardian_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="contact_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" required rows="3"></textarea>
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
                    <button type="button" class="btn btn-primary" onclick="updateStudent()">Update Student</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#studentsTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']]
            });

            // Handle form submission
            $('#addStudentForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'api/add_student.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#addStudentModal').modal('hide');
                            location.reload();
                        } else {
                            alert('Error adding student: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error adding student');
                    }
                });
            });
        });

        function editStudent(id) {
            $.ajax({
                url: 'api/get_student.php',
                type: 'GET',
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        const student = response.data;
                        $('#editStudentId').val(student.id);
                        $('#editFirstName').val(student.first_name);
                        $('#editLastName').val(student.last_name);
                        $('#editEmail').val(student.email);
                        $('#editPhone').val(student.phone);
                        $('#editAdmissionNumber').val(student.admission_number);
                        $('#editClassId').val(student.class_id);
                        $('#editGender').val(student.gender);
                        $('#editDateOfBirth').val(student.date_of_birth);
                        $('#editStatus').val(student.status);
                        $('#editStudentModal').modal('show');
                    } else {
                        alert('Error fetching student data');
                    }
                },
                error: function() {
                    alert('Error fetching student data');
                }
            });
        }

        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                $.ajax({
                    url: 'api/delete_student.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting student');
                        }
                    },
                    error: function() {
                        alert('Error deleting student');
                    }
                });
            }
        }

        $('#editStudentForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'api/update_student.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error updating student');
                    }
                },
                error: function() {
                    alert('Error updating student');
                }
            });
        });
    </script>
</body>
</html>
