<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$exam_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$exam_id) {
    set_message('error', 'Invalid exam ID.');
    header('Location: list.php');
    exit();
}

// CSRF Protection
$csrf_token = filter_input(INPUT_GET, 'csrf_token');
if (!verify_csrf_token($csrf_token)) {
    set_message('error', 'Invalid CSRF token.');
    header('Location: list.php');
    exit();
}

try {
    // First check if the exam exists and fetch exam details
    $stmt = $pdo->prepare("SELECT title FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam) {
        set_message('error', 'Exam not found.');
        header('Location: list.php');
        exit();
    }

    // Check for related exam results before deletion
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_results WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $has_results = $stmt->fetchColumn() > 0;

    if ($has_results) {
        set_message('warning', 'This exam cannot be deleted because there are student results associated with it. You can view the exam details to see the results.');
        header('Location: view.php?id=' . $exam_id);
        exit();
    }

    // Delete the exam if no results exist
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);

    if ($stmt->rowCount() > 0) {
        set_message('success', 'Exam "' . htmlspecialchars($exam['title']) . '" has been deleted successfully.');
    } else {
        set_message('error', 'Failed to delete the exam.');
    }
} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
}

header('Location: list.php');
exit();
