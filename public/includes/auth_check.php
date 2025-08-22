<?php
// public/includes/auth_check.php

// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/utils.php';
require_once __DIR__ . '/../../config/session_manager.php';

$pdo = get_pdo_connection();

// Set session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds

// Check if session has expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session has expired
    session_unset();
    session_destroy();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Location: " . BASE_URL . "public/login.php?error=session_expired");
    exit;
}

// Security: Check if user is logged in and essential session variables are set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || !isset($_SESSION['role_name'])) {
    // Using an absolute path for the redirect to make it more robust
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Location: " . BASE_URL . "public/login.php?error=auth_required");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Fetch user permissions if:
// - Not already in session
// - Role ID changed
// - Permissions for this role were updated
if (!isset($_SESSION['user_permissions']) || 
    (isset($_SESSION['last_role_id']) && $_SESSION['last_role_id'] !== $_SESSION['role_id']) ||
    needs_permission_refresh()) {
    $_SESSION['user_permissions'] = [];
    
    // For admin role (ID 1), fetch all available permissions
    if ($_SESSION['role_id'] == 1) {
        $stmt = $pdo->prepare("SELECT name FROM permissions");
        $stmt->execute();
        $_SESSION['user_permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // For other roles, fetch assigned permissions
        $stmt = $pdo->prepare("
            SELECT rp.permission 
            FROM role_permissions rp 
            JOIN permissions p ON rp.permission = p.name 
            WHERE rp.role_id = ?
        ");
        $stmt->execute([$_SESSION['role_id']]);
        $_SESSION['user_permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    $_SESSION['last_role_id'] = $_SESSION['role_id'];
    update_permission_check_time();
}

// Determine the current page's required permission
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

$requiredPermission = null;

// Define permissions for common pages
switch ($currentDir) {
    case 'public':
        switch ($currentFile) {
            case 'dashboard.php': $requiredPermission = 'dashboard_view'; break;
            // Add other top-level public pages here if they need specific permissions
        }
        break;
    case 'students':
        switch ($currentFile) {
            case 'add.php': $requiredPermission = 'students_add'; break;
            case 'list.php': $requiredPermission = 'students_list'; break;
            case 'edit.php': $requiredPermission = 'students_edit'; break;
            case 'delete.php': $requiredPermission = 'students_delete'; break;
            case 'view.php': $requiredPermission = 'students_view'; break;
        }
        break;
    case 'courses':
        switch ($currentFile) {
            case 'add.php': $requiredPermission = 'courses_add'; break;
            case 'list.php': $requiredPermission = 'courses_list'; break;
            case 'edit.php': $requiredPermission = 'courses_edit'; break;
            case 'delete.php': $requiredPermission = 'courses_delete'; break;
        }
        break;
    case 'subjects':
        switch ($currentFile) {
            case 'add.php': $requiredPermission = 'subjects_add'; break;
            case 'list.php': $requiredPermission = 'subjects_list'; break;
            case 'edit.php': $requiredPermission = 'subjects_edit'; break;
            case 'delete.php': $requiredPermission = 'subjects_delete'; break;
        }
        break;
    case 'exams':
        switch ($currentFile) {
            case 'add.php': $requiredPermission = 'exams_add'; break;
            case 'list.php': $requiredPermission = 'exams_list'; break;
            case 'edit.php': $requiredPermission = 'exams_edit'; break;
            case 'delete.php': $requiredPermission = 'exams_delete'; break;
            case 'view.php': $requiredPermission = 'exams_view'; break;
        }
        break;
    case 'grades':
        switch ($currentFile) {
            case 'mark.php': $requiredPermission = 'grades_mark'; break;
            case 'list.php': $requiredPermission = 'grades_list'; break;
            case 'edit.php': $requiredPermission = 'grades_edit'; break;
            case 'delete.php': $requiredPermission = 'grades_delete'; break;
        }
        break;
    case 'attendance':
        switch ($currentFile) {
            case 'mark.php': $requiredPermission = 'attendance_mark'; break;
            case 'list.php': $requiredPermission = 'attendance_list'; break;
            case 'delete.php': $requiredPermission = 'attendance_delete'; break;
            case 'report.php': $requiredPermission = 'attendance_report'; break;
        }
        break;
    case 'fees':
        switch ($currentFile) {
            case 'assign.php': $requiredPermission = 'fees_assign'; break;
            case 'list.php': $requiredPermission = 'fees_list'; break;
            case 'edit.php': $requiredPermission = 'fees_edit'; break;
            case 'view.php': $requiredPermission = 'fees_view'; break;
        }
        break;
    case 'fee_structures':
        switch ($currentFile) {
            case 'add.php': $requiredPermission = 'fee_structures_add'; break;
            case 'list.php': $requiredPermission = 'fee_structures_list'; break;
        }
        break;
    case 'fee_types':
        switch ($currentFile) {
            case 'add.php': $requiredPermission = 'fee_types_add'; break;
            case 'list.php': $requiredPermission = 'fee_types_list'; break;
        }
        break;
    case 'payments':
        switch ($currentFile) {
            case 'record.php': $requiredPermission = 'payments_record'; break;
            case 'list.php': $requiredPermission = 'payments_list'; break;
        }
        break;
    case 'notifications':
        switch ($currentFile) {
            case 'create.php': $requiredPermission = 'notifications_create'; break;
            case 'list.php': $requiredPermission = 'notifications_list'; break;
            case 'view.php': $requiredPermission = 'notifications_view'; break;
            case 'delete.php': $requiredPermission = 'notifications_delete'; break;
        }
        break;
    case 'users':
        switch ($currentFile) {
            case 'add.php': $requiredPermission = 'users_add'; break;
            case 'list.php': $requiredPermission = 'users_list'; break;
            case 'edit.php': $requiredPermission = 'users_edit'; break;
            case 'delete.php': $requiredPermission = 'users_delete'; break;
        }
        break;
    case 'roles':
        switch ($currentFile) {
            case 'add.php': $requiredPermission = 'roles_add'; break;
            case 'list.php': $requiredPermission = 'roles_list'; break;
            case 'edit.php': $requiredPermission = 'roles_edit'; break;
            case 'delete.php': $requiredPermission = 'roles_delete'; break;
        }
        break;
    case 'settings':
        switch ($currentFile) {
            case 'school_info.php': $requiredPermission = 'settings_school_info'; break;
            case 'grading_scale.php': $requiredPermission = 'settings_grading_scale'; break;
        }
        break;
}

// Check permission unless it's the admin role (role_id = 1)
if ($_SESSION['role_id'] != 1 && $requiredPermission !== null) {
    if (!in_array($requiredPermission, $_SESSION['user_permissions'])) {
        // Redirect to an unauthorized page or display an error
        header("Location: " . BASE_URL . "public/unauthorized.php"); // Create this page later
        exit;
    }
}

// Function to check permission anywhere in the application
function hasPermission($permission) {
    // Admin (role_id 1) always has permission
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        return true;
    }
    return isset($_SESSION['user_permissions']) && in_array($permission, $_SESSION['user_permissions']);
}

