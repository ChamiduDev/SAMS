<?php
require_once '../includes/header.php';

// Check if user has permission to manage fees
if (!has_role('admin') && !hasPermission('fees_edit')) {
    set_message('error', 'You do not have permission to delete fees.');
    header('Location: index.php');
    exit();
}

// Validate the fee ID
$fee_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$fee_id) {
    set_message('error', 'Invalid fee ID.');
    header('Location: index.php');
    exit();
}

$pdo = get_pdo_connection();

try {
    // First check if the fee exists and get its details
    $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE id = ?");
    $stmt->execute([$fee_id]);
    $fee = $stmt->fetch();

    if (!$fee) {
        set_message('error', 'Fee record not found.');
        header('Location: index.php');
        exit();
    }

    // Check if there are any payments associated with this fee
    $stmt = $pdo->prepare("SELECT COUNT(*) as payment_count, SUM(amount_paid) as total_paid 
                          FROM fee_payments WHERE student_fee_id = ?");
    $stmt->execute([$fee_id]);
    $payment_info = $stmt->fetch();
    $has_payments = $payment_info['payment_count'] > 0;

    // Begin transaction
    $pdo->beginTransaction();

    if ($has_payments) {
        // Cannot delete fees that have payments to maintain payment records
        $pdo->rollBack();
        set_message('error', 'Cannot delete this fee as it has existing payments.');
        header('Location: index.php');
        exit();
    }

    // Delete the fee record
    $stmt = $pdo->prepare("DELETE FROM student_fees WHERE id = ?");
    $stmt->execute([$fee_id]);

    // Commit transaction
    $pdo->commit();
    
    set_message('success', 'Fee has been deleted successfully.');

    $status_message = $payment_count > 0 
        ? 'Fee record has been marked as void (payments exist).' 
        : 'Fee record has been cancelled.';
    set_message('success', $status_message);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_message('error', 'Error deleting fee record: ' . $e->getMessage());
}

// Redirect back to the fee list
header('Location: index.php');
exit();
?>
