<?php
require_once '../includes/header.php';
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// CSRF Protection
if (!verify_csrf_token($_POST['csrf_token'])) {
    set_message('error', 'Invalid CSRF token.');
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Validate passwords
if (!$current_password || !$new_password || !$confirm_password) {
    set_message('error', 'All password fields are required.');
    header('Location: index.php');
    exit();
}

if ($new_password !== $confirm_password) {
    set_message('error', 'New passwords do not match.');
    header('Location: index.php');
    exit();
}

$pdo = get_pdo_connection();

try {
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password'])) {
        set_message('error', 'Current password is incorrect.');
        header('Location: index.php');
        exit();
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $user_id]);

    set_message('success', 'Password updated successfully.');
} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
}

header('Location: index.php');
exit();
?>
