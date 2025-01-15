<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only allow access to this page if no users exist
$stmt = $conn->query("SELECT COUNT(*) as count FROM administrators");
$result = $stmt->fetch();

if ($result['count'] > 0) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $employeeId = 'ADMIN' . sprintf('%03d', 1); // First admin

    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($phone)) {
        $error = 'All fields are required';
    } else {
        try {
            // Create administrator account
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("
                INSERT INTO administrators (
                    email, password, first_name, last_name, 
                    employee_id, phone, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $email, $hashedPassword, $firstName, 
                $lastName, $employeeId, $phone
            ]);

            // Log the setup
            $adminId = $conn->lastInsertId();
            $stmt = $conn->prepare("
                INSERT INTO notification_logs (
                    student_id, notification_type, notification_method, 
                    status, message
                ) VALUES (?, 'setup', 'system', 'sent', ?)
            ");
            $message = "Administrator account created from IP: " . $_SERVER['REMOTE_ADDR'];
            $stmt->execute([$adminId, $message]);

            $success = 'Administrator account created successfully. You can now login.';
            
            // Redirect to login page after 3 seconds
            header("refresh:3;url=login.php");
        } catch(PDOException $e) {
            error_log("Setup Error: " . $e->getMessage());
            $error = 'Failed to create administrator account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Initial Setup - School Management System</title>
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
        .setup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
        }
        .setup-header {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .setup-form {
            padding: 40px;
        }
        .form-control {
            padding: 12px;
            border-radius: 10px;
        }
        .btn-setup {
            padding: 12px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            width: 100%;
        }
        .btn-setup:hover {
            background: linear-gradient(135deg, #000DFF 0%, #6B73FF 100%);
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="setup-header">
                <h3 class="mb-3">Initial Setup</h3>
                <p class="mb-0">Create Administrator Account</p>
            </div>
            <div class="setup-form">
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

                <form method="post" action="">
                    <div class="mb-4">
                        <label class="form-label">First Name</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class='bx bx-user'></i>
                            </span>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Last Name</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class='bx bx-user'></i>
                            </span>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class='bx bx-envelope'></i>
                            </span>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>

                    <div class="mb-4">
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
                        <div class="password-strength mt-2"></div>
                    </div>

                    <button type="submit" class="btn btn-setup text-white">
                        <i class='bx bx-check-circle me-2'></i>Create Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Password visibility toggle
            $('.toggle-password').click(function() {
                const input = $(this).closest('.input-group').find('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).find('i').toggleClass('bx-show bx-hide');
            });

            // Password strength checker
            $('#password').on('input', function() {
                const password = $(this).val();
                let strength = 0;
                let message = '';

                // Length check
                if (password.length >= 8) strength += 1;
                // Uppercase check
                if (password.match(/[A-Z]/)) strength += 1;
                // Lowercase check
                if (password.match(/[a-z]/)) strength += 1;
                // Number check
                if (password.match(/[0-9]/)) strength += 1;
                // Special character check
                if (password.match(/[^A-Za-z0-9]/)) strength += 1;

                switch(strength) {
                    case 0:
                    case 1:
                        message = '<span class="text-danger">Very Weak</span>';
                        break;
                    case 2:
                        message = '<span class="text-warning">Weak</span>';
                        break;
                    case 3:
                        message = '<span class="text-info">Medium</span>';
                        break;
                    case 4:
                        message = '<span class="text-primary">Strong</span>';
                        break;
                    case 5:
                        message = '<span class="text-success">Very Strong</span>';
                        break;
                }

                $('.password-strength').html('Password Strength: ' + message);
            });
        });
    </script>
</body>
</html>
