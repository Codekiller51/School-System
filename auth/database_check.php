<?php
require_once '../config/database.php';

function checkAndCreateTables($conn) {
    try {
        // Read the SQL file
        $sql = file_get_contents(__DIR__ . '/../database/auth_tables.sql');
        
        // Split into individual queries
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        // Execute each query
        foreach ($queries as $query) {
            if (!empty($query)) {
                $conn->exec($query);
            }
        }
        
        return true;
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

// Check if tables exist
function tablesExist($conn) {
    $tables = ['administrators', 'teachers', 'students', 'parents', 'login_logs', 'password_resets'];
    $existing = [];
    
    try {
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->rowCount() > 0) {
                $existing[] = $table;
            }
        }
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
    }
    
    return $existing;
}

// Get missing tables
$existingTables = tablesExist($conn);
$missingTables = array_diff(
    ['administrators', 'teachers', 'students', 'parents', 'login_logs', 'password_resets'],
    $existingTables
);

// Create missing tables if any
if (!empty($missingTables)) {
    if (checkAndCreateTables($conn)) {
        echo "Database tables created successfully!";
    } else {
        echo "Error creating database tables. Check error log for details.";
    }
} else {
    echo "All required tables exist!";
}
?>
