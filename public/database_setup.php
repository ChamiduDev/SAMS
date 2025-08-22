<?php
// Database setup verification script
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

function debug_log($message) {
    $log_file = __DIR__ . '/database_setup.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "$message<br>";
}

try {
    debug_log("Starting database verification...");
    
    // Create PDO connection
    $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    debug_log("Connected to MySQL server successfully");
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $dbExists = $stmt->fetchColumn();
    
    if (!$dbExists) {
        debug_log("Database does not exist. Creating database " . DB_NAME);
        $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        debug_log("Database created successfully");
    } else {
        debug_log("Database already exists");
    }
    
    // Switch to the database
    $pdo->exec("USE " . DB_NAME);
    debug_log("Using database: " . DB_NAME);
    
    // Check roles table
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    if (!$stmt->fetchColumn()) {
        debug_log("Creating roles table...");
        $pdo->exec("
            CREATE TABLE roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        debug_log("Roles table created");
        
        // Insert default roles
        $pdo->exec("
            INSERT INTO roles (name) VALUES 
            ('admin'),
            ('teacher'),
            ('student')
        ");
        debug_log("Default roles inserted");
    } else {
        debug_log("Roles table exists");
        // Display current roles
        $roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
        debug_log("Current roles: " . print_r($roles, true));
    }
    
    // Check users table
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetchColumn()) {
        debug_log("Creating users table...");
        $pdo->exec("
            CREATE TABLE users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES roles(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        debug_log("Users table created");
    } else {
        debug_log("Users table exists");
        // Display table structure
        $structure = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        debug_log("Users table structure: " . print_r($structure, true));
        
        // Check foreign key
        $fk = $pdo->query("
            SELECT * FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
            AND TABLE_NAME = 'users' 
            AND REFERENCED_TABLE_NAME = 'roles'
        ")->fetchAll(PDO::FETCH_ASSOC);
        debug_log("Foreign key check: " . print_r($fk, true));
    }
    
    debug_log("Database verification complete");
    
} catch (PDOException $e) {
    debug_log("ERROR: " . $e->getMessage());
    debug_log("Error Code: " . $e->getCode());
    debug_log("Stack trace: " . $e->getTraceAsString());
}
?>
