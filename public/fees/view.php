<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role_name'];

$student_fee_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$student_fee_id) {
    set_message('error', 'Invalid student fee ID.');
    header('Location: list.php');
    exit();
}

$student_fee = null;
$payments = [];

// Fetch student fee details
try {
    $sql = "SELECT sf.id, sf.total_amount, sf.outstanding_amount, sf.due_date, sf.status,
                    s.id AS student_id, s.first_name, s.last_name,
                    fs.title AS structure_title, ft.name AS fee_type_name
             FROM student_fees sf
             JOIN students s ON sf.student_id = s.id
             JOIN fee_structures fs ON sf.structure_id = fs.id
             JOIN fee_types ft ON fs.type_id = ft.id
             WHERE sf.id = ?";

    // Role-based access control
    if (has_role('student')) {
        $stmt_student_id = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt_student_id->execute([$user_id]);
        $current_student_id = $stmt_student_id->fetchColumn();
        if ($current_student_id) {
            $sql .= " AND sf.student_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_fee_id, $current_student_id]);
        } else {
            set_message('error', 'Access denied. You are not linked to a student record.');
            header('Location: list.php');
            exit();
        }
    } elseif (has_role('parent')) {
        $stmt_children_ids = $pdo->prepare("SELECT id FROM students WHERE parent_user_id = ?"); // Assuming parent_user_id
        $stmt_children_ids->execute([$user_id]);
        $children_ids = $stmt_children_ids->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($children_ids)) {
            $placeholders = implode(',', array_fill(0, count($children_ids), '?'));
            $sql .= " AND sf.student_id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $params = array_merge([$student_fee_id], $children_ids);
            $stmt->execute($params);
        } else {
            set_message('error', 'Access denied. You have no linked children.');
            header('Location: list.php');
            exit();
        }
    } else { // Admin or other roles
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_fee_id]);
    }

    $student_fee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_fee) {
        set_message('error', 'Student fee record not found or access denied.');
        header('Location: list.php');
        exit();
    }
} catch (PDOException $e) {
    set_message('error', 'Database error fetching student fee details: ' . $e->getMessage());
    header('Location: list.php');
    exit();
}

