<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();

$student = null;
$error = null;

if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $student = $stmt->fetch();

        if (!$student) {
            $error = "Student not found";
        }
    } catch(PDOException $e) {
        $error = "Error fetching student data: " . $e->getMessage();
    }
} else {
    $error = "Student ID not provided";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Student - School Management System</title>
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
            <span class="text">Student Details</span>
        </div>

        <div class="container-fluid px-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
                <div class="row">
                    <div class="col-xl-4">
                        <!-- Student Profile Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Student Profile</h5>
                            </div>
                            <div class="card-body text-center">
                                <img src="<?php echo isset($student['photo']) ? $student['photo'] : '../../../Asset/images/default-avatar.png'; ?>" 
                                     alt="Student Photo" 
                                     class="rounded-circle mb-3" 
                                     style="width: 150px; height: 150px; object-fit: cover;">
                                <h5 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                                <p class="text-muted mb-3">Admission No: <?php echo htmlspecialchars($student['admission_number']); ?></p>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" onclick="window.location.href='edit.php?id=<?php echo $student['id']; ?>'">
                                        <i class='bx bx-edit-alt'></i> Edit Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <!-- Student Details Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Student Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Full Name</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Date of Birth</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars(date('F j, Y', strtotime($student['date_of_birth']))); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Gender</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars(ucfirst($student['gender'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Class</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($student['class_level'] . ' - ' . $student['section']); ?>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Admission Date</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars(date('F j, Y', strtotime($student['admission_date']))); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Information Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Parent Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-0">Parent Name</p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($student['parent_name']); ?>
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
                                            <?php echo htmlspecialchars($student['parent_phone']); ?>
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
                                            <?php echo htmlspecialchars($student['parent_email'] ?? 'Not provided'); ?>
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
                                            <?php echo nl2br(htmlspecialchars($student['address'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Back Button -->
            <div class="mb-4">
                <a href="list.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to Students List
                </a>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sidebar.js"></script>
</body>
</html>
