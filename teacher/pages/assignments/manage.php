<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireTeacher();

$teacherId = $_SESSION['user_id'];

try {
    // Get teacher's classes and subjects
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            ts.class_level,
            ts.section,
            s.id as subject_id,
            s.name as subject_name
        FROM teacher_subjects ts
        JOIN subjects s ON s.id = ts.subject_id
        WHERE ts.teacher_id = ?
        ORDER BY ts.class_level, ts.section, s.name
    ");
    $stmt->execute([$teacherId]);
    $teacherSubjects = $stmt->fetchAll();

    // Get active assignments
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as subject_name,
            COUNT(DISTINCT sa.student_id) as submissions_count
        FROM assignments a
        JOIN subjects s ON s.id = a.subject_id
        LEFT JOIN student_assignments sa ON sa.assignment_id = a.id
        WHERE a.teacher_id = ?
        GROUP BY a.id
        ORDER BY a.due_date DESC
    ");
    $stmt->execute([$teacherId]);
    $assignments = $stmt->fetchAll();

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="sidebar close">
        <?php include '../../includes/sidebar.php'; ?>
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Manage Assignments</span>
        </div>

        <div class="container-fluid px-4">
            <!-- Create Assignment Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class='bx bx-plus-circle me-1'></i>
                    Create New Assignment
                </div>
                <div class="card-body">
                    <form id="assignmentForm" method="post" action="../../../api/assignments/create.php" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject & Class</label>
                                <select class="form-select" id="subject" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($teacherSubjects as $subject): ?>
                                        <option value="<?php echo $subject['subject_id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_name'] . 
                                                ' (Class ' . $subject['class_level'] . '-' . 
                                                $subject['section'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="datetime-local" class="form-control" id="due_date" name="due_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="max_marks" class="form-label">Maximum Marks</label>
                                <input type="number" class="form-control" id="max_marks" name="max_marks" required>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="col-12">
                                <label for="attachment" class="form-label">Attachment (optional)</label>
                                <input type="file" class="form-control" id="attachment" name="attachment">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-save'></i> Create Assignment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Active Assignments -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class='bx bx-list-ul me-1'></i>
                    Active Assignments
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Due Date</th>
                                    <th>Submissions</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                        <td>
                                            <?php 
                                            $dueDate = new DateTime($assignment['due_date']);
                                            echo $dueDate->format('M d, Y h:i A'); 
                                            ?>
                                        </td>
                                        <td>
                                            <a href="submissions.php?id=<?php echo $assignment['id']; ?>">
                                                <?php echo $assignment['submissions_count']; ?> submissions
                                            </a>
                                        </td>
                                        <td>
                                            <?php
                                            $now = new DateTime();
                                            if ($now > $dueDate) {
                                                echo '<span class="badge bg-danger">Overdue</span>';
                                            } elseif ($assignment['status'] === 'draft') {
                                                echo '<span class="badge bg-warning">Draft</span>';
                                            } else {
                                                echo '<span class="badge bg-success">Active</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit.php?id=<?php echo $assignment['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class='bx bx-edit'></i>
                                                </a>
                                                <a href="submissions.php?id=<?php echo $assignment['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class='bx bx-folder'></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger delete-assignment"
                                                        data-id="<?php echo $assignment['id']; ?>">
                                                    <i class='bx bx-trash'></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script>
        $(document).ready(function() {
            // Handle assignment form submission
            $('#assignmentForm').submit(function(e) {
                e.preventDefault();
                
                let formData = new FormData(this);
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Assignment created successfully!');
                            location.reload();
                        } else {
                            alert('Error creating assignment: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error creating assignment. Please try again.');
                    }
                });
            });

            // Handle assignment deletion
            $('.delete-assignment').click(function() {
                if (confirm('Are you sure you want to delete this assignment?')) {
                    const assignmentId = $(this).data('id');
                    
                    $.post('../../../api/assignments/delete.php', {
                        id: assignmentId
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting assignment: ' + response.message);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
