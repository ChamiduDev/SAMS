<?php
require_once '../includes/student/header.php';
require_once '../../config/config.php';

$pdo = get_pdo_connection();
$fee_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    // Get student ID from the logged-in user
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_id = $stmt->fetchColumn();

    if (!$student_id) {
        set_message('error', "Student record not found.");
        header('Location: fees.php');
        exit();
    }

    // Check if the fee exists and belongs to the student
    $stmt = $pdo->prepare("SELECT id FROM student_fees WHERE id = ? AND student_id = ?");
    $stmt->execute([$fee_id, $student_id]);
    
    if (!$stmt->fetch()) {
        set_message('error', "Fee record not found or access denied.");
        header('Location: fees.php');
        exit();
    }

    // Check if there are any payments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE student_fee_id = ?");
    $stmt->execute([$fee_id]);
    $payment_count = $stmt->fetchColumn();

    if ($payment_count > 0) {
        set_message('error', "Cannot delete fee record that has payments associated with it.");
        header('Location: fees.php');
        exit();
    }

    // Delete the fee record
    $stmt = $pdo->prepare("DELETE FROM student_fees WHERE id = ? AND student_id = ?");
    $stmt->execute([$fee_id, $student_id]);

    set_message('success', "Fee record deleted successfully.");

} catch (PDOException $e) {
    error_log("Fee deletion failed: " . $e->getMessage());
    set_message('error', "A database error occurred. Please try again later.");
}

header('Location: fees.php');
exit();
