<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

// 2. Get PDO connection and check for CSRF function
$pdo = get_pdo_connection();
if (!function_exists('verify_csrf_token')) {
    die("Required security components are not available. Please check your configuration.");
}

// 3. Check for Notification ID
$notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($notification_id === 0) {
    header("Location: list.php?error=missing_id");
    exit;
}

// 4. CSRF Token Validation
if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
    die("Invalid request (CSRF token mismatch). Please go back and try again.");
}

try {
    // --- Database Deletion ---
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bindParam(1, $notification_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header("Location: list.php?success=deleted");
        exit;
    } else {
        header("Location: list.php?error=delete_failed");
        exit;
    }
} catch (PDOException $e) {
    error_log("Notification deletion failed: " . $e->getMessage());
    header("Location: list.php?error=db_error");
    exit;
}

?>
