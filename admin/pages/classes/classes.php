<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("
            INSERT INTO classes (name, section, class_teacher_id, academic_year)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['name'],
            $_POST['section'],
            $_POST['class_teacher_id'],
            $_POST['academic_year']
        ]);

        setFlashMessage('success', 'Class added successfully!');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error adding class: ' . $e->getMessage());
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get all classes with teacher information
try {
    $stmt = $conn->query("
        SELECT c.*, 
               CONCAT(t.first_name, ' ', t.last_name) as teacher_name
        FROM classes c
        LEFT JOIN teachers t ON c.class_teacher_id = t.id
        ORDER BY c.name, c.section
    ");
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching classes: ' . $e->getMessage());
    $classes = [];
}

// Get all teachers for dropdown
try {
    $stmt = $conn->query("
        SELECT id, first_name, last_name 
        FROM teachers 
        WHERE status = 'active'
        ORDER BY first_name, last_name
    ");
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching teachers: ' . $e->getMessage());
    $teachers = [];
}
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
                    <h4 class="mb-0">Classes</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        Add New Class
                    </button>
                </div>
                <div class="card-body">
                    <table id="classesTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Section</th>
                                <th>Class Teacher</th>
                                <th>Academic Year</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($class['name']); ?></td>
                                    <td><?php echo htmlspecialchars($class['section']); ?></td>
                                    <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewClass(<?php echo $class['id']; ?>)">
                                            View Students
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="editClass(<?php echo $class['id']; ?>)">
                                            Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteClass(<?php echo $class['id']; ?>)">
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

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Class Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="section" class="form-label">Section</label>
                            <input type="text" class="form-control" id="section" name="section" required>
                        </div>
                        <div class="mb-3">
                            <label for="class_teacher_id" class="form-label">Class Teacher</label>
                            <select class="form-select" id="class_teacher_id" name="class_teacher_id">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                   placeholder="e.g., 2024-2025" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Class</button>
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
            $('#classesTable').DataTable({
                order: [[0, 'asc'], [1, 'asc']]
            });
        });

        function viewClass(classId) {
            window.location.href = 'view_class.php?id=' + classId;
        }

        function editClass(classId) {
            window.location.href = 'edit_class.php?id=' + classId;
        }

        function deleteClass(classId) {
            if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
                fetch('delete_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'class_id=' + classId
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
