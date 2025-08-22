<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';
require_once '../../config/config.php';

$pdo = get_pdo_connection();

try {
    // Get student ID from the logged-in user
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_id = $stmt->fetchColumn();

    if (!$student_id) {
        die("Student record not found.");
    }

    // Fetch student fees with detailed information
    $sql = "
        SELECT sf.*, fs.title as structure_title, ft.name as fee_type_name,
               (SELECT COALESCE(SUM(amount_paid), 0) FROM fee_payments WHERE student_fee_id = sf.id) as total_paid
        FROM student_fees sf
        JOIN fee_structures fs ON sf.structure_id = fs.id
        JOIN fee_types ft ON fs.type_id = ft.id
        WHERE sf.student_id = ?
        ORDER BY sf.due_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $total_fees = 0;
    $total_paid = 0;
    $total_outstanding = 0;
    foreach ($fees as $fee) {
        $total_fees += $fee['total_amount'];
        $total_paid += $fee['total_paid'];
        $total_outstanding += $fee['outstanding_amount'];
    }

    // Fetch recent payments
    $payment_sql = "
        SELECT 
            fp.*,
            sf.total_amount,
            fs.title as structure_title,
            ft.name as fee_type_name,
            sf.status,
            sf.outstanding_amount
        FROM fee_payments fp
        JOIN student_fees sf ON fp.student_fee_id = sf.id
        JOIN fee_structures fs ON sf.structure_id = fs.id
        JOIN fee_types ft ON fs.type_id = ft.id
        WHERE sf.student_id = ?
        ORDER BY fp.payment_date DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($payment_sql);
    $stmt->execute([$student_id]);
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Fee fetch failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4">
        <div class="row">
            <!-- Fee Summary -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Fee Summary</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted">Total Fees</h6>
                                    <h4 class="mb-0">Rs. <?php echo number_format($total_fees, 2); ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted">Total Paid</h6>
                                    <h4 class="mb-0 text-success">Rs. <?php echo number_format($total_paid, 2); ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted">Outstanding Balance</h6>
                                    <h4 class="mb-0 text-danger">Rs. <?php echo number_format($total_outstanding, 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Details -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Fee Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fee Type</th>
                                        <th>Due Date</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Outstanding</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fees as $fee): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($fee['fee_type_name']); ?>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($fee['structure_title']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                                        <td>Rs.<?php echo number_format($fee['total_amount'], 2); ?></td>
                                        <td>Rs.<?php echo number_format($fee['total_paid'], 2); ?></td>
                                        <td>Rs.<?php echo number_format($fee['outstanding_amount'], 2); ?></td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'bg-warning',
                                                'partial' => 'bg-info',
                                                'paid' => 'bg-success',
                                                'overdue' => 'bg-danger'
                                            ][$fee['status']];
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($fee['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Payments</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_payments): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_payments as $payment): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Rs.<?php echo number_format($payment['amount_paid'], 2); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($payment['fee_type_name']); ?> - 
                                                    <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-success rounded-pill">
                                                <?php echo ucfirst($payment['method']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No recent payments found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/student/footer.php'; ?>
