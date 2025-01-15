<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get student info
$student_id = $_SESSION['user_id'];
$student_query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get student's class info
$class_query = "SELECT c.* FROM classes c 
                JOIN class_students cs ON c.id = cs.class_id 
                WHERE cs.student_id = ?";
$stmt = $conn->prepare($class_query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

// Get attendance summary
$attendance_query = "SELECT status, COUNT(*) as count 
                    FROM attendance 
                    WHERE student_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) 
                    GROUP BY status";
$stmt = $conn->prepare($attendance_query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$attendance = ['present' => 0, 'absent' => 0, 'late' => 0];
while ($row = $result->fetch_assoc()) {
    $attendance[$row['status']] = $row['count'];
}
$total_days = array_sum($attendance);
$attendance_percentage = $total_days > 0 ? ($attendance['present'] / $total_days) * 100 : 0;

// Get upcoming exams
$exams_query = "SELECT e.*, s.name as subject_name 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                WHERE e.class_id = ? AND e.start_date >= CURRENT_DATE 
                ORDER BY e.start_date ASC LIMIT 5";
$stmt = $conn->prepare($exams_query);
$stmt->bind_param('i', $class['id']);
$stmt->execute();
$upcoming_exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent exam results
$results_query = "SELECT er.*, e.name as exam_name, s.name as subject_name 
                 FROM exam_results er 
                 JOIN exams e ON er.exam_id = e.id 
                 JOIN subjects s ON er.subject_id = s.id 
                 WHERE er.student_id = ? 
                 ORDER BY er.created_at DESC LIMIT 5";
$stmt = $conn->prepare($results_query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$exam_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get notifications
$notif_query = "SELECT nq.* 
                FROM notification_queue nq 
                WHERE nq.student_id = ? AND nq.status = 'pending'
                ORDER BY nq.created_at DESC LIMIT 5";
$stmt = $conn->prepare($notif_query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - School Management System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo-details">
            <i class='bx bxs-school logosmall'></i>
            <span class="logo_name">School System</span>
        </div>
        <ul class="nav-links">
            <li class="active">
                <a href="index.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="link_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="attendance.php">
                    <i class='bx bxs-calendar-check'></i>
                    <span class="link_name">Attendance</span>
                </a>
            </li>
            <li>
                <a href="subjects.php">
                    <i class='bx bxs-book'></i>
                    <span class="link_name">Subjects</span>
                </a>
            </li>
            <li>
                <a href="timetable.php">
                    <i class='bx bxs-calendar'></i>
                    <span class="link_name">Timetable</span>
                </a>
            </li>
            <li>
                <a href="assignments.php">
                    <i class='bx bxs-notepad'></i>
                    <span class="link_name">Assignments</span>
                </a>
            </li>
            <li>
                <a href="exams.php">
                    <i class='bx bxs-file'></i>
                    <span class="link_name">Exams</span>
                </a>
            </li>
            <li>
                <a href="results.php">
                    <i class='bx bxs-report'></i>
                    <span class="link_name">Results</span>
                </a>
            </li>
            <li>
                <a href="profile.php">
                    <i class='bx bxs-user-circle'></i>
                    <span class="link_name">Profile</span>
                </a>
            </li>
            <li>
                <a href="settings.php">
                    <i class='bx bxs-cog'></i>
                    <span class="link_name">Settings</span>
                </a>
            </li>
            <li>
                <a href="../auth/logout.php">
                    <i class='bx bxs-log-out'></i>
                    <span class="link_name">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <section class="home-section">
        <nav>
            <div class="sidebar-button">
                <i class='bx bx-menu sidebarBtn'></i>
                <span class="dashboard">Dashboard</span>
            </div>
            <div class="search-box">
                <input type="text" placeholder="Search...">
                <i class='bx bx-search'></i>
            </div>
            <div class="profile-details">
                <img src="../assets/img/student-avatar.png" alt="Student Avatar">
                <span class="admin_name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                <i class='bx bx-chevron-down'></i>
            </div>
        </nav>

        <div class="home-content">
            <!-- Overview Boxes -->
            <div class="overview-boxes">
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Attendance</div>
                        <div class="number"><?php echo number_format($attendance_percentage, 1); ?>%</div>
                        <div class="indicator">
                            <i class='bx bxs-calendar-check'></i>
                            <span class="text">Last 30 Days</span>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Present Days</div>
                        <div class="number"><?php echo $attendance['present']; ?></div>
                        <div class="indicator">
                            <i class='bx bxs-user-check'></i>
                            <span class="text">Out of <?php echo $total_days; ?> Days</span>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Upcoming Exams</div>
                        <div class="number"><?php echo count($upcoming_exams); ?></div>
                        <div class="indicator">
                            <i class='bx bxs-calendar'></i>
                            <span class="text">Scheduled Exams</span>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Recent Results</div>
                        <div class="number"><?php echo count($exam_results); ?></div>
                        <div class="indicator">
                            <i class='bx bxs-report'></i>
                            <span class="text">Latest Scores</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="sales-boxes">
                <div class="recent-sales box">
                    <div class="title">Recent Exam Results</div>
                    <div class="sales-details">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Score</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exam_results as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                        <td><?php echo $result['score']; ?>/<?php echo $result['total_marks']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo match($result['grade']) {
                                                'A' => 'success',
                                                'B' => 'primary',
                                                'C' => 'info',
                                                'D' => 'warning',
                                                default => 'danger'
                                            }; ?>"><?php echo $result['grade']; ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($result['date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="top-sales box">
                    <div class="title">Notifications</div>
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted text-center">No pending notifications</p>
                    <?php else: ?>
                        <div class="notification-list">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-item">
                                    <div class="icon">
                                        <?php echo match($notif['type']) {
                                            'absence' => '<i class="bx bxs-user-x text-danger"></i>',
                                            'exam_result' => '<i class="bx bxs-badge-check text-success"></i>',
                                            'discipline' => '<i class="bx bxs-error text-warning"></i>',
                                            'announcement' => '<i class="bx bxs-bell text-info"></i>'
                                        }; ?>
                                    </div>
                                    <div class="details">
                                        <h6><?php echo ucfirst($notif['type']); ?> Notification</h6>
                                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small>From: <?php echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']); ?></small>
                                    </div>
                                    <div class="time">
                                        <?php echo date('H:i', strtotime($notif['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script>
        let sidebar = document.querySelector(".sidebar");
        let sidebarBtn = document.querySelector(".sidebarBtn");
        sidebarBtn.onclick = function() {
            sidebar.classList.toggle("active");
            if(sidebar.classList.contains("active")){
                sidebarBtn.classList.replace("bx-menu" ,"bx-menu-alt-right");
            }else
                sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
