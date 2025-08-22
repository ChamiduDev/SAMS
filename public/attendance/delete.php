<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$attendance_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$attendance_id) {
    $_SESSION['error_message'] = "Invalid attendance record ID.";
    header('Location: list.php');
    exit();
}

try {
    // Prepare and execute the delete statement
    $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
    $stmt->execute([$attendance_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Attendance record deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Attendance record not found or could not be deleted.";
    }
} catch (PDOException $e) {
    error_log("Error deleting attendance record: " . $e->getMessage());
    $_SESSION['error_message'] = "Error deleting attendance record: " . $e->getMessage();
}

header('Location: list.php');
exit();
?>
