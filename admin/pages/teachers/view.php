<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();

$teacher = null;
$error = null;

if (isset($_GET['id'])) {
    try {
        // Get teacher details
        $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $teacher = $stmt->fetch();

        if ($teacher) {
            // Get assigned subjects
            $stmt = $conn->prepare("
                SELECT s.* 
                FROM subjects s 
                JOIN teacher_subjects ts ON s.id = ts.subject_id 
                WHERE ts.teacher_id = ? AND ts.academic_year = ?
            ");
            $stmt->execute([$_GET['id'], getAcademicYear()]);
            $subjects = $stmt->fetchAll();
        } else {
            $error = "Teacher not found";
        }
    } catch(PDOException $e) {
        $error = "Error fetching teacher data: " . $e->getMessage();
    }
} else {
    $error = "Teacher ID not provided";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Teacher - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar close">
        <!-- Sidebar content will be loaded via JavaScript -->
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Teacher Details</span>
        </div>

        <div class="container-fluid px-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
                <div class="row">
                    <div class="col-xl-4">
                        <!-- Teacher Profile Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Teacher Profile</h5>
                            </div>
                            <div class="card-body text-center">
                                <img src="<?php echo isset($teacher['photo']) ? $teacher['photo'] : '../../../Asset/images/default-avatar.png'; ?>" 
                                     alt="Teacher Photo" 
                                     class="rounded-circle mb-3" 
                                     style="width: 150px; height: 150px; object-fit: cover;">
                                <h5 class="mb-1"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                                <p class="text-muted mb-3">Employee ID: <?php echo htmlspecialchars($teacher['employee_id']); ?></p>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" onclick="window.location.href='edit.php?id=<?php echo $teacher['id']; ?>'">
                                        <i class='bx bx-edit-alt'></i> Edit Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <!-- Teacher Details Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Teacher Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Full Name</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Department</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($teacher['department']); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Qualification</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo nl2br(htmlspecialchars($teacher['qualification'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Joining Date</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars(date('F j, Y', strtotime($teacher['joining_date']))); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Contact Number</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($teacher['phone']); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Email</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($teacher['email']); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Address</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo nl2br(htmlspecialchars($teacher['address'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Subjects Card -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Assigned Subjects</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignSubjectModal">
                                    <i class='bx bx-plus'></i> Assign Subject
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Subject Code</th>
                                                <th>Subject Name</th>
                                                <th>Class Level</th>
                                                <th>Credits</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subjects as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['class_level']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['credits']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" onclick="removeSubject(<?php echo $teacher['id']; ?>, <?php echo $subject['id']; ?>)">
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

            <!-- Back Button -->
            <div class="mb-4">
                <a href="list.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to Teachers List
                </a>
            </div>
        </div>

        <!-- Assign Subject Modal -->
        <div class="modal fade" id="assignSubjectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="assignSubjectForm" action="../../../api/teachers/assign_subject.php" method="POST">
                            <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php
                                    $stmt = $conn->query("SELECT * FROM subjects ORDER BY name");
                                    while ($row = $stmt->fetch()) {
                                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['class_level']) . ")</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Assign Subject</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script>
        // Handle subject assignment
        $('#assignSubjectForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Error assigning subject');
                    }
                },
                error: function() {
                    alert('Error assigning subject');
                }
            });
        });

        // Handle subject removal
        function removeSubject(teacherId, subjectId) {
            if (confirm('Are you sure you want to remove this subject assignment?')) {
                $.ajax({
                    url: '../../../api/teachers/remove_subject.php',
                    type: 'POST',
                    data: {
                        teacher_id: teacherId,
                        subject_id: subjectId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Error removing subject');
                        }
                    },
                    error: function() {
                        alert('Error removing subject');
                    }
                });
            }
        }
    </script>
</body>
</html>
