<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/utils.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function log_message($message, $data = null) {
    echo "<div style='margin: 10px; padding: 10px; border: 1px solid #ccc;'>";
    echo "<strong>$message</strong><br>";
    if ($data !== null) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    echo "</div>";
}

try {
    // 1. Connect to database
    $pdo = get_pdo_connection();
    log_message("Database connection successful");
    
    // 2. Start transaction
    $pdo->beginTransaction();
    log_message("Transaction started");
    
    // 3. Generate test user data
    $testData = [
        'username' => 'test_teacher_' . time(),
        'email' => 'test_teacher_' . time() . '@school.com',
        'password' => password_hash('test123', PASSWORD_DEFAULT),
        'role' => 'teacher'
    ];
    log_message("Test user data prepared:", $testData);
    
    // 4. Get role ID
    $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $roleStmt->execute([$testData['role']]);
    $roleData = $roleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$roleData) {
        throw new Exception("Role not found: " . $testData['role']);
    }
    $roleId = $roleData['id'];
    log_message("Role ID found:", $roleId);
    
    // 5. Insert user
    $sql = "INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)";
    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':username' => $testData['username'],
        ':email' => $testData['email'],
        ':password' => $testData['password'],
        ':role_id' => $roleId
    ];
    
    log_message("Executing INSERT with parameters:", $params);
    
    $result = $stmt->execute($params);
    
    if (!$result) {
        throw new Exception("Insert failed: " . implode(", ", $stmt->errorInfo()));
    }
    
    $newUserId = $pdo->lastInsertId();
    log_message("User inserted successfully with ID:", $newUserId);
    
    // 6. Verify the new user
    $verifyStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $verifyStmt->execute([$newUserId]);
    $newUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$newUser) {
        throw new Exception("Failed to verify new user");
    }
    log_message("New user verified in database:", $newUser);
    
    // 7. Commit transaction
    $pdo->commit();
    log_message("Transaction committed successfully");
    
    // 8. Final verification
    $finalCheck = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $finalCheck->execute([$newUserId]);
    $finalUser = $finalCheck->fetch(PDO::FETCH_ASSOC);
    log_message("Final user verification:", $finalUser);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
        log_message("Transaction rolled back");
    }
    log_message("ERROR: " . $e->getMessage());
    log_message("Stack trace:", $e->getTraceAsString());
} finally {
    echo "<hr>";
    echo "<h3>Database Status:</h3>";
    
    try {
        $stmt = $pdo->query("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC LIMIT 5");
        $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        log_message("Most recent users in database:", $recentUsers);
    } catch (Exception $e) {
        log_message("Error checking recent users: " . $e->getMessage());
    }
}
?>
