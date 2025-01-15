<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Timetable - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .timetable-cell {
            min-width: 200px;
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        .timetable-cell.lesson {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .timetable-cell.lesson:hover {
            background-color: #e9ecef;
        }
        .time-slot {
            font-weight: bold;
            background-color: #e3e6f0;
        }
    </style>
</head>
<body>
    <div class="sidebar close">
        <!-- Sidebar content will be loaded via JavaScript -->
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Class Timetable</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <h5 class="mb-0">Class Timetable</h5>
                        <select id="classFilter" class="form-select" style="width: 200px;">
                            <option value="">Select Class</option>
                            <?php
                            $stmt = $conn->query("
                                SELECT DISTINCT name, section 
                                FROM classes 
                                WHERE academic_year = '" . getAcademicYear() . "'
                                ORDER BY name, section
                            ");
                            while ($class = $stmt->fetch()) {
                                echo "<option value='" . $class['name'] . "_" . $class['section'] . "'>" . 
                                     htmlspecialchars($class['name'] . ' ' . $class['section']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLessonModal">
                        <i class='bx bx-plus'></i> Add Lesson
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="timetableView" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="time-slot">Time / Day</th>
                                    <th>Monday</th>
                                    <th>Tuesday</th>
                                    <th>Wednesday</th>
                                    <th>Thursday</th>
                                    <th>Friday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $timeSlots = [
                                    '08:00-08:40' => '1st Lesson',
                                    '08:40-09:20' => '2nd Lesson',
                                    '09:20-10:00' => '3rd Lesson',
                                    '10:00-10:30' => 'Break',
                                    '10:30-11:10' => '4th Lesson',
                                    '11:10-11:50' => '5th Lesson',
                                    '11:50-12:30' => '6th Lesson',
                                    '12:30-13:10' => '7th Lesson',
                                    '13:10-14:00' => 'Lunch Break',
                                    '14:00-14:40' => '8th Lesson',
                                    '14:40-15:20' => '9th Lesson',
                                    '15:20-16:00' => '10th Lesson'
                                ];

                                foreach ($timeSlots as $time => $label) {
                                    echo "<tr>";
                                    echo "<td class='time-slot'>$time<br>$label</td>";
                                    for ($day = 1; $day <= 5; $day++) {
                                        echo "<td class='timetable-cell' data-time='$time' data-day='$day'></td>";
                                    }
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Lesson Modal -->
        <div class="modal fade" id="addLessonModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Lesson</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addLessonForm" action="../../../api/timetable/add.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class_name" required>
                                    <option value="">Select Class</option>
                                    <?php
                                    $stmt = $conn->query("
                                        SELECT DISTINCT name, section 
                                        FROM classes 
                                        WHERE academic_year = '" . getAcademicYear() . "'
                                        ORDER BY name, section
                                    ");
                                    while ($class = $stmt->fetch()) {
                                        echo "<option value='" . $class['name'] . "_" . $class['section'] . "'>" . 
                                             htmlspecialchars($class['name'] . ' ' . $class['section']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <select class="form-select select2" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php
                                    $stmt = $conn->query("SELECT id, subject_code, name FROM subjects ORDER BY name");
                                    while ($subject = $stmt->fetch()) {
                                        echo "<option value='" . $subject['id'] . "'>" . 
                                             htmlspecialchars($subject['name'] . ' (' . $subject['subject_code'] . ')') . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Teacher</label>
                                <select class="form-select select2" name="teacher_id" required>
                                    <option value="">Select Teacher</option>
                                    <?php
                                    $stmt = $conn->query("SELECT id, first_name, last_name FROM teachers ORDER BY first_name, last_name");
                                    while ($teacher = $stmt->fetch()) {
                                        echo "<option value='" . $teacher['id'] . "'>" . 
                                             htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Day</label>
                                <select class="form-select" name="day" required>
                                    <option value="">Select Day</option>
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time Slot</label>
                                <select class="form-select" name="time_slot" required>
                                    <option value="">Select Time Slot</option>
                                    <?php
                                    foreach ($timeSlots as $time => $label) {
                                        if (!strpos($label, 'Break')) {
                                            echo "<option value='$time'>$time ($label)</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Lesson</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Lesson Modal -->
        <div class="modal fade" id="editLessonModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Lesson</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editLessonForm" action="../../../api/timetable/update.php" method="POST">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <select class="form-select select2" name="subject_id" id="edit_subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php
                                    $stmt = $conn->query("SELECT id, subject_code, name FROM subjects ORDER BY name");
                                    while ($subject = $stmt->fetch()) {
                                        echo "<option value='" . $subject['id'] . "'>" . 
                                             htmlspecialchars($subject['name'] . ' (' . $subject['subject_code'] . ')') . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Teacher</label>
                                <select class="form-select select2" name="teacher_id" id="edit_teacher_id" required>
                                    <option value="">Select Teacher</option>
                                    <?php
                                    $stmt = $conn->query("SELECT id, first_name, last_name FROM teachers ORDER BY first_name, last_name");
                                    while ($teacher = $stmt->fetch()) {
                                        echo "<option value='" . $teacher['id'] . "'>" . 
                                             htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update Lesson</button>
                                <button type="button" class="btn btn-danger" id="deleteLessonBtn">Delete Lesson</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script src="../../js/timetable.js"></script>
</body>
</html>
