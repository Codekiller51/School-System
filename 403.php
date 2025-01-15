<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - School Management System</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        p {
            color: #7f8c8d;
            margin-bottom: 2rem;
        }
        .btn-back {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <i class='bx bx-shield-x error-icon'></i>
            <h1>403 Forbidden</h1>
            <p>Sorry, you don't have permission to access this page.</p>
            
            <?php if (isset($_SESSION['role'])): ?>
                <a href="/School-System/<?php echo $_SESSION['role']; ?>/index.php" class="btn-back">
                    <i class='bx bx-arrow-back me-2'></i>Back to Dashboard
                </a>
            <?php else: ?>
                <a href="/School-System/auth/login.php" class="btn-back">
                    <i class='bx bx-log-in-circle me-2'></i>Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
