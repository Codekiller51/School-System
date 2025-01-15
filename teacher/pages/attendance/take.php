<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireTeacher();

$teacherId = $_SESSION['user_id'];
$date = $_GET['date'] ?? date('Y-m-d');
$classLevel = $_GET['class'] ?? '';
$section = $_GET['section'] ?? '';

try {
    // Get teacher's assigned classes
    $stmt = $conn->prepare("
        SELECT DISTINCT c.level, c.section, c.name
        FROM classes c
        JOIN teacher_subjects ts ON ts.class_level = c.level AND ts.section = c.section
        WHERE ts.teacher_id = ?
        ORDER BY c.level, c.section
    ");
    $stmt->execute([$teacherId]);
    $assignedClasses = $stmt->fetchAll();

    // If class is selected, get students
    $students = [];
    if ($classLevel && $section) {
        $stmt = $conn->prepare("
            SELECT s.*, 
                   a.status, 
                   a.reason,
                   a.id as attendance_id
            FROM students s
            LEFT JOIN attendance a ON a.student_id = s.id 
                AND a.date = ? 
                AND a.class_level = ? 
                AND a.section = ?
            WHERE s.class_level = ? AND s.section = ?
            ORDER BY s.first_name, s.last_name
        ");
        $stmt->execute([$date, $classLevel, $section, $classLevel, $section]);
        $students = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="sidebar close">
        <?php include '../../includes/sidebar.php'; ?>
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Take Attendance</span>
        </div>

        <div class="container-fluid px-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class='bx bx-calendar-check me-1'></i>
                    Attendance Form
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="get" class="row g-3 mb-4">
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo $date; ?>" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-filter-alt'></i> Filter
                            </button>
                        </div>
                    </form>

                    <?php if ($classLevel && $section && !empty($students)): ?>
                        <!-- Attendance Form -->
                        <form id="attendanceForm" method="post" action="../../../api/attendance/save.php">
                            <input type="hidden" name="date" value="<?php echo $date; ?>">
                            <input type="hidden" name="class_level" value="<?php echo $classLevel; ?>">
                            <input type="hidden" name="section" value="<?php echo $section; ?>">
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Admission No.</th>
                                            <th>Status</th>
                                            <th>Reason (if absent)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                    <?php if ($student['attendance_id']): ?>
                                                        <input type="hidden" name="attendance_ids[<?php echo $student['id']; ?>]" 
                                                               value="<?php echo $student['attendance_id']; ?>">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                                <td>
                                                    <select name="status[<?php echo $student['id']; ?>]" 
                                                            class="form-select attendance-status">
                                                        <option value="present" <?php echo $student['status'] === 'present' ? 'selected' : ''; ?>>
                                                            Present
                                                        </option>
                                                        <option value="absent" <?php echo $student['status'] === 'absent' ? 'selected' : ''; ?>>
                                                            Absent
                                                        </option>
                                                        <option value="late" <?php echo $student['status'] === 'late' ? 'selected' : ''; ?>>
                                                            Late
                                                        </option>
                                                        <option value="excused" <?php echo $student['status'] === 'excused' ? 'selected' : ''; ?>>
                                                            Excused
                                                        </option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control reason-input"
                                                           name="reason[<?php echo $student['id']; ?>]"
                                                           value="<?php echo htmlspecialchars($student['reason'] ?? ''); ?>"
                                                           <?php echo $student['status'] === 'present' ? 'disabled' : ''; ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-save'></i> Save Attendance
                                </button>
                            </div>
                        </form>
                    <?php elseif ($classLevel && $section): ?>
                        <div class="alert alert-info">
                            No students found in this class.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.form-select').select2({
                theme: 'bootstrap-5'
            });

            // Handle class change
            $('#class').change(function() {
                const classLevel = $(this).val();
                const sectionSelect = $('#section');
                sectionSelect.empty().append('<option value="">Select Section</option>');

                if (classLevel) {
                    $.get('../../../api/classes/sections.php', { class: classLevel }, function(sections) {
                        sections.forEach(function(section) {
                            sectionSelect.append(new Option(section, section));
                        });
                    });
                }
            });

            // Handle attendance status change
            $('.attendance-status').change(function() {
                const reasonInput = $(this).closest('tr').find('.reason-input');
                reasonInput.prop('disabled', $(this).val() === 'present');
            });

            // Handle form submission
            $('#attendanceForm').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            alert('Attendance saved successfully!');
                            location.reload();
                        } else {
                            alert('Error saving attendance: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error saving attendance. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>
