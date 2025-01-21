<?php
session_start();
require_once '../config/database.php';

// Only administrator can access registration
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic information
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        
        // Validate basic fields
        if (!$email) {
            throw new Exception('Invalid email format');
        }
        if (empty($role) || empty($password) || empty($firstName) || empty($lastName)) {
            throw new Exception('All required fields must be filled');
        }
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Determine table based on role
        switch ($role) {
            case 'administrator':
                $table = 'administrators';
                
                // Check if email exists
                $stmt = $conn->prepare("SELECT id FROM administrators WHERE email = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('Email already exists');
                }
                
                // Insert administrator
                $stmt = $conn->prepare("
                    INSERT INTO administrators (
                        first_name, last_name, email, password, phone
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('sssss', $firstName, $lastName, $email, $hashedPassword, $phone);
                break;

            case 'teacher':
                // Additional teacher fields
                $dateOfBirth = $_POST['date_of_birth'] ?? '';
                $gender = $_POST['gender'] ?? '';
                $address = $_POST['address'] ?? '';
                $subjectSpecialization = $_POST['subject_specialization'] ?? '';
                $qualification = $_POST['qualification'] ?? '';
                $experienceYears = filter_input(INPUT_POST, 'experience_years', FILTER_VALIDATE_INT);
                $joiningDate = $_POST['joining_date'] ?? '';
                
                // Validate teacher-specific fields
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
                $stmt = $conn->prepare("
                    INSERT INTO teachers (
                        first_name, last_name, email, password, phone, address,
                        date_of_birth, gender, subject_specialization, qualification,
                        experience_years, joining_date, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active'
                    )
                ");
                $stmt->bind_param(
                    'ssssssssssss',
                    $firstName, $lastName, $email, $hashedPassword, $phone, $address,
                    $dateOfBirth, $gender, $subjectSpecialization, $qualification,
                    $experienceYears, $joiningDate
                );
                break;

            case 'student':
                // Additional student fields
                $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
                $rollNumber = filter_input(INPUT_POST, 'roll_number', FILTER_SANITIZE_STRING);
                $admissionNumber = filter_input(INPUT_POST, 'admission_number', FILTER_SANITIZE_STRING);
                $admissionDate = $_POST['admission_date'] ?? '';
                $gender = $_POST['gender'] ?? '';
                $dateOfBirth = $_POST['date_of_birth'] ?? '';
                $address = $_POST['address'] ?? '';
                $bloodGroup = $_POST['blood_group'] ?? '';
                
                // Validate student-specific fields
                if (empty($classId) || empty($rollNumber) || empty($admissionNumber) || 
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
                $stmt = $conn->prepare("
                    INSERT INTO students (
                        first_name, last_name, email, password, class_id,
                        roll_number, admission_number, admission_date, gender,
                        date_of_birth, phone, address, blood_group, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active'
                    )
                ");
                $stmt->bind_param(
                    'ssssssssssssss',
                    $firstName, $lastName, $email, $hashedPassword, $classId,
                    $rollNumber, $admissionNumber, $admissionDate, $gender,
                    $dateOfBirth, $phone, $address, $bloodGroup
                );
                break;

            default:
                throw new Exception('Invalid role selected');
        }
        
        // Execute the prepared statement
        if (!$stmt->execute()) {
            throw new Exception("Error creating account: " . $stmt->error);
        }
        
        $success = ucfirst($role) . " account created successfully!";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register New User - School Management System</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
        }
        .register-header {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .register-form {
            padding: 40px;
        }
        .role-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .role-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .role-option:hover {
            border-color: #6B73FF;
            background: #f8f9fa;
        }
        .role-option.active {
            border-color: #000DFF;
            background: #e7f1ff;
        }
        .role-option i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #6c757d;
        }
        .role-option.active i {
            color: #000DFF;
        }
        .form-control {
            padding: 12px;
            border-radius: 10px;
        }
        .btn-register {
            padding: 12px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #000DFF 0%, #6B73FF 100%);
        }
        .password-requirements {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .requirement {
            margin-bottom: 5px;
        }
        .requirement i {
            margin-right: 5px;
        }
        .requirement.valid {
            color: #198754;
        }
        .requirement.invalid {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="row g-0">
                <div class="col-lg-8">
                    <div class="register-header">
                        <h3 class="mb-3">Register New User</h3>
                        <p class="mb-0">Create a new account for students, teachers, parents, or administrators</p>
                    </div>
                    <div class="register-form">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class='bx bx-error-circle me-2'></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class='bx bx-check-circle me-2'></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="" id="registerForm">
                            <div class="role-selector">
                                <div class="role-option" data-role="administrator">
                                    <i class='bx bx-shield'></i>
                                    <div>Administrator</div>
                                </div>
                                <div class="role-option" data-role="teacher">
                                    <i class='bx bx-chalkboard'></i>
                                    <div>Teacher</div>
                                </div>
                                <div class="role-option" data-role="student">
                                    <i class='bx bx-user-circle'></i>
                                    <div>Student</div>
                                </div>
                            </div>
                            <input type="hidden" name="role" id="selectedRole">

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class='bx bx-user'></i>
                                        </span>
                                        <input type="text" class="form-control" name="first_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class='bx bx-user'></i>
                                        </span>
                                        <input type="text" class="form-control" name="last_name" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class='bx bx-envelope'></i>
                                    </span>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class='bx bx-lock-alt'></i>
                                        </span>
                                        <input type="password" class="form-control" name="password" 
                                               id="password" required>
                                        <button class="btn btn-outline-secondary toggle-password" 
                                                type="button">
                                            <i class='bx bx-show'></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class='bx bx-lock-alt'></i>
                                        </span>
                                        <input type="password" class="form-control" 
                                               name="confirm_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" 
                                                type="button">
                                            <i class='bx bx-show'></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="password-requirements">
                                    <div class="requirement" id="length">
                                        <i class='bx bx-x'></i> At least 8 characters
                                    </div>
                                    <div class="requirement" id="uppercase">
                                        <i class='bx bx-x'></i> At least one uppercase letter
                                    </div>
                                    <div class="requirement" id="lowercase">
                                        <i class='bx bx-x'></i> At least one lowercase letter
                                    </div>
                                    <div class="requirement" id="number">
                                        <i class='bx bx-x'></i> At least one number
                                    </div>
                                    <div class="requirement" id="special">
                                        <i class='bx bx-x'></i> At least one special character
                                    </div>
                                </div>
                            </div>

                            <div id="teacherFields" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Subject Specialization</label>
                                    <input type="text" name="subject_specialization" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Qualification</label>
                                    <textarea name="qualification" class="form-control"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Experience Years</label>
                                    <input type="number" name="experience_years" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Joining Date</label>
                                    <input type="date" name="joining_date" class="form-control">
                                </div>
                            </div>

                            <div id="studentFields" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label">Class ID</label>
                                    <input type="number" name="class_id" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Roll Number</label>
                                    <input type="text" name="roll_number" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Admission Number</label>
                                    <input type="text" name="admission_number" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Admission Date</label>
                                    <input type="date" name="admission_date" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Blood Group</label>
                                    <input type="text" name="blood_group" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-register text-white">
                                    <i class='bx bx-user-plus me-2'></i>Create Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-4 bg-light">
                    <div class="p-4 h-100">
                        <h5 class="mb-4">Account Types</h5>
                        
                        <div class="mb-4">
                            <h6><i class='bx bx-shield me-2'></i>Administrator</h6>
                            <p class="small text-muted">
                                Manage users, oversee system operations, and maintain school records.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6><i class='bx bx-chalkboard me-2'></i>Teacher</h6>
                            <p class="small text-muted">
                                Manage classes, record attendance, grade assignments, and communicate with students.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6><i class='bx bx-user-circle me-2'></i>Student</h6>
                            <p class="small text-muted">
                                Access assignments, view grades, track attendance, and manage study materials.
                            </p>
                        </div>

                        <div class="alert alert-info" role="alert">
                            <i class='bx bx-info-circle me-2'></i>
                            All accounts require approval from the system administrator.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Role selection
            $('.role-option').click(function() {
                $('.role-option').removeClass('active');
                $(this).addClass('active');
                $('#selectedRole').val($(this).data('role'));

                // Show/hide role-specific fields
                const role = $(this).data('role');
                if (role === 'teacher') {
                    $('#teacherFields').removeClass('d-none');
                    $('#studentFields').addClass('d-none');
                } else if (role === 'student') {
                    $('#teacherFields').addClass('d-none');
                    $('#studentFields').removeClass('d-none');
                } else {
                    $('#teacherFields').addClass('d-none');
                    $('#studentFields').addClass('d-none');
                }
            });

            // Password visibility toggle
            $('.toggle-password').click(function() {
                const input = $(this).closest('.input-group').find('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).find('i').toggleClass('bx-show bx-hide');
            });

            // Password validation
            $('#password').on('input', function() {
                const password = $(this).val();
                
                // Length check
                if (password.length >= 8) {
                    $('#length').addClass('valid').removeClass('invalid')
                        .find('i').addClass('bx-check').removeClass('bx-x');
                } else {
                    $('#length').addClass('invalid').removeClass('valid')
                        .find('i').addClass('bx-x').removeClass('bx-check');
                }

                // Uppercase check
                if (/[A-Z]/.test(password)) {
                    $('#uppercase').addClass('valid').removeClass('invalid')
                        .find('i').addClass('bx-check').removeClass('bx-x');
                } else {
                    $('#uppercase').addClass('invalid').removeClass('valid')
                        .find('i').addClass('bx-x').removeClass('bx-check');
                }

                // Lowercase check
                if (/[a-z]/.test(password)) {
                    $('#lowercase').addClass('valid').removeClass('invalid')
                        .find('i').addClass('bx-check').removeClass('bx-x');
                } else {
                    $('#lowercase').addClass('invalid').removeClass('valid')
                        .find('i').addClass('bx-x').removeClass('bx-check');
                }

                // Number check
                if (/\d/.test(password)) {
                    $('#number').addClass('valid').removeClass('invalid')
                        .find('i').addClass('bx-check').removeClass('bx-x');
                } else {
                    $('#number').addClass('invalid').removeClass('valid')
                        .find('i').addClass('bx-x').removeClass('bx-check');
                }

                // Special character check
                if (/[!@#$%^&*]/.test(password)) {
                    $('#special').addClass('valid').removeClass('invalid')
                        .find('i').addClass('bx-check').removeClass('bx-x');
                } else {
                    $('#special').addClass('invalid').removeClass('valid')
                        .find('i').addClass('bx-x').removeClass('bx-check');
                }
            });

            // Form validation
            $('#registerForm').submit(function(e) {
                if (!$('#selectedRole').val()) {
                    e.preventDefault();
                    alert('Please select a role');
                }
            });
        });
    </script>
</body>
</html>
