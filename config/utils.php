<?php

// Include required configurations
require_once __DIR__ . '/database.php';

/**
 * Check if the current user has a specific permission
 * @param string $permission The permission to check for
 * @return bool True if the user has the permission, false otherwise
 */
function has_permission($permission) {
    // Check if user is logged in and has permissions in session
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_permissions'])) {
        return false;
    }

    // Admin role (ID 1) has all permissions
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        return true;
    }

    // Check if the permission exists in the user's permissions array
    return in_array($permission, $_SESSION['user_permissions']);
}

/**
 * Performs a clean redirect with cache prevention
 * @param string $url The URL to redirect to
 * @param bool $forceRefresh Whether to force a page refresh
 */
function redirect_with_refresh($url, $forceRefresh = false) {
    // End and clean all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Ensure session data is saved
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Set cache prevention headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    
    // Add refresh parameter if needed
    if ($forceRefresh) {
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        $url .= $separator . 'refresh=' . time();
    }
    
    // Perform redirect
    header("Location: $url");
    exit();
}


/**
 * Generates a CSRF token and stores it in the session
 * @return string The generated CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies if the provided CSRF token matches the one in the session
 * @param string $token The token to verify
 * @return bool True if the token is valid, false otherwise
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Checks if the current user has a specific role.
 * @param string $role_name The name of the role to check (e.g., 'admin', 'teacher', 'student', 'parent').
 * @return bool True if the user has the role, false otherwise.
 */
function has_role($role_name) {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $role_name;
}

/**
 * Checks if the current user has admin access. If not, redirects to login page.
 */
function check_admin_access() {
    if (!isset($_SESSION['user_id'])) {
        // Not logged in, redirect to login
        header('Location: ' . BASE_URL . 'public/login.php?error=auth_required');
        exit();
    }
    if (!has_role('admin')) {
        // Logged in but not admin, redirect to dashboard or unauthorized page
        header('Location: ' . BASE_URL . 'public/dashboard.php?error=unauthorized_access');
        exit();
    }
}

/**
 * Validates a date string in 'YYYY-MM-DD' format.
 * @param string $date The date string to validate.
 * @return bool True if the date is valid, false otherwise.
 */
function isValidDate($date) {
    return (bool)strtotime($date) && date('Y-m-d', strtotime($date)) === $date;
}

/**
 * Sets a flash message in the session.
 * @param string $type The type of message (e.g., 'success', 'error', 'warning', 'info').
 * @param string $message The message content.
 */
function set_message($type, $message) {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = [
        'type' => $type,
        'content' => $message
    ];
    error_log("Message set: $type - $message"); // Debug log
}

/**
 * Retrieves and clears flash messages from the session.
 * @return array Array of messages, each containing 'type' and 'content'.
 */
function get_message() {
    $messages = isset($_SESSION['messages']) ? $_SESSION['messages'] : [];
    unset($_SESSION['messages']);
    return $messages;
}

/**
 * Displays Bootstrap alerts for all messages.
 * @param array $messages Array of messages to display.
 */
function display_message($messages) {
    if (!is_array($messages)) {
        return;
    }
    foreach ($messages as $message) {
        if (is_array($message) && isset($message['type']) && isset($message['content'])) {
            $type = htmlspecialchars($message['type']);
            $content = htmlspecialchars($message['content']);
            $icon = $message['type'] === 'success' ? 'check-circle' : 'exclamation-circle';
            
            echo '<div class="alert alert-' . $type . ' d-flex align-items-center" role="alert">';
            echo '<i class="fas fa-' . $icon . ' me-2"></i>';
            echo $content;
            echo '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
}

/**
 * Checks if a database table exists in the current schema.
 * @param PDO $pdo
 * @param string $tableName
 * @return bool
 */
function database_table_exists(PDO $pdo, string $tableName): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Checks if a column exists on a table in the current schema.
 * @param PDO $pdo
 * @param string $tableName
 * @param string $columnName
 * @return bool
 */
function database_column_exists(PDO $pdo, string $tableName, string $columnName): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName, $columnName]);
    return (bool)$stmt->fetchColumn();
}

?>