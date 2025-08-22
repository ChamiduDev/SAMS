<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: list.php');
        exit();
    }

    $subject_id_to_delete = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;

    if ($subject_id_to_delete <= 0) {
        set_message('error', 'Invalid subject ID for deletion.');
        header('Location: list.php');
        exit();
    }

    try {
        // Proceed with deletion
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id_to_delete]);

        if ($stmt->rowCount() > 0) {
            set_message('success', 'Subject deleted successfully.');
        } else {
            set_message('error', 'Subject not found or could not be deleted.');
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