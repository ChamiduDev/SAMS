<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/utils.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database and Session Diagnostics</h1>";

// Start fresh session
session_start();

// Function to log debug info
function debug_log($title, $data = null) {
    echo "<div style='margin: 10px; padding: 10px; border: 1px solid #ccc;'>";
    echo "<h3>$title</h3>";
    if ($data !== null) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    echo "</div>";
}

// Test 1: Database Connection
try {
    $pdo = get_pdo_connection();
    debug_log("Database Connection", "SUCCESS - Connected to MySQL");
    
    // Test database selection
    $stmt = $pdo->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    debug_log("Current Database", $dbName);
} catch (PDOException $e) {
    debug_log("Database Connection Error", $e->getMessage());
}

// Test 2: Session Functionality
$_SESSION['test_key'] = 'test_value_' . time();
debug_log("Session Data", $_SESSION);

// Test 3: Database Tables
try {
    // Check roles table
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Roles Table", $roles);
    
    // Check users table structure
    $stmt = $pdo->query("DESCRIBE users");
    $userStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Users Table Structure", $userStructure);
    
    // Count existing users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    debug_log("Total Users", $userCount);
} catch (PDOException $e) {
    debug_log("Database Tables Error", $e->getMessage());
}

// Test 4: Message System
set_message('success', 'Test success message');
set_message('error', 'Test error message');
$messages = get_message();
debug_log("Message System", $messages);

?>
