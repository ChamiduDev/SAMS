<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/utils.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = get_pdo_connection();
    echo "<h2>Database Test Results:</h2>";
    
    // 1. Check existing users
    echo "<h3>Current Users in Database:</h3>";
    $stmt = $pdo->query("SELECT id, username, email, role_id FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
    // 2. Test inserting a new user
    echo "<h3>Attempting to Create Test User:</h3>";
    
    $testUsername = 'test_user_' . time();
    $testEmail = 'test_' . time() . '@example.com';
    $testPassword = password_hash('test123', PASSWORD_DEFAULT);
    $testRoleId = 1; // admin role
    
    try {
        $pdo->beginTransaction();
        
        $sql = "INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)";
        echo "SQL: $sql<br>";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$testUsername, $testEmail, $testPassword, $testRoleId]);
        
        if ($result) {
            $newId = $pdo->lastInsertId();
            echo "Insert successful! New user ID: $newId<br>";
            
            // Verify the new user
            $verify = $pdo->query("SELECT * FROM users WHERE id = $newId");
            $newUser = $verify->fetch(PDO::FETCH_ASSOC);
            echo "New user details:<br><pre>";
            print_r($newUser);
            echo "</pre>";
            
            $pdo->commit();
            echo "Transaction committed successfully<br>";
        } else {
            $pdo->rollBack();
            echo "Insert failed! Error info:<br><pre>";
            print_r($stmt->errorInfo());
            echo "</pre>";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Error creating test user: " . $e->getMessage() . "<br>";
        echo "Error code: " . $e->getCode() . "<br>";
    }
    
    // 3. Verify table structure
    echo "<h3>Users Table Structure:</h3>";
    $structure = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    // 4. Check foreign key constraints
    echo "<h3>Foreign Key Constraints:</h3>";
    $fk = $pdo->query("
        SELECT * FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
        AND TABLE_NAME = 'users' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($fk);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
