<?php
// File: config/session_manager.php

/**
 * Forces a refresh of permissions for all active sessions of users with the specified role
 * @param int $role_id The ID of the role whose permissions were updated
 */
function mark_role_permissions_for_refresh($role_id) {
    if (!isset($_SESSION['roles_updated'])) {
        $_SESSION['roles_updated'] = [];
    }
    $_SESSION['roles_updated'][$role_id] = time();
}

/**
 * Checks if the current user's permissions need to be refreshed
 * @return bool True if permissions need refreshing, false otherwise
 */
function needs_permission_refresh() {
    if (!isset($_SESSION['roles_updated']) || !isset($_SESSION['role_id']) || !isset($_SESSION['last_permission_check'])) {
        return true;
    }
    
    $role_id = $_SESSION['role_id'];
    if (isset($_SESSION['roles_updated'][$role_id])) {
        $update_time = $_SESSION['roles_updated'][$role_id];
        $last_check = $_SESSION['last_permission_check'];
        return $update_time > $last_check;
    }
    
    return false;
}

/**
 * Updates the last permission check timestamp for the current session
 */
function update_permission_check_time() {
    $_SESSION['last_permission_check'] = time();
}
?>
