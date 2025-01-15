<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../auth/login.php');
    exit();
}

// Get teacher info
$teacher_id = $_SESSION['user_id'];
$teacher_query = "SELECT * FROM teachers WHERE id = ?";
$stmt = $conn->prepare($teacher_query);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Get teacher's subjects
$subjects_query = "SELECT s.* FROM subjects s 
                  JOIN teacher_subjects ts ON s.id = ts.subject_id 
                  WHERE ts.teacher_id = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get teacher's class (if class teacher)
$class_query = "SELECT c.*, 
                (SELECT COUNT(*) FROM students s 
                 JOIN class_students cs ON s.id = cs.student_id 
                 WHERE cs.class_id = c.id) as student_count
                FROM classes c 
                WHERE c.class_teacher_id = ?";
$stmt = $conn->prepare($class_query);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

// Get today's attendance for teacher's class
$attendance = ['present' => 0, 'absent' => 0, 'late' => 0];
if ($class) {
    $attendance_query = "SELECT status, COUNT(*) as count 
                        FROM attendance 
                        WHERE class_id = ? AND date = CURRENT_DATE 
                        GROUP BY status";
    $stmt = $conn->prepare($attendance_query);
    $stmt->bind_param('i', $class['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendance[$row['status']] = $row['count'];
    }
}

// Get upcoming exams
$exams_query = "SELECT e.*, s.name as subject_name 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                JOIN teacher_subjects ts ON s.id = ts.subject_id 
                WHERE ts.teacher_id = ? AND e.start_date >= CURRENT_DATE 
                ORDER BY e.start_date ASC LIMIT 5";
$stmt = $conn->prepare($exams_query);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$upcoming_exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get notifications
$notif_query = "SELECT nq.*, s.first_name, s.last_name, s.class_level, s.section
                FROM notification_queue nq
                JOIN students s ON nq.student_id = s.id
                JOIN class_students cs ON s.id = cs.student_id
                JOIN classes c ON cs.class_id = c.id
                WHERE nq.status = 'pending' AND c.class_teacher_id = ?
                ORDER BY nq.created_at DESC LIMIT 5";
$stmt = $conn->prepare($notif_query);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - School Management System</title>
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
                    <span class="link_name">My Subjects</span>
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
                <a href="marks.php">
                    <i class='bx bxs-report'></i>
                    <span class="link_name">Marks</span>
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
                <img src="../assets/img/teacher-avatar.png" alt="Teacher Avatar">
                <span class="admin_name"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></span>
                <i class='bx bx-chevron-down'></i>
            </div>
        </nav>

        <div class="home-content">
            <!-- Overview Boxes -->
            <div class="overview-boxes">
                <?php if ($class): ?>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Present</div>
                        <div class="number"><?php echo $attendance['present']; ?></div>
                        <div class="indicator">
                            <i class='bx bxs-user-check'></i>
                            <span class="text">Today's Attendance</span>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Absent</div>
                        <div class="number"><?php echo $attendance['absent']; ?></div>
                        <div class="indicator">
                            <i class='bx bxs-user-x'></i>
                            <span class="text">Today's Absences</span>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Late</div>
                        <div class="number"><?php echo $attendance['late']; ?></div>
                        <div class="indicator">
                            <i class='bx bxs-time'></i>
                            <span class="text">Late Arrivals</span>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Students</div>
                        <div class="number"><?php echo $class['student_count']; ?></div>
                        <div class="indicator">
                            <i class='bx bxs-graduation'></i>
                            <span class="text">Total Students</span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">My Subjects</div>
                        <div class="number"><?php echo count($subjects); ?></div>
                        <div class="indicator">
                            <i class='bx bxs-book'></i>
                            <span class="text">Total Subjects</span>
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
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <div class="sales-boxes">
                <div class="recent-sales box">
                    <div class="title">Upcoming Exams</div>
                    <div class="sales-details">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_exams as $exam): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo match($exam['type']) {
                                                'midterm' => 'warning',
                                                'final' => 'danger',
                                                default => 'info'
                                            }; ?>"><?php echo ucfirst($exam['type']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($exam['date'])); ?></td>
                                        <td><?php echo $exam['duration']; ?> mins</td>
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
                                        <p>For: <?php echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']); ?></p>
                                        <small>Class: <?php echo htmlspecialchars($notif['class_level'] . '-' . $notif['section']); ?></small>
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
