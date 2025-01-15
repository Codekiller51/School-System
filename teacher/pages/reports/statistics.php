<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireTeacher();

$teacherId = $_SESSION['user_id'];
$classLevel = $_GET['class'] ?? '';
$section = $_GET['section'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

try {
    // Get teacher's assigned classes
    $stmt = $conn->prepare("
        SELECT DISTINCT c.level, c.section
        FROM classes c
        JOIN teacher_subjects ts ON ts.class_level = c.level AND ts.section = c.section
        WHERE ts.teacher_id = ?
        ORDER BY c.level, c.section
    ");
    $stmt->execute([$teacherId]);
    $assignedClasses = $stmt->fetchAll();

    // Get attendance statistics if class is selected
    $attendanceStats = [];
    $examStats = [];
    $subjectPerformance = [];
    
    if ($classLevel && $section) {
        // Overall attendance statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT a.student_id) as total_students,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as total_absent,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as total_late,
                COUNT(*) as total_records
            FROM attendance a
            WHERE a.class_level = ? 
            AND a.section = ?
            AND a.date BETWEEN ? AND ?
        ");
        $stmt->execute([$classLevel, $section, $startDate, $endDate]);
        $attendanceStats = $stmt->fetch();

        // Daily attendance trend
        $stmt = $conn->prepare("
            SELECT 
                a.date,
                COUNT(DISTINCT a.student_id) as total_students,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count
            FROM attendance a
            WHERE a.class_level = ? 
            AND a.section = ?
            AND a.date BETWEEN ? AND ?
            GROUP BY a.date
            ORDER BY a.date
        ");
        $stmt->execute([$classLevel, $section, $startDate, $endDate]);
        $attendanceTrend = $stmt->fetchAll();

        // Exam performance statistics
        $stmt = $conn->prepare("
            SELECT 
                e.name as exam_name,
                s.name as subject_name,
                AVG(er.marks) as average_marks,
                MIN(er.marks) as min_marks,
                MAX(er.marks) as max_marks,
                COUNT(DISTINCT er.student_id) as students_appeared
            FROM exam_results er
            JOIN exams e ON e.id = er.exam_id
            JOIN subjects s ON s.id = er.subject_id
            JOIN teacher_subjects ts ON ts.subject_id = er.subject_id
            WHERE ts.teacher_id = ?
            AND ts.class_level = ?
            AND ts.section = ?
            GROUP BY e.id, s.id
            ORDER BY e.start_date DESC, s.name
        ");
        $stmt->execute([$teacherId, $classLevel, $section]);
        $examStats = $stmt->fetchAll();

        // Subject-wise performance
        $stmt = $conn->prepare("
            SELECT 
                s.name as subject_name,
                AVG(er.marks) as average_marks,
                COUNT(DISTINCT er.student_id) as total_students,
                SUM(CASE WHEN er.marks >= e.pass_marks THEN 1 ELSE 0 END) as passed_students
            FROM exam_results er
            JOIN subjects s ON s.id = er.subject_id
            JOIN exams e ON e.id = er.exam_id
            JOIN teacher_subjects ts ON ts.subject_id = er.subject_id
            WHERE ts.teacher_id = ?
            AND ts.class_level = ?
            AND ts.section = ?
            GROUP BY s.id
            ORDER BY s.name
        ");
        $stmt->execute([$teacherId, $classLevel, $section]);
        $subjectPerformance = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Statistics - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="sidebar close">
        <?php include '../../includes/sidebar.php'; ?>
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Class Statistics</span>
        </div>

        <div class="container-fluid px-4">
            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="class" class="form-label">Class</label>
                            <select name="class" id="class" class="form-select" required>
                                <option value="">Select Class</option>
                                <?php foreach ($assignedClasses as $class): ?>
                                    <option value="<?php echo $class['level']; ?>"
                                            <?php echo $classLevel === $class['level'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['level']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="section" class="form-label">Section</label>
                            <select name="section" id="section" class="form-select" required>
                                <option value="">Select Section</option>
                                <?php if ($classLevel): ?>
                                    <?php foreach ($assignedClasses as $class): ?>
                                        <?php if ($class['level'] === $classLevel): ?>
                                            <option value="<?php echo $class['section']; ?>"
                                                    <?php echo $section === $class['section'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['section']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="<?php echo $startDate; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="<?php echo $endDate; ?>" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-filter-alt'></i> Apply Filter
                            </button>
                            <?php if ($classLevel && $section): ?>
                                <a href="export.php?class=<?php echo urlencode($classLevel); ?>&section=<?php echo urlencode($section); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                                   class="btn btn-success">
                                    <i class='bx bx-export'></i> Export Report
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($classLevel && $section && !empty($attendanceStats)): ?>
                <!-- Attendance Overview -->
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class='bx bx-bar-chart-alt-2 me-1'></i>
                                Attendance Overview
                            </div>
                            <div class="card-body">
                                <canvas id="attendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class='bx bx-line-chart me-1'></i>
                                Daily Attendance Trend
                            </div>
                            <div class="card-body">
                                <canvas id="attendanceTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam Performance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class='bx bx-trophy me-1'></i>
                        Exam Performance
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Subject</th>
                                        <th>Average Marks</th>
                                        <th>Highest</th>
                                        <th>Lowest</th>
                                        <th>Students Appeared</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($examStats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['exam_name']); ?></td>
                                            <td><?php echo htmlspecialchars($stat['subject_name']); ?></td>
                                            <td><?php echo number_format($stat['average_marks'], 1); ?></td>
                                            <td><?php echo $stat['max_marks']; ?></td>
                                            <td><?php echo $stat['min_marks']; ?></td>
                                            <td><?php echo $stat['students_appeared']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Subject Performance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class='bx bx-book me-1'></i>
                        Subject-wise Performance
                    </div>
                    <div class="card-body">
                        <canvas id="subjectPerformanceChart"></canvas>
                    </div>
                </div>

                <script>
                    // Attendance Overview Chart
                    new Chart(document.getElementById('attendanceChart'), {
                        type: 'pie',
                        data: {
                            labels: ['Present', 'Absent', 'Late'],
                            datasets: [{
                                data: [
                                    <?php echo $attendanceStats['total_present']; ?>,
                                    <?php echo $attendanceStats['total_absent']; ?>,
                                    <?php echo $attendanceStats['total_late']; ?>
                                ],
                                backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                            }]
                        }
                    });

                    // Attendance Trend Chart
                    new Chart(document.getElementById('attendanceTrendChart'), {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_column($attendanceTrend, 'date')); ?>,
                            datasets: [{
                                label: 'Attendance %',
                                data: <?php 
                                    echo json_encode(array_map(function($day) {
                                        return ($day['present_count'] / $day['total_students']) * 100;
                                    }, $attendanceTrend));
                                ?>,
                                borderColor: '#4e73df',
                                tension: 0.1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100
                                }
                            }
                        }
                    });

                    // Subject Performance Chart
                    new Chart(document.getElementById('subjectPerformanceChart'), {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode(array_column($subjectPerformance, 'subject_name')); ?>,
                            datasets: [{
                                label: 'Average Marks',
                                data: <?php echo json_encode(array_column($subjectPerformance, 'average_marks')); ?>,
                                backgroundColor: '#4e73df'
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
            <?php elseif ($classLevel && $section): ?>
                <div class="alert alert-info">
                    No data available for the selected period.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sidebar.js"></script>
</body>
</html>
