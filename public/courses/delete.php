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

    $course_id_to_delete = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

    if ($course_id_to_delete <= 0) {
        set_message('error', 'Invalid course ID for deletion.');
        header('Location: list.php');
        exit();
    }

    try {
        // Check if the course has any linked subjects
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE course_id = ?");
        $stmt->execute([$course_id_to_delete]);
        $linked_subjects_count = $stmt->fetchColumn();

        if ($linked_subjects_count > 0) {
            set_message('error', 'Cannot delete course. It has ' . $linked_subjects_count . ' linked subject(s). Please delete the subjects first.');
            header('Location: list.php');
            exit();
        }

        // Proceed with deletion
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$course_id_to_delete]);

        if ($stmt->rowCount() > 0) {
            set_message('success', 'Course deleted successfully.');
        } else {
            set_message('error', 'Course not found or could not be deleted.');
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