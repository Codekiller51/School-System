<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireTeacher();

$teacherId = $_SESSION['user_id'];
$examId = $_GET['exam'] ?? '';
$subjectId = $_GET['subject'] ?? '';
$classLevel = $_GET['class'] ?? '';
$section = $_GET['section'] ?? '';

try {
    // Get available exams
    $stmt = $conn->prepare("
        SELECT DISTINCT e.* 
        FROM exams e
        JOIN teacher_subjects ts ON FIND_IN_SET(CONCAT(ts.class_level, '_', ts.section), REPLACE(e.classes, ' ', ''))
        WHERE ts.teacher_id = ? AND e.status = 'active'
        ORDER BY e.start_date DESC
    ");
    $stmt->execute([$teacherId]);
    $exams = $stmt->fetchAll();

    // Get teacher's subjects
    $stmt = $conn->prepare("
        SELECT DISTINCT s.*, ts.class_level, ts.section
        FROM subjects s
        JOIN teacher_subjects ts ON ts.subject_id = s.id
        WHERE ts.teacher_id = ?
        ORDER BY s.name
    ");
    $stmt->execute([$teacherId]);
    $subjects = $stmt->fetchAll();

    // Get students and their marks if exam and subject are selected
    $students = [];
    if ($examId && $subjectId && $classLevel && $section) {
        $stmt = $conn->prepare("
            SELECT s.*, 
                   er.marks,
                   er.remarks,
                   er.id as result_id
            FROM students s
            LEFT JOIN exam_results er ON er.student_id = s.id 
                AND er.exam_id = ? 
                AND er.subject_id = ?
            WHERE s.class_level = ? AND s.section = ?
            ORDER BY s.first_name, s.last_name
        ");
        $stmt->execute([$examId, $subjectId, $classLevel, $section]);
        $students = $stmt->fetchAll();

        // Get exam details
        $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$examId]);
        $examDetails = $stmt->fetch();

        // Get subject details
        $stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        $subjectDetails = $stmt->fetch();
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Marks - School Management System</title>
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
            <span class="text">Record Marks</span>
        </div>

        <div class="container-fluid px-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class='bx bx-edit me-1'></i>
                    Record Exam Marks
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="get" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="exam" class="form-label">Exam</label>
                            <select name="exam" id="exam" class="form-select" required>
                                <option value="">Select Exam</option>
                                <?php foreach ($exams as $exam): ?>
                                    <option value="<?php echo $exam['id']; ?>"
                                            <?php echo $examId == $exam['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($exam['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select name="subject" id="subject" class="form-select" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"
                                            data-class="<?php echo $subject['class_level']; ?>"
                                            data-section="<?php echo $subject['section']; ?>"
                                            <?php echo $subjectId == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="class" class="form-label">Class</label>
                            <select name="class" id="class" class="form-select" required>
                                <option value="">Select Class</option>
                                <?php 
                                $classes = array_unique(array_column($subjects, 'class_level'));
                                foreach ($classes as $class): 
                                ?>
                                    <option value="<?php echo $class; ?>"
                                            <?php echo $classLevel == $class ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="section" class="form-label">Section</label>
                            <select name="section" id="section" class="form-select" required>
                                <option value="">Select Section</option>
                                <?php 
                                if ($classLevel) {
                                    $sections = array_unique(array_column(array_filter($subjects, function($s) use ($classLevel) {
                                        return $s['class_level'] === $classLevel;
                                    }), 'section'));
                                    foreach ($sections as $sec): 
                                ?>
                                    <option value="<?php echo $sec; ?>"
                                            <?php echo $section == $sec ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sec); ?>
                                    </option>
                                <?php 
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-filter-alt'></i> Filter
                            </button>
                        </div>
                    </form>

                    <?php if ($examId && $subjectId && $classLevel && $section && !empty($students)): ?>
                        <!-- Exam Information -->
                        <div class="alert alert-info">
                            <h5 class="alert-heading">Exam Details</h5>
                            <p class="mb-0">
                                Exam: <?php echo htmlspecialchars($examDetails['name']); ?><br>
                                Subject: <?php echo htmlspecialchars($subjectDetails['name']); ?><br>
                                Maximum Marks: <?php echo $examDetails['max_marks']; ?><br>
                                Pass Marks: <?php echo $examDetails['pass_marks']; ?>
                            </p>
                        </div>

                        <!-- Marks Form -->
                        <form id="marksForm" method="post" action="../../../api/exams/save-marks.php">
                            <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                            <input type="hidden" name="subject_id" value="<?php echo $subjectId; ?>">
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Admission No.</th>
                                            <th>Marks</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                    <?php if ($student['result_id']): ?>
                                                        <input type="hidden" name="result_ids[<?php echo $student['id']; ?>]" 
                                                               value="<?php echo $student['result_id']; ?>">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                                <td>
                                                    <input type="number" class="form-control marks-input"
                                                           name="marks[<?php echo $student['id']; ?>]"
                                                           value="<?php echo $student['marks']; ?>"
                                                           min="0" max="<?php echo $examDetails['max_marks']; ?>"
                                                           step="0.5" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                           name="remarks[<?php echo $student['id']; ?>]"
                                                           value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>"
                                                           placeholder="Optional remarks">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-save'></i> Save Marks
                                </button>
                            </div>
                        </form>
                    <?php elseif ($examId && $subjectId && $classLevel && $section): ?>
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

            // Handle subject change
            $('#subject').change(function() {
                const option = $(this).find('option:selected');
                const classLevel = option.data('class');
                const section = option.data('section');
                
                $('#class').val(classLevel).trigger('change');
                setTimeout(() => {
                    $('#section').val(section).trigger('change');
                }, 100);
            });

            // Handle class change
            $('#class').change(function() {
                const classLevel = $(this).val();
                const sectionSelect = $('#section');
                sectionSelect.empty().append('<option value="">Select Section</option>');

                if (classLevel) {
                    const sections = new Set();
                    $('#subject option').each(function() {
                        if ($(this).data('class') === classLevel) {
                            sections.add($(this).data('section'));
                        }
                    });
                    sections.forEach(section => {
                        sectionSelect.append(new Option(section, section));
                    });
                }
            });

            // Handle form submission
            $('#marksForm').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            alert('Marks saved successfully!');
                            location.reload();
                        } else {
                            alert('Error saving marks: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error saving marks. Please try again.');
                    }
                });
            });

            // Validate marks input
            $('.marks-input').on('input', function() {
                const maxMarks = <?php echo $examDetails['max_marks'] ?? 100; ?>;
                if (parseFloat($(this).val()) > maxMarks) {
                    $(this).val(maxMarks);
                }
            });
        });
    </script>
</body>
</html>
