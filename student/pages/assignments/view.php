<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireStudent();

$studentId = $_SESSION['user_id'];
$assignmentId = $_GET['id'] ?? 0;

try {
    // Get assignment details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as subject_name,
            t.first_name as teacher_first_name,
            t.last_name as teacher_last_name,
            sa.id as submission_id,
            sa.submission_text,
            sa.file_path as submitted_file,
            sa.submitted_at,
            sa.marks,
            sa.feedback
        FROM assignments a
        JOIN subjects s ON s.id = a.subject_id
        JOIN teachers t ON t.id = a.teacher_id
        LEFT JOIN student_assignments sa ON sa.assignment_id = a.id AND sa.student_id = ?
        WHERE a.id = ?
    ");
    $stmt->execute([$studentId, $assignmentId]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        die("Assignment not found");
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment Details - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .assignment-header {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .submission-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-upload:hover {
            border-color: #6c757d;
            background: #f8f9fa;
        }
        .feedback-card {
            background: #e7f3ff;
            border-left: 4px solid #4e73df;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar close">
        <?php include '../../includes/sidebar.php'; ?>
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Assignment Details</span>
        </div>

        <div class="container-fluid px-4">
            <!-- Assignment Header -->
            <div class="assignment-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                        <p class="mb-2">
                            <i class='bx bx-book me-1'></i>
                            <?php echo htmlspecialchars($assignment['subject_name']); ?> |
                            <i class='bx bx-user me-1'></i>
                            <?php echo htmlspecialchars($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?>
                        </p>
                        <p class="mb-0">
                            <i class='bx bx-calendar me-1'></i>
                            Due: <?php echo date('M d, Y h:i A', strtotime($assignment['due_date'])); ?>
                            <?php if (strtotime($assignment['due_date']) < time()): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <h5 class="mb-1">Maximum Marks: <?php echo $assignment['max_marks']; ?></h5>
                        <?php if ($assignment['submission_id']): ?>
                            <span class="badge bg-success">Submitted</span>
                            <?php if ($assignment['marks']): ?>
                                <span class="badge bg-primary">Marks: <?php echo $assignment['marks']; ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-warning">Not Submitted</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Assignment Description -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class='bx bx-detail me-1'></i>
                            Assignment Description
                        </div>
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                            
                            <?php if ($assignment['attachment_path']): ?>
                                <div class="mt-3">
                                    <h6>Attachment:</h6>
                                    <a href="../../<?php echo $assignment['attachment_path']; ?>" 
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class='bx bx-download'></i> Download Assignment File
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Submission Form -->
                    <?php if (!$assignment['submission_id'] && strtotime($assignment['due_date']) > time()): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class='bx bx-upload me-1'></i>
                                Submit Assignment
                            </div>
                            <div class="card-body">
                                <form id="submissionForm" method="post" action="../../../api/assignments/submit.php" 
                                      enctype="multipart/form-data">
                                    <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="submission_text" class="form-label">Your Answer</label>
                                        <textarea class="form-control" id="submission_text" name="submission_text" 
                                                  rows="5" required></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Attachment (if required)</label>
                                        <div class="file-upload" id="dropZone">
                                            <i class='bx bx-cloud-upload bx-lg mb-2'></i>
                                            <p class="mb-0">Drag & drop your file here or click to browse</p>
                                            <input type="file" id="file" name="attachment" class="d-none">
                                        </div>
                                        <div id="fileInfo" class="small text-muted mt-2"></div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class='bx bx-send'></i> Submit Assignment
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Submitted Work -->
                    <?php if ($assignment['submission_id']): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class='bx bx-check-circle me-1'></i>
                                Your Submission
                                <small class="text-muted">
                                    (Submitted on <?php echo date('M d, Y h:i A', strtotime($assignment['submitted_at'])); ?>)
                                </small>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <?php echo nl2br(htmlspecialchars($assignment['submission_text'])); ?>
                                </div>

                                <?php if ($assignment['submitted_file']): ?>
                                    <div class="mb-3">
                                        <h6>Your Attachment:</h6>
                                        <a href="../../<?php echo $assignment['submitted_file']; ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class='bx bx-download'></i> View Submitted File
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if ($assignment['marks']): ?>
                                    <div class="feedback-card">
                                        <h6>Teacher's Feedback:</h6>
                                        <p class="mb-2">Marks: <?php echo $assignment['marks']; ?>/<?php echo $assignment['max_marks']; ?></p>
                                        <?php if ($assignment['feedback']): ?>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <!-- Submission Guidelines -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class='bx bx-info-circle me-1'></i>
                            Submission Guidelines
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class='bx bx-check text-success me-2'></i>
                                    Submit before the due date
                                </li>
                                <li class="mb-2">
                                    <i class='bx bx-check text-success me-2'></i>
                                    Follow the assignment instructions carefully
                                </li>
                                <li class="mb-2">
                                    <i class='bx bx-check text-success me-2'></i>
                                    Check your work before submission
                                </li>
                                <li>
                                    <i class='bx bx-check text-success me-2'></i>
                                    Upload files in supported formats only
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Assignment Status -->
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bx-time me-1'></i>
                            Status
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Status
                                    <?php if ($assignment['submission_id']): ?>
                                        <span class="badge bg-success">Submitted</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Due Date
                                    <span><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Time Left
                                    <?php
                                    $timeLeft = strtotime($assignment['due_date']) - time();
                                    if ($timeLeft > 0) {
                                        $days = floor($timeLeft / (60 * 60 * 24));
                                        $hours = floor(($timeLeft % (60 * 60 * 24)) / (60 * 60));
                                        echo "<span>{$days}d {$hours}h left</span>";
                                    } else {
                                        echo "<span class='text-danger'>Overdue</span>";
                                    }
                                    ?>
                                </li>
                            </ul>
                        </div>
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
            // File upload handling
            const dropZone = $('#dropZone');
            const fileInput = $('#file');
            const fileInfo = $('#fileInfo');

            dropZone.on('click', function() {
                fileInput.click();
            });

            dropZone.on('dragover', function(e) {
                e.preventDefault();
                dropZone.addClass('bg-light');
            });

            dropZone.on('dragleave', function(e) {
                e.preventDefault();
                dropZone.removeClass('bg-light');
            });

            dropZone.on('drop', function(e) {
                e.preventDefault();
                dropZone.removeClass('bg-light');
                const files = e.originalEvent.dataTransfer.files;
                handleFiles(files);
            });

            fileInput.on('change', function() {
                handleFiles(this.files);
            });

            function handleFiles(files) {
                if (files.length > 0) {
                    const file = files[0];
                    fileInfo.html(`Selected file: ${file.name} (${formatFileSize(file.size)})`);
                }
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Form submission
            $('#submissionForm').submit(function(e) {
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
                            alert('Assignment submitted successfully!');
                            location.reload();
                        } else {
                            alert('Error submitting assignment: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error submitting assignment. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>
