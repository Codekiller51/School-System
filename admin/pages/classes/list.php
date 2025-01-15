<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Management - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <div class="sidebar close">
        <!-- Sidebar content will be loaded via JavaScript -->
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Class Management</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Classes List</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class='bx bx-plus'></i> Add New Class
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Academic Year</label>
                        <select id="academicYearFilter" class="form-select w-auto">
                            <?php
                            $currentYear = date('Y');
                            for ($i = 0; $i < 2; $i++) {
                                $year = $currentYear - $i;
                                $academicYear = $year . '-' . ($year + 1);
                                echo "<option value='$academicYear'" . ($i === 0 ? ' selected' : '') . ">$academicYear</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <table id="classesTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Section</th>
                                <th>Class Teacher</th>
                                <th>Total Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Class Modal -->
        <div class="modal fade" id="addClassModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addClassForm" action="../../../api/classes/add.php" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Class Name</label>
                                    <select class="form-select" name="name" required>
                                        <option value="">Select Class</option>
                                        <option value="Form 1">Form 1</option>
                                        <option value="Form 2">Form 2</option>
                                        <option value="Form 3">Form 3</option>
                                        <option value="Form 4">Form 4</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Section</label>
                                    <select class="form-select" name="section" required>
                                        <option value="">Select Section</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Class Teacher</label>
                                <select class="form-select select2" name="class_teacher_id">
                                    <option value="">Select Class Teacher</option>
                                    <?php
                                    $stmt = $conn->query("SELECT id, first_name, last_name, department FROM teachers ORDER BY first_name, last_name");
                                    while ($teacher = $stmt->fetch()) {
                                        echo "<option value='" . $teacher['id'] . "'>" . 
                                             htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) . 
                                             " (" . htmlspecialchars($teacher['department']) . ")</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Academic Year</label>
                                <select class="form-select" name="academic_year" required>
                                    <?php
                                    $currentYear = date('Y');
                                    $academicYear = $currentYear . '-' . ($currentYear + 1);
                                    echo "<option value='$academicYear'>$academicYear</option>";
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Class</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Class Modal -->
        <div class="modal fade" id="editClassModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editClassForm" action="../../../api/classes/update.php" method="POST">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Class Name</label>
                                    <select class="form-select" name="name" id="edit_name" required>
                                        <option value="">Select Class</option>
                                        <option value="Form 1">Form 1</option>
                                        <option value="Form 2">Form 2</option>
                                        <option value="Form 3">Form 3</option>
                                        <option value="Form 4">Form 4</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Section</label>
                                    <select class="form-select" name="section" id="edit_section" required>
                                        <option value="">Select Section</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Class Teacher</label>
                                <select class="form-select select2" name="class_teacher_id" id="edit_class_teacher_id">
                                    <option value="">Select Class Teacher</option>
                                    <?php
                                    $stmt = $conn->query("SELECT id, first_name, last_name, department FROM teachers ORDER BY first_name, last_name");
                                    while ($teacher = $stmt->fetch()) {
                                        echo "<option value='" . $teacher['id'] . "'>" . 
                                             htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) . 
                                             " (" . htmlspecialchars($teacher['department']) . ")</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Class</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Students Modal -->
        <div class="modal fade" id="viewStudentsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Class Students</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Admission Number</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Parent Name</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody id="classStudentsTableBody">
                                    <!-- Student data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script src="../../js/classes.js"></script>
</body>
</html>
