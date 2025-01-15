<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireStudent();

$studentId = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

try {
    // Get student information
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            c.name as class_name
        FROM students s
        LEFT JOIN classes c ON c.level = s.class_level AND c.section = s.section
        WHERE s.id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    // Get monthly attendance
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            s.name as subject_name,
            t.first_name as teacher_first_name,
            t.last_name as teacher_last_name
        FROM attendance a
        LEFT JOIN subjects s ON s.id = a.subject_id
        LEFT JOIN teachers t ON t.id = a.teacher_id
        WHERE a.student_id = ? 
        AND MONTH(a.date) = ? 
        AND YEAR(a.date) = ?
        ORDER BY a.date DESC, a.period
    ");
    $stmt->execute([$studentId, $month, $year]);
    $attendance = $stmt->fetchAll();

    // Calculate statistics
    $totalDays = count($attendance);
    $presentDays = count(array_filter($attendance, fn($a) => $a['status'] === 'present'));
    $absentDays = count(array_filter($attendance, fn($a) => $a['status'] === 'absent'));
    $lateDays = count(array_filter($attendance, fn($a) => $a['status'] === 'late'));
    
    $attendanceRate = $totalDays ? round(($presentDays / $totalDays) * 100, 1) : 0;

    // Group attendance by date
    $groupedAttendance = [];
    foreach ($attendance as $record) {
        $groupedAttendance[date('Y-m-d', strtotime($record['date']))][] = $record;
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Get list of months for filter
$months = [];
for ($i = 1; $i <= 12; $i++) {
    $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance History - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .attendance-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            padding: 20px;
        }
        .calendar-day {
            aspect-ratio: 1;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }
        .calendar-day:hover {
            transform: scale(1.05);
        }
        .calendar-day.present {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .calendar-day.absent {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .calendar-day.late {
            background: #fff3cd;
            border: 1px solid #ffeeba;
        }
        .calendar-day .date {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .calendar-day .status {
            font-size: 0.8rem;
            margin-top: 5px;
        }
        .stats-card {
            border-radius: 15px;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .attendance-detail {
            border-left: 4px solid;
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .attendance-detail.present {
            border-color: #28a745;
            background: #f8fff9;
        }
        .attendance-detail.absent {
            border-color: #dc3545;
            background: #fff8f8;
        }
        .attendance-detail.late {
            border-color: #ffc107;
            background: #fffdf8;
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
            <span class="text">Attendance History</span>
        </div>

        <div class="container-fluid px-4">
            <!-- Attendance Overview -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card h-100 bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Attendance Rate</h6>
                                    <h3 class="mb-0"><?php echo $attendanceRate; ?>%</h3>
                                </div>
                                <div>
                                    <i class='bx bx-line-chart bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card h-100 bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Present Days</h6>
                                    <h3 class="mb-0"><?php echo $presentDays; ?></h3>
                                </div>
                                <div>
                                    <i class='bx bx-check-circle bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card h-100 bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Absent Days</h6>
                                    <h3 class="mb-0"><?php echo $absentDays; ?></h3>
                                </div>
                                <div>
                                    <i class='bx bx-x-circle bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card h-100 bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Late Days</h6>
                                    <h3 class="mb-0"><?php echo $lateDays; ?></h3>
                                </div>
                                <div>
                                    <i class='bx bx-time bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Month Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="month" class="form-label">Month</label>
                            <select name="month" id="month" class="form-select">
                                <?php foreach ($months as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo $key == $month ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="year" class="form-label">Year</label>
                            <select name="year" id="year" class="form-select">
                                <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                    <option value="<?php echo $y; ?>" 
                                            <?php echo $y == $year ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class='bx bx-filter-alt'></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <!-- Attendance Calendar -->
                <div class="col-xl-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bx-calendar me-1'></i>
                            Monthly Calendar
                        </div>
                        <div class="card-body">
                            <div class="attendance-calendar">
                                <?php
                                $firstDay = mktime(0, 0, 0, $month, 1, $year);
                                $daysInMonth = date('t', $firstDay);
                                $startDay = date('N', $firstDay) - 1;

                                // Add empty cells for days before the 1st
                                for ($i = 0; $i < $startDay; $i++) {
                                    echo '<div></div>';
                                }

                                // Calendar days
                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                    $dayRecords = $groupedAttendance[$date] ?? [];
                                    $status = 'no-record';
                                    if (!empty($dayRecords)) {
                                        $statuses = array_column($dayRecords, 'status');
                                        if (in_array('absent', $statuses)) {
                                            $status = 'absent';
                                        } elseif (in_array('late', $statuses)) {
                                            $status = 'late';
                                        } else {
                                            $status = 'present';
                                        }
                                    }
                                    ?>
                                    <div class="calendar-day <?php echo $status; ?>" 
                                         data-bs-toggle="modal" 
                                         data-bs-target="#dayModal"
                                         data-date="<?php echo $date; ?>">
                                        <div class="date"><?php echo $day; ?></div>
                                        <?php if ($status !== 'no-record'): ?>
                                            <div class="status">
                                                <?php echo ucfirst($status); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Chart -->
                <div class="col-xl-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class='bx bx-pie-chart-alt-2 me-1'></i>
                            Attendance Distribution
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed List -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class='bx bx-list-ul me-1'></i>
                    Detailed Attendance Records
                </div>
                <div class="card-body">
                    <?php foreach ($groupedAttendance as $date => $records): ?>
                        <h6 class="mb-3"><?php echo date('l, F j, Y', strtotime($date)); ?></h6>
                        <?php foreach ($records as $record): ?>
                            <div class="attendance-detail <?php echo $record['status']; ?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Period <?php echo $record['period']; ?></strong>
                                        (<?php echo date('h:i A', strtotime($record['time'])); ?>)
                                    </div>
                                    <div class="col-md-3">
                                        <?php echo htmlspecialchars($record['subject_name']); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <?php 
                                        echo htmlspecialchars($record['teacher_first_name'] . ' ' . 
                                            $record['teacher_last_name']); 
                                        ?>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="badge bg-<?php 
                                            echo $record['status'] === 'present' ? 'success' : 
                                                 ($record['status'] === 'absent' ? 'danger' : 'warning');
                                        ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                        <?php if ($record['remarks']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($record['remarks']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Day Detail Modal -->
    <div class="modal fade" id="dayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Attendance Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="dayDetails"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script>
        // Attendance Chart
        new Chart(document.getElementById('attendanceChart'), {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data: [<?php echo "$presentDays, $absentDays, $lateDays"; ?>],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Day Modal
        const attendanceData = <?php echo json_encode($groupedAttendance); ?>;
        
        $('#dayModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const date = button.data('date');
            const dayRecords = attendanceData[date] || [];
            
            let html = `<h6>${new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })}</h6>`;
            
            if (dayRecords.length === 0) {
                html += '<p class="text-muted">No attendance records for this day</p>';
            } else {
                dayRecords.forEach(record => {
                    const statusClass = record.status === 'present' ? 'success' : 
                                      (record.status === 'absent' ? 'danger' : 'warning');
                    html += `
                        <div class="attendance-detail ${record.status}">
                            <div class="mb-2">
                                <strong>Period ${record.period}</strong>
                                (${new Date(record.time).toLocaleTimeString('en-US', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })})
                            </div>
                            <div>${record.subject_name}</div>
                            <div>${record.teacher_first_name} ${record.teacher_last_name}</div>
                            <div>
                                <span class="badge bg-${statusClass}">
                                    ${record.status.charAt(0).toUpperCase() + record.status.slice(1)}
                                </span>
                            </div>
                            ${record.remarks ? `<small class="text-muted">${record.remarks}</small>` : ''}
                        </div>
                    `;
                });
            }
            
            $('#dayDetails').html(html);
        });
    </script>
</body>
</html>
