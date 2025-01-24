<?php
require_once '../config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userType = $_POST['user_type'] ?? '';
    $response = ['success' => false, 'message' => ''];

    try {
        // Common fields validation
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

        if (!$email || empty($password) || empty($firstName) || empty($lastName)) {
            throw new Exception('Please fill all required fields');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        switch ($userType) {
            case 'administrator':
                // Check if email exists
                $stmt = $conn->prepare("SELECT id FROM administrators WHERE email = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('Email already exists');
                }

                // Insert administrator
                $stmt = $conn->prepare("INSERT INTO administrators (email, password, first_name, last_name, phone) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssss', $email, $hashedPassword, $firstName, $lastName, $phone);
                break;

            case 'teacher':
                // Validate teacher-specific fields
                $dateOfBirth = $_POST['date_of_birth'] ?? '';
                $gender = $_POST['gender'] ?? '';
                $qualification = $_POST['qualification'] ?? '';
                $joiningDate = $_POST['joining_date'] ?? '';
                $address = $_POST['address'] ?? '';
                $subjectSpecialization = $_POST['subject_specialization'] ?? '';
                $experienceYears = filter_input(INPUT_POST, 'experience_years', FILTER_VALIDATE_INT);

                if (empty($dateOfBirth) || empty($gender) || empty($qualification) || 
                    empty($joiningDate) || empty($subjectSpecialization)) {
                    throw new Exception('All teacher fields are required');
                }

                // Check if email exists
                $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('Email already exists');
                }

                // Insert teacher
                $stmt = $conn->prepare("INSERT INTO teachers (email, password, first_name, last_name, phone, 
                                      date_of_birth, gender, qualification, joining_date, address, 
                                      subject_specialization, experience_years, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                $stmt->bind_param('sssssssssssi', 
                    $email, $hashedPassword, $firstName, $lastName, $phone,
                    $dateOfBirth, $gender, $qualification, $joiningDate, $address,
                    $subjectSpecialization, $experienceYears
                );
                break;

            case 'student':
                // Validate student-specific fields
                $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
                $rollNumber = filter_input(INPUT_POST, 'roll_number', FILTER_SANITIZE_STRING);
                $admissionNumber = filter_input(INPUT_POST, 'admission_number', FILTER_SANITIZE_STRING);
                $admissionDate = $_POST['admission_date'] ?? '';
                $gender = $_POST['gender'] ?? '';
                $dateOfBirth = $_POST['date_of_birth'] ?? '';
                $address = $_POST['address'] ?? '';
                $bloodGroup = $_POST['blood_group'] ?? '';

                if (!$classId || empty($rollNumber) || empty($admissionNumber) || 
                    empty($admissionDate) || empty($gender) || empty($dateOfBirth)) {
                    throw new Exception('All student fields are required');
                }

                // Check if admission number exists
                $stmt = $conn->prepare("SELECT id FROM students WHERE admission_number = ?");
                $stmt->bind_param('s', $admissionNumber);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('Admission number already exists');
                }

                // Insert student
                $stmt = $conn->prepare("INSERT INTO students (email, password, first_name, last_name, phone,
                                      class_id, roll_number, admission_number, admission_date, gender,
                                      date_of_birth, address, blood_group, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                $stmt->bind_param('sssssssssssss',
                    $email, $hashedPassword, $firstName, $lastName, $phone,
                    $classId, $rollNumber, $admissionNumber, $admissionDate, $gender,
                    $dateOfBirth, $address, $bloodGroup
                );
                break;

            default:
                throw new Exception('Invalid user type');
        }

        if (!$stmt->execute()) {
            throw new Exception("Error creating account: " . $stmt->error);
        }

        $response['success'] = true;
        $response['message'] = ucfirst($userType) . ' account created successfully!';

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }

    // Return JSON response for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Fetch classes for student form
$classes = [];
try {
    $stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE status = 'Active' ORDER BY name, section");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
} catch (Exception $e) {
    // Handle error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Users - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Add Users (Temporary Page)</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($response)): ?>
                            <div class="alert alert-<?php echo $response['success'] ? 'success' : 'danger'; ?>">
                                <?php echo htmlspecialchars($response['message']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary active" data-form="administratorForm">Administrator</button>
                                <button type="button" class="btn btn-outline-primary" data-form="teacherForm">Teacher</button>
                                <button type="button" class="btn btn-outline-primary" data-form="studentForm">Student</button>
                            </div>
                        </div>

                        <!-- Administrator Form -->
                        <form id="administratorForm" class="form-section" method="POST">
                            <input type="hidden" name="user_type" value="administrator">
                            
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Create Administrator</button>
                            </div>
                        </form>

                        <!-- Teacher Form -->
                        <form id="teacherForm" class="form-section d-none" method="POST">
                            <input type="hidden" name="user_type" value="teacher">
                            
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" name="date_of_birth" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject Specialization *</label>
                                <input type="text" name="subject_specialization" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Qualification *</label>
                                <input type="text" name="qualification" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Experience Years</label>
                                <input type="number" name="experience_years" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Joining Date *</label>
                                <input type="date" name="joining_date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control"></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Create Teacher</button>
                            </div>
                        </form>

                        <!-- Student Form -->
                        <form id="studentForm" class="form-section d-none" method="POST">
                            <input type="hidden" name="user_type" value="student">
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Class *</label>
                                <select name="class_id" class="form-control" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo htmlspecialchars($class['id']); ?>">
                                            <?php echo htmlspecialchars($class['name'] . ' - ' . $class['section']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Roll Number *</label>
                                <input type="text" name="roll_number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admission Number *</label>
                                <input type="text" name="admission_number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admission Date *</label>
                                <input type="date" name="admission_date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" name="date_of_birth" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Blood Group</label>
                                <input type="text" name="blood_group" class="form-control">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Create Student</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Form switching
            $('.btn-group button').click(function() {
                $('.btn-group button').removeClass('active');
                $(this).addClass('active');
                
                $('.form-section').addClass('d-none');
                $('#' + $(this).data('form')).removeClass('d-none');
            });

            // Form submission
            $('.form-section').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        const alertClass = response.success ? 'success' : 'danger';
                        const alert = `<div class="alert alert-${alertClass}">${response.message}</div>`;
                        $('.card-body > .alert').remove();
                        $('.card-body').prepend(alert);
                        
                        if (response.success) {
                            $('.form-section')[0].reset();
                        }
                    },
                    error: function() {
                        const alert = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
                        $('.card-body > .alert').remove();
                        $('.card-body').prepend(alert);
                    }
                });
            });
        });
    </script>
</body>
</html>
