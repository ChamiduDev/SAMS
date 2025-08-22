<?php
require_once '../includes/header.php';
require_once '../../config/utils.php';
require_once '../includes/auth_check.php';

$pdo = get_pdo_connection();

// Check if user has permission to delete students
if (!has_permission('students_delete')) {
    set_message('error', 'You do not have permission to delete students.');
    header('Location: list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: list.php');
        exit();
    }

    if (!isset($_POST['student_id'])) {
        set_message('error', 'Student ID not provided.');
        header('Location: list.php');
        exit();
    }

    $student_id_to_delete = filter_var($_POST['student_id'], FILTER_VALIDATE_INT);

    if ($student_id_to_delete <= 0) {
        set_message('error', 'Invalid student ID for deletion.');
        header('Location: list.php');
        exit();
    }

    try {
        // Perform soft delete by updating the status column
        $stmt = $pdo->prepare("UPDATE students SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$student_id_to_delete]);

        if ($stmt->rowCount() > 0) {
            set_message('success', 'Student marked as deleted successfully.');
        } else {
            set_message('error', 'Student not found or could not be marked as deleted.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Database error: ' . $e->getMessage());
    }

    header('Location: list.php');
    exit();
} else {
    set_message('error', 'Invalid request method.');
    header('Location: list.php');
    exit();
}
?>