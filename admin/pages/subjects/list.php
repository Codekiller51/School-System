<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subject Management - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar close">
        <!-- Sidebar content will be loaded via JavaScript -->
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Subject Management</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Subjects List</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class='bx bx-plus'></i> Add New Subject
                    </button>
                </div>
                <div class="card-body">
                    <table id="subjectsTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Class Level</th>
                                <th>Credits</th>
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

        <!-- Add Subject Modal -->
        <div class="modal fade" id="addSubjectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addSubjectForm" action="../../../api/subjects/add.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Subject Code</label>
                                <input type="text" class="form-control" name="subject_code" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Science">Science</option>
                                    <option value="English">English</option>
                                    <option value="Social Studies">Social Studies</option>
                                    <option value="Physical Education">Physical Education</option>
                                    <option value="Arts">Arts</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Class Level</label>
                                <select class="form-select" name="class_level" required>
                                    <option value="">Select Class Level</option>
                                    <option value="Form 1">Form 1</option>
                                    <option value="Form 2">Form 2</option>
                                    <option value="Form 3">Form 3</option>
                                    <option value="Form 4">Form 4</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Credits</label>
                                <input type="number" class="form-control" name="credits" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Subject</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Subject Modal -->
        <div class="modal fade" id="editSubjectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editSubjectForm" action="../../../api/subjects/update.php" method="POST">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="mb-3">
                                <label class="form-label">Subject Code</label>
                                <input type="text" class="form-control" name="subject_code" id="edit_subject_code" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject Name</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department" id="edit_department" required>
                                    <option value="">Select Department</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Science">Science</option>
                                    <option value="English">English</option>
                                    <option value="Social Studies">Social Studies</option>
                                    <option value="Physical Education">Physical Education</option>
                                    <option value="Arts">Arts</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Class Level</label>
                                <select class="form-select" name="class_level" id="edit_class_level" required>
                                    <option value="">Select Class Level</option>
                                    <option value="Form 1">Form 1</option>
                                    <option value="Form 2">Form 2</option>
                                    <option value="Form 3">Form 3</option>
                                    <option value="Form 4">Form 4</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Credits</label>
                                <input type="number" class="form-control" name="credits" id="edit_credits" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Subject</button>
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
    <script src="../../js/sidebar.js"></script>
    <script src="../../js/subjects.js"></script>
</body>
</html>
