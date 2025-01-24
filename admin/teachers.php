
<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrator') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    // Get admin info
    $admin_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM administrators WHERE id = ?");
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    // Get teachers with pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT * FROM teachers ORDER BY first_name, last_name LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get total teachers count
    $countQuery = "SELECT COUNT(*) as total FROM teachers";
    $totalTeachers = $conn->query($countQuery)->fetch_assoc()['total'];
    $totalPages = ceil($totalTeachers / $limit);
    
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
    <title>Teacher Management - School Management System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Teacher Management</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                                        <i class='bx bx-plus'></i> Add New Teacher
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Specialization</th>
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
                                                    <td>
                                                        <span class="badge <?php echo $teacher['status'] === 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                                            <?php echo htmlspecialchars($teacher['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewTeacher(<?php echo $teacher['id']; ?>)">
                                                            <i class='bx bx-show'></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-primary" onclick="editTeacher(<?php echo $teacher['id']; ?>)">
                                                            <i class='bx bx-edit'></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteTeacher(<?php echo $teacher['id']; ?>)">
                                                            <i class='bx bx-trash'></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                            <?php endfor; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTeacherForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject Specialization</label>
                            <input type="text" class="form-control" name="subject_specialization">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Qualification</label>
                            <input type="text" class="form-control" name="qualification">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experience (Years)</label>
                                <input type="number" class="form-control" name="experience_years" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Joining Date</label>
                                <input type="date" class="form-control" name="joining_date">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveTeacher()">Save Teacher</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editTeacherForm">
                        <input type="hidden" name="teacher_id">
                        <!-- Same form fields as Add Teacher Modal -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateTeacher()">Update Teacher</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Teacher Modal -->
    <div class="modal fade" id="viewTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Teacher Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img src="" alt="Teacher Profile" class="img-fluid rounded-circle mb-2" id="teacherProfileImage">
                            <h4 id="teacherName"></h4>
                            <p class="text-muted" id="teacherSpecialization"></p>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Email:</strong> <span id="teacherEmail"></span></p>
                                    <p><strong>Phone:</strong> <span id="teacherPhone"></span></p>
                                    <p><strong>Gender:</strong> <span id="teacherGender"></span></p>
                                    <p><strong>Date of Birth:</strong> <span id="teacherDOB"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Qualification:</strong> <span id="teacherQualification"></span></p>
                                    <p><strong>Experience:</strong> <span id="teacherExperience"></span></p>
                                    <p><strong>Joining Date:</strong> <span id="teacherJoiningDate"></span></p>
                                    <p><strong>Status:</strong> <span id="teacherStatus"></span></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p><strong>Address:</strong></p>
                                <p id="teacherAddress"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Teacher CRUD operations
        function viewTeacher(id) {
            fetch(`api/get_teacher.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('teacherName').textContent = data.first_name + ' ' + data.last_name;
                    document.getElementById('teacherEmail').textContent = data.email;
                    document.getElementById('teacherPhone').textContent = data.phone || 'N/A';
                    document.getElementById('teacherSpecialization').textContent = data.subject_specialization || 'N/A';
                    document.getElementById('teacherGender').textContent = data.gender || 'N/A';
                    document.getElementById('teacherDOB').textContent = data.date_of_birth || 'N/A';
                    document.getElementById('teacherQualification').textContent = data.qualification || 'N/A';
                    document.getElementById('teacherExperience').textContent = data.experience_years ? data.experience_years + ' years' : 'N/A';
                    document.getElementById('teacherJoiningDate').textContent = data.joining_date || 'N/A';
                    document.getElementById('teacherStatus').textContent = data.status;
                    document.getElementById('teacherAddress').textContent = data.address || 'N/A';
                    
                    const profileImage = data.profile_image || `https://ui-avatars.com/api/?name=${encodeURIComponent(data.first_name + ' ' + data.last_name)}&background=4361ee&color=fff`;
                    document.getElementById('teacherProfileImage').src = profileImage;
                    
                    new bootstrap.Modal(document.getElementById('viewTeacherModal')).show();
                })
                .catch(error => alert('Error loading teacher details'));
        }

        function editTeacher(id) {
            fetch(`api/get_teacher.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    const form = document.getElementById('editTeacherForm');
                    form.teacher_id.value = data.id;
                    // Populate other form fields...
                    new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
                })
                .catch(error => alert('Error loading teacher details'));
        }

        function saveTeacher() {
            const form = document.getElementById('addTeacherForm');
            const formData = new FormData(form);
            
            fetch('api/add_teacher.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Teacher added successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Error adding teacher');
                }
            })
            .catch(error => alert('Error adding teacher'));
        }

        function updateTeacher() {
            const form = document.getElementById('editTeacherForm');
            const formData = new FormData(form);
            
            fetch('api/update_teacher.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Teacher updated successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Error updating teacher');
                }
            })
            .catch(error => alert('Error updating teacher'));
        }

        function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher?')) {
                fetch('api/delete_teacher.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Teacher deleted successfully');
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting teacher');
                    }
                })
                .catch(error => alert('Error deleting teacher'));
            }
        }
    </script>
</body>
</html>
