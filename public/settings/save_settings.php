<?php
require_once '../includes/header.php';
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !has_role('admin')) {
    set_message('error', 'You do not have permission to modify settings.');
    header('Location: ../dashboard.php');
    exit();
}

// CSRF Protection
if (!verify_csrf_token($_POST['csrf_token'])) {
    set_message('error', 'Invalid CSRF token.');
    header('Location: index.php');
    exit();
}

$pdo = get_pdo_connection();

try {
    switch ($_POST['settings_type']) {
        case 'general':
            // Validate inputs
            $session_timeout = filter_var($_POST['session_timeout'], FILTER_VALIDATE_INT);
            $default_timezone = filter_var($_POST['default_timezone'], FILTER_SANITIZE_STRING);
            $date_format = filter_var($_POST['date_format'], FILTER_SANITIZE_STRING);

            if (!$session_timeout || $session_timeout < 1) {
                throw new Exception('Invalid session timeout value');
            }

            if (!in_array($default_timezone, DateTimeZone::listIdentifiers())) {
                throw new Exception('Invalid timezone');
            }

            $valid_date_formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'd.m.Y'];
            if (!in_array($date_format, $valid_date_formats)) {
                throw new Exception('Invalid date format');
            }

            // Update general settings
            $stmt = $pdo->prepare("
                UPDATE system_settings SET 
                session_timeout = ?,
                default_timezone = ?,
                date_format = ?
                WHERE id = 1
            ");
            $stmt->execute([$session_timeout, $default_timezone, $date_format]);
            break;

        case 'academic':
            // Validate inputs
            $attendance_threshold = filter_var($_POST['attendance_threshold'], FILTER_VALIDATE_FLOAT);
            $passing_grade = filter_var($_POST['passing_grade'], FILTER_VALIDATE_FLOAT);

            if ($attendance_threshold === false || $attendance_threshold < 0 || $attendance_threshold > 100) {
                throw new Exception('Invalid attendance threshold value');
            }

            if ($passing_grade === false || $passing_grade < 0 || $passing_grade > 100) {
                throw new Exception('Invalid passing grade value');
            }

            // Update academic settings
            $stmt = $pdo->prepare("
                UPDATE system_settings SET 
                attendance_threshold = ?,
                passing_grade = ?
                WHERE id = 1
            ");
            $stmt->execute([$attendance_threshold, $passing_grade]);
            break;

        case 'notifications':
            // Validate inputs
            $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
            $enable_parent_portal = isset($_POST['enable_parent_portal']) ? 1 : 0;

            // Update notification settings
            $stmt = $pdo->prepare("
                UPDATE system_settings SET 
                enable_notifications = ?,
                enable_parent_portal = ?
                WHERE id = 1
            ");
            $stmt->execute([$enable_notifications, $enable_parent_portal]);
            break;

        case 'school':
            // Validate inputs
            $school_name = filter_var($_POST['school_name'], FILTER_SANITIZE_STRING);
            $school_email = filter_var($_POST['school_email'], FILTER_VALIDATE_EMAIL);
            $school_phone = filter_var($_POST['school_phone'], FILTER_SANITIZE_STRING);
            $school_address = filter_var($_POST['school_address'], FILTER_SANITIZE_STRING);

            if (!$school_name) {
                throw new Exception('School name is required');
            }
            if (!$school_email) {
                throw new Exception('Valid email address is required');
            }

            $logo_path = null;
            // Handle logo upload if present
            if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['school_logo']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
                }

                $max_size = 5 * 1024 * 1024; // 5MB
                if ($_FILES['school_logo']['size'] > $max_size) {
                    throw new Exception('File size too large. Maximum size is 5MB.');
                }

                $upload_dir = __DIR__ . '/../assets/images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'school_logo_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $upload_path)) {
                    $logo_path = '../assets/images/' . $new_filename;
                }
            }

            // Update school information
            $sql = "INSERT INTO school_info 
                   (id, school_name, school_email, school_phone, school_address" . ($logo_path ? ", school_logo" : "") . ") 
                   VALUES (1, ?, ?, ?, ?" . ($logo_path ? ", ?" : "") . ")
                   ON DUPLICATE KEY UPDATE 
                   school_name = VALUES(school_name),
                   school_email = VALUES(school_email),
                   school_phone = VALUES(school_phone),
                   school_address = VALUES(school_address)" .
                   ($logo_path ? ", school_logo = VALUES(school_logo)" : "");

            $params = [$school_name, $school_email, $school_phone, $school_address];
            if ($logo_path) {
                $params[] = $logo_path;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            break;

        default:
            throw new Exception('Invalid settings type');
    }

    set_message('success', 'Settings updated successfully.');
} catch (Exception $e) {
    set_message('error', 'Failed to update settings: ' . $e->getMessage());
} catch (PDOException $e) {
    set_message('error', 'Database error while updating settings: ' . $e->getMessage());
}

header('Location: index.php');
exit();
?>
