<?php
// public/logout.php

require_once '../config/utils.php';

// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Verify CSRF token
if (!isset($_GET['csrf_token']) || !function_exists('verify_csrf_token') || !verify_csrf_token($_GET['csrf_token'])) {
    die('Invalid logout request. Please use the logout link from the menu.');
}

// Clear all session data
$_SESSION = [];

// Set cache control headers to prevent back-button access
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to the login page using an absolute path
header("Location: /public/login.php?status=logged_out");
exit;

?>