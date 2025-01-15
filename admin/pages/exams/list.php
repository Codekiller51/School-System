<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Management - School Management System</title>
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
            <span class="text">Exam Management</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <h5 class="mb-0">Exams List</h5>
                        <select id="academicYearFilter" class="form-select" style="width: 200px;">
                            <?php
                            $currentYear = date('Y');
                            for ($i = 0; $i < 2; $i++) {
                                $year = $currentYear - $i;
                                $academicYear = $year . '-' . ($year + 1);
                                echo "<option value='$academicYear'" . ($i === 0 ? ' selected' : '') . ">$academicYear</option>";
                            }
                            ?>
                        </select>
                        <select id="termFilter" class="form-select" style="width: 150px;">
                            <option value="">All Terms</option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">
                        <i class='bx bx-plus'></i> Add New Exam
                    </button>
                </div>
                <div class="card-body">
                    <table id="examsTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Term</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Classes</th>
                                <th>Status</th>
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

        <!-- Add Exam Modal -->
        <div class="modal fade" id="addExamModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Exam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addExamForm" action="../../../api/exams/add.php" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Exam Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Term</label>
                                    <select class="form-select" name="term" required>
                                        <option value="">Select Term</option>
                                        <option value="1">Term 1</option>
                                        <option value="2">Term 2</option>
                                        <option value="3">Term 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Classes</label>
                                <select class="form-select select2" name="classes[]" multiple required>
                                    <?php
                                    $stmt = $conn->query("
                                        SELECT DISTINCT name, section 
                                        FROM classes 
                                        WHERE academic_year = '" . getAcademicYear() . "'
                                        ORDER BY name, section
                                    ");
                                    while ($class = $stmt->fetch()) {
                                        echo "<option value='" . $class['name'] . "_" . $class['section'] . "'>" . 
                                             htmlspecialchars($class['name'] . ' ' . $class['section']) . "</option>";
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
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Exam</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Exam Modal -->
        <div class="modal fade" id="editExamModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Exam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editExamForm" action="../../../api/exams/update.php" method="POST">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Exam Name</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Term</label>
                                    <select class="form-select" name="term" id="edit_term" required>
                                        <option value="">Select Term</option>
                                        <option value="1">Term 1</option>
                                        <option value="2">Term 2</option>
                                        <option value="3">Term 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" id="edit_start_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" id="edit_end_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Classes</label>
                                <select class="form-select select2" name="classes[]" id="edit_classes" multiple required>
                                    <?php
                                    $stmt = $conn->query("
                                        SELECT DISTINCT name, section 
                                        FROM classes 
                                        WHERE academic_year = '" . getAcademicYear() . "'
                                        ORDER BY name, section
                                    ");
                                    while ($class = $stmt->fetch()) {
                                        echo "<option value='" . $class['name'] . "_" . $class['section'] . "'>" . 
                                             htmlspecialchars($class['name'] . ' ' . $class['section']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update Exam</button>
                                <button type="button" class="btn btn-danger" id="deleteExamBtn">Delete Exam</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage Results Modal -->
        <div class="modal fade" id="manageResultsModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manage Exam Results</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex gap-3 mb-3">
                            <select id="resultClassFilter" class="form-select" style="width: 200px;">
                                <option value="">Select Class</option>
                            </select>
                            <select id="resultSubjectFilter" class="form-select" style="width: 200px;">
                                <option value="">Select Subject</option>
                            </select>
                            <button type="button" class="btn btn-primary" id="loadResultsBtn">
                                Load Results
                            </button>
                        </div>
                        <form id="examResultsForm" action="../../../api/exams/save_results.php" method="POST">
                            <input type="hidden" name="exam_id" id="result_exam_id">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Admission No.</th>
                                            <th>Marks</th>
                                            <th>Grade</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody id="resultsTableBody">
                                        <!-- Results will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Save Results</button>
                        </form>
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
    <script src="../../js/exams.js"></script>
</body>
</html>
