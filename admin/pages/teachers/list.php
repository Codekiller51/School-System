<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Management - School Management System</title>
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
            <span class="text">Teacher Management</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Teachers List</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <i class='bx bx-plus'></i> Add New Teacher
                    </button>
                </div>
                <div class="card-body">
                    <table id="teachersTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Qualification</th>
                                <th>Contact</th>
                                <th>Email</th>
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

        <!-- Add Teacher Modal -->
        <div class="modal fade" id="addTeacherModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Teacher</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addTeacherForm" action="../../../api/teachers/add.php" method="POST">
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
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
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Joining Date</label>
                                    <input type="date" class="form-control" name="joining_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Qualification</label>
                                <textarea class="form-control" name="qualification" rows="2" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Teacher</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Teacher Modal -->
        <div class="modal fade" id="editTeacherModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Teacher</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editTeacherForm" action="../../../api/teachers/update.php" method="POST">
                            <input type="hidden" name="id" id="edit_id">
                            <!-- Same form fields as Add Teacher Modal -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                                </div>
                            </div>
                            <!-- Add other fields similar to add form -->
                            <button type="submit" class="btn btn-primary">Update Teacher</button>
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
    <script src="../../js/teachers.js"></script>
</body>
</html>