// Fetch payment history
try {
    $stmt = $pdo->prepare("SELECT fp.id, fp.amount_paid, fp.payment_date, fp.method, fp.reference, u.username AS created_by_username FROM fee_payments fp LEFT JOIN users u ON fp.created_by = u.id WHERE fp.student_fee_id = ? ORDER BY fp.payment_date DESC");
    $stmt->execute([$student_fee_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching payment history: ' . $e->getMessage());
}

$message = get_message();
?>



    <div class="container mt-4">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h2 class="mb-0">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Fee Details
                </h2>
            </div>
            <div class="col-auto">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Fees
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="avatar-circle bg-primary-subtle">
                                    <i class="fas fa-user-graduate text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1"><?php echo htmlspecialchars($student_fee['first_name'] . ' ' . $student_fee['last_name']); ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-file-alt me-2"></i>
                                    <?php echo htmlspecialchars($student_fee['structure_title']); ?> - 
                                    <?php echo htmlspecialchars($student_fee['fee_type_name']); ?>
                                </p>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                <span class="badge <?php 
                                    echo $student_fee['status'] === 'paid' ? 'bg-success' :
                                        ($student_fee['status'] === 'partial' ? 'bg-warning' :
                                        ($student_fee['status'] === 'overdue' ? 'bg-danger' : 'bg-secondary'));
                                ?> text-uppercase fs-6">
                                    <?php echo htmlspecialchars(ucfirst($student_fee['status'])); ?>
                                </span>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Total Amount</span>
                                        <h4 class="mb-0">Rs. <?php echo number_format($student_fee['total_amount'], 2); ?></h4>
                                    </div>
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Outstanding</span>
                                        <h4 class="mb-0 <?php echo $student_fee['outstanding_amount'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                            Rs. <?php echo number_format($student_fee['outstanding_amount'], 2); ?>
                                        </h4>
                                    </div>
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar <?php echo $student_fee['outstanding_amount'] > 0 ? 'bg-danger' : 'bg-success'; ?>" 
                                             style="width: <?php echo ($student_fee['outstanding_amount'] / $student_fee['total_amount']) * 100; ?>%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="border rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-muted d-block">Due Date</span>
                                            <h5 class="mb-0">
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                <?php 
                                                    $due_date = new DateTime($student_fee['due_date']);
                                                    echo $due_date->format('M d, Y');
                                                ?>
                                            </h5>
                                        </div>
                                        <?php if (has_role('admin')): ?>
                                            <div>
                                                <a href="edit.php?id=<?php echo htmlspecialchars($student_fee['id']); ?>" class="btn btn-outline-primary me-2">
                                                    <i class="fas fa-edit me-2"></i>Edit Details
                                                </a>
                                                <a href="../payments/record.php?student_fee_id=<?php echo htmlspecialchars($student_fee['id']); ?>" class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>Record Payment
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-history text-primary me-2"></i>
                            <h5 class="card-title mb-0">Payment History</h5>
                        </div>
                    </div>

                    <?php if (empty($payments)): ?>
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Payments Recorded</h5>
                            <p class="mb-0">No payments have been recorded for this fee yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Amount</th>
                                        <th class="border-0">Date</th>
                                        <th class="border-0">Method</th>
                                        <th class="border-0">Reference</th>
                                        <th class="border-0">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-medium">Rs. <?php echo number_format($payment['amount_paid'], 2); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-calendar text-muted me-2"></i>
                                                    <?php 
                                                        $payment_date = new DateTime($payment['payment_date']);
                                                        echo $payment_date->format('M d, Y'); 
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas <?php
                                                        echo $payment['method'] === 'cash' ? 'fa-money-bill' :
                                                            ($payment['method'] === 'card' ? 'fa-credit-card' :
                                                            ($payment['method'] === 'bank' ? 'fa-university' : 'fa-money-check'));
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($payment['method']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo htmlspecialchars($payment['reference']); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user text-muted me-2"></i>
                                                    <?php echo htmlspecialchars($payment['created_by_username'] ?? 'N/A'); ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title d-flex align-items-center mb-4">
                            <i class="fas fa-chart-pie text-primary me-2"></i>
                            Payment Overview
                        </h5>

                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <div class="progress rounded-circle" style="width: 120px; height: 120px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: <?php echo (($student_fee['total_amount'] - $student_fee['outstanding_amount']) / $student_fee['total_amount']) * 100; ?>%" 
                                         aria-valuenow="<?php echo (($student_fee['total_amount'] - $student_fee['outstanding_amount']) / $student_fee['total_amount']) * 100; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                    <h3 class="mb-0">
                                        <?php echo number_format((($student_fee['total_amount'] - $student_fee['outstanding_amount']) / $student_fee['total_amount']) * 100); ?>%
                                    </h3>
                                    <small class="text-muted">Paid</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Amount</span>
                            <span class="fw-bold">Rs. <?php echo number_format($student_fee['total_amount'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Amount Paid</span>
                            <span class="fw-bold text-success">
                                Rs. <?php echo number_format($student_fee['total_amount'] - $student_fee['outstanding_amount'], 2); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Outstanding</span>
                            <span class="fw-bold text-danger">Rs. <?php echo number_format($student_fee['outstanding_amount'], 2); ?></span>
                        </div>

                        <hr class="my-4">

                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center text-<?php echo strtotime($student_fee['due_date']) < time() ? 'danger' : 'success'; ?>">
                                <i class="fas <?php echo strtotime($student_fee['due_date']) < time() ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> me-2"></i>
                                <span>
                                    <?php
                                        $due_date = new DateTime($student_fee['due_date']);
                                        $now = new DateTime();
                                        $interval = $due_date->diff($now);
                                        if ($due_date < $now) {
                                            echo 'Overdue by ' . $interval->days . ' days';
                                        } else {
                                            echo $interval->days . ' days until due';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .avatar-circle {
            width: 48px;
            height: 48px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-circle i {
            font-size: 20px;
        }
        .progress.rounded-circle {
            transform: rotate(-90deg);
        }
    </style>

    
