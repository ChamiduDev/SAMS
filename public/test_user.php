<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/utils.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = get_pdo_connection();

    // Test data
    $username = 'testuser_' . time();
    $email = 'test_' . time() . '@example.com';
    $password = password_hash('test123', PASSWORD_DEFAULT);
    $roleId = 1; // admin role

    // Prepare and execute insert
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role_id)
        VALUES (:username, :email, :password, :role_id)
    ");

    $result = $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $password,
        ':role_id' => $roleId
    ]);

    if ($result) {
        $userId = $pdo->lastInsertId();
        echo "Test user created successfully with ID: $userId\n";
        
        // Verify the user was created
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nUser details:\n";
        print_r($user);
    } else {
        echo "Failed to create test user\n";
        print_r($stmt->errorInfo());
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
