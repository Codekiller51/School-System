<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireStudent();

$studentId = $_SESSION['user_id'];

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

    // Get all exam results grouped by term
    $stmt = $conn->prepare("
        SELECT 
            e.id as exam_id,
            e.name as exam_name,
            e.term,
            e.max_marks,
            e.pass_marks,
            s.id as subject_id,
            s.name as subject_name,
            er.marks,
            er.remarks,
            er.created_at,
            t.first_name as teacher_first_name,
            t.last_name as teacher_last_name
        FROM exam_results er
        JOIN exams e ON e.id = er.exam_id
        JOIN subjects s ON s.id = er.subject_id
        JOIN teachers t ON t.id = er.teacher_id
        WHERE er.student_id = ?
        ORDER BY e.term DESC, e.created_at DESC, s.name
    ");
    $stmt->execute([$studentId]);
    $results = $stmt->fetchAll();

    // Group results by term
    $groupedResults = [];
    foreach ($results as $result) {
        $groupedResults[$result['term']][] = $result;
    }

    // Calculate statistics
    $totalMarks = array_sum(array_column($results, 'marks'));
    $totalMaxMarks = array_sum(array_column($results, 'max_marks'));
    $averagePercentage = $totalMaxMarks > 0 ? ($totalMarks / $totalMaxMarks) * 100 : 0;
    $passedExams = count(array_filter($results, function($r) {
        return $r['marks'] >= $r['pass_marks'];
    }));
    $totalExams = count($results);
    $passRate = $totalExams > 0 ? ($passedExams / $totalExams) * 100 : 0;

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Results - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .stats-card {
            border-radius: 15px;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .term-header {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .subject-row {
            transition: background-color 0.2s;
        }
        .subject-row:hover {
            background-color: #f8f9fa;
        }
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 250px;
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
            <span class="text">Exam Results</span>
        </div>

        <div class="container-fluid px-4">
            <!-- Performance Overview -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card h-100 bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Overall Average</h6>
                                    <h3 class="mb-0"><?php echo round($averagePercentage, 1); ?>%</h3>
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
                                    <h6 class="text-white-50">Pass Rate</h6>
                                    <h3 class="mb-0"><?php echo round($passRate, 1); ?>%</h3>
                                </div>
                                <div>
                                    <i class='bx bx-check-circle bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card h-100 bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Total Exams</h6>
                                    <h3 class="mb-0"><?php echo $totalExams; ?></h3>
                                </div>
                                <div>
                                    <i class='bx bx-book-open bx-lg'></i>
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
                                    <h6 class="text-white-50">Passed Exams</h6>
                                    <h3 class="mb-0"><?php echo $passedExams; ?></h3>
                                </div>
                                <div>
                                    <i class='bx bx-trophy bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Charts -->
            <div class="row mb-4">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bx-line-chart me-1'></i>
                            Performance Trend
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bx-pie-chart-alt-2 me-1'></i>
                            Subject Distribution
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="subjectChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results by Term -->
            <?php foreach ($groupedResults as $term => $termResults): ?>
                <div class="term-header">
                    <h5 class="mb-0">Term <?php echo $term; ?></h5>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Exam</th>
                                        <th>Marks</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                        <th>Teacher</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($termResults as $result): ?>
                                        <tr class="subject-row">
                                            <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                            <td>
                                                <?php echo $result['marks']; ?>/<?php echo $result['max_marks']; ?>
                                                <div class="progress mt-1">
                                                    <?php 
                                                    $percentage = ($result['marks'] / $result['max_marks']) * 100;
                                                    $progressClass = $percentage >= $result['pass_marks'] ? 'bg-success' : 'bg-danger';
                                                    ?>
                                                    <div class="progress-bar <?php echo $progressClass; ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%">
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo round($percentage, 1); ?>%</td>
                                            <td>
                                                <?php if ($percentage >= $result['pass_marks']): ?>
                                                    <span class="badge bg-success">Passed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                echo htmlspecialchars($result['teacher_first_name'] . ' ' . 
                                                    $result['teacher_last_name']); 
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($result['remarks'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script>
        // Performance Chart
        const performanceData = <?php 
            $chartData = array_map(function($r) {
                return [
                    'exam' => $r['exam_name'],
                    'percentage' => ($r['marks'] / $r['max_marks']) * 100
                ];
            }, $results);
            echo json_encode($chartData);
        ?>;

        new Chart(document.getElementById('performanceChart'), {
            type: 'line',
            data: {
                labels: performanceData.map(d => d.exam),
                datasets: [{
                    label: 'Performance (%)',
                    data: performanceData.map(d => d.percentage),
                    borderColor: '#4e73df',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Subject Distribution Chart
        const subjectData = <?php 
            $subjectAverages = [];
            foreach ($results as $result) {
                $subject = $result['subject_name'];
                if (!isset($subjectAverages[$subject])) {
                    $subjectAverages[$subject] = ['total' => 0, 'count' => 0];
                }
                $subjectAverages[$subject]['total'] += ($result['marks'] / $result['max_marks']) * 100;
                $subjectAverages[$subject]['count']++;
            }
            $chartData = array_map(function($subject, $data) {
                return [
                    'subject' => $subject,
                    'average' => $data['total'] / $data['count']
                ];
            }, array_keys($subjectAverages), $subjectAverages);
            echo json_encode(array_values($chartData));
        ?>;

        new Chart(document.getElementById('subjectChart'), {
            type: 'doughnut',
            data: {
                labels: subjectData.map(d => d.subject),
                datasets: [{
                    data: subjectData.map(d => d.average),
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
                        '#e74a3b', '#858796', '#5a5c69', '#2c9faf'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
</body>
</html>
