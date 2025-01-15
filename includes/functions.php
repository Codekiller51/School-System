<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function isParent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'parent';
}

function redirectBasedOnRole($role) {
    switch ($role) {
        case 'admin':
            header('Location: /School-System/admin/index.php');
            break;
        case 'teacher':
            header('Location: /School-System/teacher/index.php');
            break;
        case 'student':
            header('Location: /School-System/student/index.php');
            break;
        case 'parent':
            header('Location: /School-System/parent/index.php');
            break;
        default:
            header('Location: /School-System/auth/login.php');
    }
    exit();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /School-System/auth/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /School-System/403.php');
        exit();
    }
}

function requireTeacher() {
    requireLogin();
    if (!isTeacher()) {
        header('Location: /School-System/403.php');
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: /School-System/403.php');
        exit();
    }
}

function requireParent() {
    requireLogin();
    if (!isParent()) {
        header('Location: /School-System/403.php');
        exit();
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function calculateGrade($percentage) {
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}

function getAcademicYear() {
    $month = date('n');
    $year = date('Y');
    if ($month < 9) {
        return ($year - 1) . '-' . $year;
    }
    return $year . '-' . ($year + 1);
}

function flashMessage($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $message = $_SESSION['flash']['message'];
        $type = $_SESSION['flash']['type'];
        unset($_SESSION['flash']);
        
        return "
            <div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        ";
    }
    return '';
}
?>
