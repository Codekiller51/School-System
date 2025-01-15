<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only admin can access registration
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $role = $_POST['role'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $dateOfBirth = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';

        // Role-specific fields
        $employeeId = $_POST['employee_id'] ?? '';
        $department = $_POST['department'] ?? '';
        $qualification = $_POST['qualification'] ?? '';
        $joiningDate = $_POST['joining_date'] ?? '';
        $admissionNumber = $_POST['admission_number'] ?? '';
        $classLevel = $_POST['class_level'] ?? '';
        $section = $_POST['section'] ?? '';
        $admissionDate = $_POST['admission_date'] ?? '';

        // Basic validation
        if (empty($role) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            throw new Exception('All fields are required');
        }

        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }

        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        // Determine table and required fields based on role
        $table = match ($role) {
            'student' => 'students',
            'teacher' => 'teachers',
            'parent' => 'parents',
            'admin' => 'administrators',
            default => throw new Exception('Invalid role selected')
        };

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user based on role
        switch ($role) {
            case 'teacher':
                if (empty($employeeId) || empty($dateOfBirth) || empty($gender) || empty($department) || 
                    empty($qualification) || empty($joiningDate) || empty($phone) || empty($address)) {
                    throw new Exception('All teacher fields are required');
                }
                $stmt = $conn->prepare("
                    INSERT INTO teachers (
                        email, password, first_name, last_name, employee_id,
                        date_of_birth, gender, department, qualification,
                        joining_date, phone, address, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active'
                    )
                ");
                $stmt->execute([
                    $email, $hashedPassword, $firstName, $lastName, $employeeId,
                    $dateOfBirth, $gender, $department, $qualification,
                    $joiningDate, $phone, $address
                ]);
                break;

            case 'student':
                if (empty($admissionNumber) || empty($dateOfBirth) || empty($gender) || 
                    empty($classLevel) || empty($section) || empty($admissionDate) || empty($address)) {
                    throw new Exception('All student fields are required');
                }
                $stmt = $conn->prepare("
                    INSERT INTO students (
                        email, password, admission_number, first_name, last_name,
                        date_of_birth, gender, class_level, section,
                        admission_date, address, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active'
                    )
                ");
                $stmt->execute([
                    $email, $hashedPassword, $admissionNumber, $firstName, $lastName,
                    $dateOfBirth, $gender, $classLevel, $section,
                    $admissionDate, $address
                ]);
                break;

            case 'parent':
                if (empty($phone) || empty($address)) {
                    throw new Exception('All parent fields are required');
                }
                $stmt = $conn->prepare("
                    INSERT INTO parents (
                        email, password, first_name, last_name,
                        phone, address, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, 'active'
                    )
                ");
                $stmt->execute([
                    $email, $hashedPassword, $firstName, $lastName,
                    $phone, $address
                ]);
                break;

            case 'admin':
                if (empty($phone) || empty($employeeId)) {
                    throw new Exception('All administrator fields are required');
                }
                $stmt = $conn->prepare("
                    INSERT INTO administrators (
                        email, password, first_name, last_name,
                        employee_id, phone, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, 'active'
                    )
                ");
                $stmt->execute([
                    $email, $hashedPassword, $firstName, $lastName,
                    $employeeId, $phone
                ]);
                break;
        }

        $success = ucfirst($role) . ' account created successfully';

        // Log activity
        $userId = $conn->lastInsertId();
        $stmt = $conn->prepare("
            INSERT INTO notification_logs (
                student_id, notification_type, notification_method,
                status, message
            ) VALUES (?, 'account_created', 'system', 'sent', ?)
        ");
        $message = "New $role account created from IP: " . $_SERVER['REMOTE_ADDR'];
        $stmt->execute([$userId, $message]);

    } catch(Exception $e) {
        $error = $e->getMessage();
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
                                <div class="role-option" data-role="student">
                                    <i class='bx bx-user-circle'></i>
                                    <div>Student</div>
                                </div>
                                <div class="role-option" data-role="teacher">
                                    <i class='bx bx-chalkboard'></i>
                                    <div>Teacher</div>
                                </div>
                                <div class="role-option" data-role="parent">
                                    <i class='bx bx-group'></i>
                                    <div>Parent</div>
                                </div>
                                <div class="role-option" data-role="admin">
                                    <i class='bx bx-shield'></i>
                                    <div>Admin</div>
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
                                    <label class="form-label">Employee ID</label>
                                    <input type="text" name="employee_id" class="form-control">
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
                                    <label class="form-label">Department</label>
                                    <input type="text" name="department" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Qualification</label>
                                    <textarea name="qualification" class="form-control"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Joining Date</label>
                                    <input type="date" name="joining_date" class="form-control">
                                </div>
                            </div>

                            <div id="studentFields" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label">Admission Number</label>
                                    <input type="text" name="admission_number" class="form-control">
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
                                    <label class="form-label">Class Level</label>
                                    <input type="text" name="class_level" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Section</label>
                                    <input type="text" name="section" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Admission Date</label>
                                    <input type="date" name="admission_date" class="form-control">
                                </div>
                            </div>

                            <div id="commonFields" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control"></textarea>
                                </div>
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
                            <h6><i class='bx bx-user-circle me-2'></i>Student</h6>
                            <p class="small text-muted">
                                Access assignments, view grades, track attendance, and manage study materials.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6><i class='bx bx-chalkboard me-2'></i>Teacher</h6>
                            <p class="small text-muted">
                                Manage classes, record attendance, grade assignments, and communicate with students.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6><i class='bx bx-group me-2'></i>Parent</h6>
                            <p class="small text-muted">
                                Monitor child's progress, view attendance, and communicate with teachers.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6><i class='bx bx-shield me-2'></i>Administrator</h6>
                            <p class="small text-muted">
                                Manage users, oversee system operations, and maintain school records.
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
                    $('#commonFields').addClass('d-none');
                } else if (role === 'student') {
                    $('#teacherFields').addClass('d-none');
                    $('#studentFields').removeClass('d-none');
                    $('#commonFields').addClass('d-none');
                } else {
                    $('#teacherFields').addClass('d-none');
                    $('#studentFields').addClass('d-none');
                    $('#commonFields').removeClass('d-none');
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
