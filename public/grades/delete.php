<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/utils.php';

$pdo = get_pdo_connection();

// Check if user is logged in and has admin/teacher role
if (!isset($_SESSION['user_id']) || (!has_role('admin') && !has_role('teacher'))) {
    set_message('error', 'Access denied. You must be an administrator or teacher to delete results.');
    header('Location: ../login.php');
    exit();
}

$result_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$result_id) {
    set_message('error', 'Invalid result ID.');
    header('Location: list.php');
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM exam_results WHERE id = ?");
    $stmt->execute([$result_id]);

    if ($stmt->rowCount() > 0) {
        set_message('success', 'Exam result deleted successfully.');
    } else {
        set_message('error', 'Exam result not found or could not be deleted.');
    }
} catch (PDOException $e) {
    set_message('error', 'Database error deleting exam result: ' . $e->getMessage());
}

header('Location: list.php');
exit();
?>
