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
$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

if (!$username || !$email) {
    set_message('error', 'Invalid input data.');
    header('Location: index.php');
    exit();
}

$pdo = get_pdo_connection();

try {
    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        set_message('error', 'Email address is already in use.');
        header('Location: index.php');
        exit();
    }

    // Update user information
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->execute([$username, $email, $user_id]);

    // Update session username
    $_SESSION['username'] = $username;

    set_message('success', 'Profile updated successfully.');
} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
}

header('Location: index.php');
exit();
?>
