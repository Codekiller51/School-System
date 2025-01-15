<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';

    try {
        if (empty($email) || empty($role)) {
            throw new Exception('Email and role are required');
        }

        // Determine table based on role
        $table = match ($role) {
            'student' => 'students',
            'teacher' => 'teachers',
            'parent' => 'parents',
            'admin' => 'administrators',
            default => throw new Exception('Invalid role selected')
        };

        // Check if email exists
        $stmt = $conn->prepare("SELECT id, first_name FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception('Email not found');
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store reset token
        $stmt = $conn->prepare("
            INSERT INTO password_resets (
                user_id, role, token, expiry, created_at
            ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$user['id'], $role, $token, $expiry]);

        // Send reset email
        $resetLink = "https://{$_SERVER['HTTP_HOST']}/auth/reset-password.php?token=" . $token;
        $to = $email;
        $subject = "Password Reset Request";
        $message = "
            Dear {$user['first_name']},

            You have requested to reset your password. Click the link below to reset your password:

            {$resetLink}

            This link will expire in 1 hour.

            If you did not request this password reset, please ignore this email.

            Best regards,
            School Management System
        ";
        $headers = "From: noreply@school.com";

        mail($to, $subject, $message, $headers);

        $success = 'Password reset instructions have been sent to your email';

    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - School Management System</title>
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
        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        .reset-header {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .reset-form {
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
        .btn-reset {
            padding: 12px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            width: 100%;
        }
        .btn-reset:hover {
            background: linear-gradient(135deg, #000DFF 0%, #6B73FF 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="reset-header">
                <h3 class="mb-3">Forgot Password?</h3>
                <p class="mb-0">Enter your email to reset your password</p>
            </div>
            <div class="reset-form">
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

                <form method="post" action="" id="resetForm">
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

                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class='bx bx-envelope'></i>
                            </span>
                            <input type="email" class="form-control" name="email" 
                                   placeholder="Enter your email address" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-reset text-white mb-4">
                        <i class='bx bx-mail-send me-2'></i>Send Reset Link
                    </button>

                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">
                            <i class='bx bx-arrow-back me-2'></i>Back to Login
                        </a>
                    </div>
                </form>
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

            // Form validation
            $('#resetForm').submit(function(e) {
                if (!$('#selectedRole').val()) {
                    e.preventDefault();
                    alert('Please select a role');
                }
            });
        });
    </script>
</body>
</html>
