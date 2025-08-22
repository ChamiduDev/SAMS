<?php
require_once '../../config/config.php';
require_once '../../config/utils.php';

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    $pdo = get_pdo_connection();
    echo "<h3>Database and Role Testing</h3>";
    echo "<pre>";
    
    // Test database connection
    echo "Database connection successful\n\n";
    
    // Show database settings
    echo "Database settings:\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "User: " . DB_USER . "\n";
    echo "Charset: " . DB_CHARSET . "\n\n";

    // Check if roles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    if (!$stmt->fetch()) {
        echo "Roles table not found! Creating...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE
            )
        ");
        echo "Roles table created.\n\n";

        // Insert default roles
        $pdo->exec("INSERT INTO roles (id, name) VALUES 
            (1, 'admin'),
            (2, 'teacher'),
            (3, 'student')
        ");
        echo "Default roles inserted.\n\n";
    }

    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetch()) {
        echo "Users table not found! Creating...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES roles(id)
            )
        ");
        echo "Users table created.\n\n";
    }
    
    // Check roles table
    $stmt = $pdo->query("SELECT * FROM roles");
    echo "Roles in database:\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "\n";
    
    // Test user creation
    $username = "testuser_" . time();
    $email = "test_" . time() . "@example.com";
    $password = password_hash("test123456", PASSWORD_DEFAULT);
    
    // Get role ID for 'admin'
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute(['admin']);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$role) {
        throw new Exception("Admin role not found in database!");
    }
    
    $role_id = $role['id'];

    echo "Attempting to create test user:\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Role ID: $role_id\n\n";

    $pdo->beginTransaction();
    
    $sql = "INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)";
    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':username' => $username,
        ':email' => $email,
        ':password' => $password,
        ':role_id' => $role_id
    ];
    
    $result = $stmt->execute($params);
    
    if ($result) {
        $newUserId = $pdo->lastInsertId();
        echo "User created successfully! ID: $newUserId\n\n";
        
        // Verify the user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$newUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "New user details:\n";
        print_r($user);
        
        $pdo->commit();
    } else {
        $pdo->rollBack();
        echo "Failed to create user\n";
        print_r($stmt->errorInfo());
    }
    
    echo "</pre>";
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<pre>Error: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "</pre>";
}
