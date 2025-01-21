<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
requireAdmin();
 // Get admin info
 $admin_id = $_SESSION['user_id'];
 $stmt = $conn->prepare("SELECT * FROM administrators WHERE id = ?");
 $stmt->execute([$admin_id]);
 $admin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - School Management System</title>
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
                <a href="classes.php" class="nav-link ">
                    <i class='bx bxs-school'></i>
                    <span>Classes</span>
                </a>
                <a href="subjects.php" class="nav-link active">
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
                                    <h5 class="card-title mb-0">Subject Management</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                        <i class='bx bx-plus'></i> Add New Subject
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="subjectsTable">
                                            <thead>
                                                <tr>
                                                    <th>Subject Code</th>
                                                    <th>Name</th>
                                                    <th>Class Level</th>
                                                    <th>Credits</th>
                                                    <th>Department</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Table content will be loaded dynamically -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addSubjectForm">
                        <div class="mb-3">
                            <label class="form-label">Subject Code</label>
                            <input type="text" class="form-control" name="subjectCode" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class Level</label>
                            <select class="form-select" name="classLevel" required>
                                <option value="">Select Class Level</option>
                                <option value="Form 1">Form 1</option>
                                <option value="Form 2">Form 2</option>
                                <option value="Form 3">Form 3</option>
                                <option value="Form 4">Form 4</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Credits</label>
                            <input type="number" class="form-control" name="credits" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department" required>
                                <option value="">Select Department</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Sciences">Sciences</option>
                                <option value="Languages">Languages</option>
                                <option value="Humanities">Humanities</option>
                                <option value="Technical">Technical</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveSubject">Save Subject</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
