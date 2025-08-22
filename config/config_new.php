<?php
// Load all configuration files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/school_settings.php';

// Base URL for the application
define('BASE_URL', 'http://localhost/');

// Error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
