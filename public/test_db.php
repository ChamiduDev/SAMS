<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/utils.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Test database connection
    $pdo = get_pdo_connection();
    echo "Database connection successful\n";

    // Test roles table
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRoles in database:\n";
    print_r($roles);

    // Test users table structure
    $stmt = $pdo->query("DESCRIBE users");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nUsers table structure:\n";
    print_r($structure);

    // Test foreign key constraint
    $stmt = $pdo->query("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'users' AND REFERENCED_TABLE_NAME = 'roles'");
    $fk = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nForeign key constraints:\n";
    print_r($fk);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
