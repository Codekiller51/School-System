<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();

// Handle form submission for new assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("
            INSERT INTO assignments (title, description, subject_id, class_id, due_date, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['subject_id'],
            $_POST['class_id'],
            $_POST['due_date'],
            $_SESSION['user_id']
        ]);

        setFlashMessage('success', 'Assignment added successfully!');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error adding assignment: ' . $e->getMessage());
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get all assignments with related information
try {
    $stmt = $conn->query("
        SELECT a.*, 
               s.name as subject_name,
               c.name as class_name,
               c.section,
               t.first_name as teacher_first_name,
               t.last_name as teacher_last_name
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        JOIN classes c ON a.class_id = c.id
        JOIN teachers t ON a.created_by = t.id
        ORDER BY a.due_date DESC
    ");
    $assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching assignments: ' . $e->getMessage());
    $assignments = [];
}

// Get subjects for dropdown
try {
    $stmt = $conn->query("SELECT id, name, class_level FROM subjects ORDER BY name");
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching subjects: ' . $e->getMessage());
    $subjects = [];
}

// Get classes for dropdown
try {
    $stmt = $conn->query("SELECT id, name, section FROM classes ORDER BY name, section");
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching classes: ' . $e->getMessage());
    $classes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment Management - School Management System</title>
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
            <span class="text">Assignment Management</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Assignments</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
                        Add New Assignment
                    </button>
                </div>
                <div class="card-body">
                    <table id="assignmentsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Due Date</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['class_name'] . ' ' . $assignment['section']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewAssignment(<?php echo $assignment['id']; ?>)">
                                            View
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteAssignment(<?php echo $assignment['id']; ?>)">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Add Assignment Modal -->
    <div class="modal fade" id="addAssignmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="subject_id" class="form-label">Subject</label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['name'] . ' (' . $subject['class_level'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="class_id" class="form-label">Class</label>
                                <select class="form-select" id="class_id" name="class_id" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>">
                                            <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="datetime-local" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="../../js/script.js"></script>
    <script>
        $(document).ready(function() {
            $('#assignmentsTable').DataTable({
                order: [[3, 'desc']]
            });
        });

        function viewAssignment(assignmentId) {
            // Implement view functionality
            window.location.href = 'view_assignment.php?id=' + assignmentId;
        }

        function deleteAssignment(assignmentId) {
            if (confirm('Are you sure you want to delete this assignment?')) {
                fetch('delete_assignment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'assignment_id=' + assignmentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                });
            }
        }
    </script>
</body>
</html>
