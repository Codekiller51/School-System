<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Database connection
try {
    require_once '../config/database.php';
    
    // Verify database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not established"));
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    try {
        switch ($role) {
            case 'administrator':
                header('Location: ../admin/index.php');
                break;
            case 'teacher':
                header('Location: ../teacher/index.php');
                break;
            case 'student':
                header('Location: ../student/index.php');
                break;
            default:
                // Invalid role in session, clear session
                session_unset();
                session_destroy();
                throw new Exception("Invalid role in session");
        }
        exit();
    } catch (Exception $e) {
        $error = "Session error: " . $e->getMessage();
    }
}

$error = '';
$success = '';

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        
        if (!$email) {
            throw new Exception('Invalid email format');
        }
        if (empty($password)) {
            throw new Exception('Password is required');
        }
        if (empty($role)) {
            throw new Exception('Role is required');
        }

        // Select appropriate table and query based on role
        switch ($role) {
            case 'administrator':
                $table = 'administrators';
                $query = "SELECT id, password, email FROM $table WHERE email = ?";
                break;
            case 'teacher':
                $table = 'teachers';
                $query = "SELECT id, password, email, status FROM $table WHERE email = ? AND status = 'Active'";
                break;
            case 'student':
                $table = 'students';
                $query = "SELECT id, password, email, status FROM $table WHERE email = ? AND status = 'Active'";
                break;
            default:
                throw new Exception('Invalid role selected');
        }
        
        // Check credentials
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Failed to get result: " . $stmt->error);
        }
        
        if ($result->num_rows === 0) {
            throw new Exception("Invalid email or password");
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password");
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $role;
        $_SESSION['email'] = $user['email'];
        
        // Clear any existing error messages
        unset($_SESSION['error']);
        
        // Redirect based on role
        switch ($role) {
            case 'administrator':
                header('Location: ../admin/index.php');
                break;
            case 'teacher':
                header('Location: ../teacher/index.php');
                break;
            case 'student':
                header('Location: ../student/index.php');
                break;
        }
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Log error for debugging
        error_log("Login error: " . $error);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - School Management System</title>
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
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
        }
        .login-header {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .login-form {
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
        .btn-login {
            padding: 12px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            width: 100%;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #000DFF 0%, #6B73FF 100%);
        }
        .school-info {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
        }
        .alert {
            border-radius: 10px;
        }
        
    </style>
</head>
<body>
    <div class="container" style="max-width: 70%;">
        <div class="login-container">
            <div class="row g-0">
                <div class="col-lg-6">
                    <div class="login-header">
                        <h3 class="mb-3">Welcome Back!</h3>
                        <p class="mb-0">Please login to access your dashboard</p>
                    </div>
                    <div class="login-form">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class='bx bx-error-circle me-2'></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="" id="loginForm">
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
                                <div class="role-option" data-role="administrator">
                                    <i class='bx bx-shield'></i>
                                    <div>Administrator</div>
                                </div>
                            </div>
                            <input type="hidden" name="role" id="selectedRole">

                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class='bx bx-envelope'></i>
                                    </span>
                                    <input type="email" class="form-control" name="email" 
                                           placeholder="Email address" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class='bx bx-lock-alt'></i>
                                    </span>
                                    <input type="password" class="form-control" name="password" 
                                           placeholder="Password" required>
                                    <button class="btn btn-outline-secondary toggle-password" 
                                            type="button">
                                        <i class='bx bx-show'></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login text-white mb-4">
                                <i class='bx bx-log-in-circle me-2'></i>Login
                            </button>

                            <div class="text-center">
                                <a href="forgot-password.php" class="text-decoration-none">
                                    Forgot password?
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6" class="onmobile">
                    <div class="school-info h-100 d-flex flex-column justify-content-center p-5">
                        <img src="../asset/logo/1.webp" alt="School Logo" 
                             class="img-fluid mb-4" style="max-width: 200px; margin: 0 auto;">
                        <h4 class="mb-4">School Management System</h4>
                        <p class="mb-4">
                            Access your personalized dashboard to manage assignments, 
                            track attendance, view grades, and much more.
                        </p>
                        <div class="row g-4">
                            <div class="col-6">
                                <div class="p-3 rounded bg-white">
                                    <i class='bx bx-book-reader text-primary mb-2' style="font-size: 24px;"></i>
                                    <h6>Easy Learning</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded bg-white">
                                    <i class='bx bx-line-chart text-primary mb-2' style="font-size: 24px;"></i>
                                    <h6>Track Progress</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded bg-white">
                                    <i class='bx bx-calendar-check text-primary mb-2' style="font-size: 24px;"></i>
                                    <h6>Attendance</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded bg-white">
                                    <i class='bx bx-message-square-dots text-primary mb-2' style="font-size: 24px;"></i>
                                    <h6>Communication</h6>
                                </div>
                            </div>
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
            });

            // Password visibility toggle
            $('.toggle-password').click(function() {
                const input = $(this).closest('.input-group').find('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).find('i').toggleClass('bx-show bx-hide');
            });

            // Form validation
            $('#loginForm').submit(function(e) {
                if (!$('#selectedRole').val()) {
                    e.preventDefault();
                    alert('Please select a role');
                }
            });
        });
    </script>
</body>
</html>
