<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireTeacher();

$teacherId = $_SESSION['user_id'];
$currentWeek = isset($_GET['week']) ? $_GET['week'] : date('W');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

try {
    // Get teacher's timetable
    $stmt = $conn->prepare("
        SELECT 
            tt.*,
            s.name as subject_name,
            c.name as class_name,
            c.level as class_level,
            c.section
        FROM timetable tt
        JOIN subjects s ON s.id = tt.subject_id
        JOIN classes c ON c.level = tt.class_level AND c.section = tt.section
        WHERE tt.teacher_id = ?
        ORDER BY tt.day_of_week, tt.start_time
    ");
    $stmt->execute([$teacherId]);
    $timetableData = $stmt->fetchAll();

    // Organize timetable by day and time
    $timetable = [];
    $timeSlots = [];
    foreach ($timetableData as $slot) {
        $timetable[$slot['day_of_week']][$slot['start_time']] = $slot;
        $timeSlots[$slot['start_time']] = true;
    }
    $timeSlots = array_keys($timeSlots);
    sort($timeSlots);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

$days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Timetable - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .timetable-cell {
            min-height: 100px;
            border: 1px solid #dee2e6;
            padding: 10px;
        }
        .timetable-slot {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 5px;
        }
        .current-time {
            background-color: #e7f3ff;
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
            <span class="text">My Timetable</span>
        </div>

        <div class="container-fluid px-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class='bx bx-calendar me-1'></i>
                    Weekly Schedule
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <?php foreach ($days as $dayNum => $dayName): ?>
                                        <th><?php echo $dayName; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($timeSlots as $timeSlot): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            echo date('h:i A', strtotime($timeSlot)) . ' - ' . 
                                                 date('h:i A', strtotime($timeSlot) + 45*60); 
                                            ?>
                                        </td>
                                        <?php foreach ($days as $dayNum => $dayName): ?>
                                            <td class="timetable-cell <?php 
                                                echo (date('N') == $dayNum && 
                                                     date('H:i:s') >= $timeSlot && 
                                                     date('H:i:s') <= date('H:i:s', strtotime($timeSlot) + 45*60)) 
                                                     ? 'current-time' : ''; 
                                            ?>">
                                                <?php if (isset($timetable[$dayNum][$timeSlot])): 
                                                    $slot = $timetable[$dayNum][$timeSlot];
                                                ?>
                                                    <div class="timetable-slot">
                                                        <strong><?php echo htmlspecialchars($slot['subject_name']); ?></strong>
                                                        <br>
                                                        Class: <?php echo htmlspecialchars($slot['class_level'] . '-' . $slot['section']); ?>
                                                        <br>
                                                        Room: <?php echo htmlspecialchars($slot['room']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Class Schedule Summary -->
            <div class="row">
                <?php foreach ($days as $dayNum => $dayName): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class='bx bx-time-five me-1'></i>
                                <?php echo $dayName; ?>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php
                                    if (isset($timetable[$dayNum])) {
                                        foreach ($timetable[$dayNum] as $time => $slot) {
                                            echo "<li class='list-group-item'>";
                                            echo "<strong>" . date('h:i A', strtotime($time)) . "</strong><br>";
                                            echo htmlspecialchars($slot['subject_name']) . "<br>";
                                            echo "Class " . htmlspecialchars($slot['class_level'] . '-' . $slot['section']);
                                            echo "</li>";
                                        }
                                    } else {
                                        echo "<li class='list-group-item'>No classes scheduled</li>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sidebar.js"></script>
</body>
</html>
