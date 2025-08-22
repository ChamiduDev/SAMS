<?php
require_once '../includes/header.php';
$pdo = get_pdo_connection();

// Check permissions
$can_manage_fees = has_role('admin') || hasPermission('fees_edit');
$can_view_payments = hasPermission('payments_list');
$can_record_payments = hasPermission('payments_record');
$is_student = has_role('student');

// Get current user's student ID if they are a student
$student_id = null;
if ($is_student) {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_id = $stmt->fetchColumn();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage_fees) {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: index.php');
        exit();
    }

    switch ($action) {
        case 'add_fee_type':
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            try {
                $stmt = $pdo->prepare("INSERT INTO fee_types (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                set_message('success', 'Fee type added successfully.');
            } catch (PDOException $e) {
                set_message('error', 'Error adding fee type: ' . $e->getMessage());
            }
            break;

        case 'add_fee_structure':
            $title = trim($_POST['title']);
            $type_id = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
            $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
            $applicable_class = trim($_POST['applicable_class']);
            $applicable_year = filter_input(INPUT_POST, 'applicable_year', FILTER_VALIDATE_INT);

            try {
                $stmt = $pdo->prepare("INSERT INTO fee_structures (title, type_id, amount, applicable_class, applicable_year) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $type_id, $amount, $applicable_class ?: null, $applicable_year ?: null]);
                set_message('success', 'Fee structure added successfully.');
            } catch (PDOException $e) {
                set_message('error', 'Error adding fee structure: ' . $e->getMessage());
            }
            break;

        case 'assign_fee':
            $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
            $structure_id = filter_input(INPUT_POST, 'structure_id', FILTER_VALIDATE_INT);
            $due_date = trim($_POST['due_date']);

            try {
                // Get fee structure amount
                $stmt = $pdo->prepare("SELECT amount FROM fee_structures WHERE id = ?");
                $stmt->execute([$structure_id]);
                $amount = $stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO student_fees (student_id, structure_id, total_amount, outstanding_amount, due_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$student_id, $structure_id, $amount, $amount, $due_date]);
                set_message('success', 'Fee assigned successfully.');
            } catch (PDOException $e) {
                set_message('error', 'Error assigning fee: ' . $e->getMessage());
            }
            break;

        case 'record_payment':
            $student_fee_id = filter_input(INPUT_POST, 'student_fee_id', FILTER_VALIDATE_INT);
            $amount_paid = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
            $payment_date = trim($_POST['payment_date']);
            $method = trim($_POST['method']);
            $reference = trim($_POST['reference']);

            try {
                $pdo->beginTransaction();

                // Get current fee details
                $stmt = $pdo->prepare("SELECT outstanding_amount FROM student_fees WHERE id = ?");
                $stmt->execute([$student_fee_id]);
                $current_outstanding = $stmt->fetchColumn();

                if ($amount_paid > $current_outstanding) {
                    throw new Exception('Amount paid cannot exceed outstanding amount.');
                }

                // Record payment
                $stmt = $pdo->prepare("INSERT INTO fee_payments (student_fee_id, amount_paid, payment_date, method, reference, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$student_fee_id, $amount_paid, $payment_date, $method, $reference, $_SESSION['user_id']]);

                // Update student_fees
                $new_outstanding = $current_outstanding - $amount_paid;
                $new_status = $new_outstanding <= 0 ? 'paid' : 'partial';
                $stmt = $pdo->prepare("UPDATE student_fees SET outstanding_amount = ?, status = ? WHERE id = ?");
                $stmt->execute([$new_outstanding, $new_status, $student_fee_id]);

                $pdo->commit();
                set_message('success', 'Payment recorded successfully.');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_message('error', 'Error recording payment: ' . $e->getMessage());
            }
            break;
    }

    header('Location: index.php');
    exit();
}

// Fetch data for display
try {
    // Fee Types
    $stmt = $pdo->query("SELECT * FROM fee_types ORDER BY name");
    $fee_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fee Structures
    $stmt = $pdo->query("
        SELECT fs.*, ft.name as type_name 
        FROM fee_structures fs 
        JOIN fee_types ft ON fs.type_id = ft.id 
        ORDER BY fs.title
    ");
    $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get filter parameters
    $status = $_GET['status'] ?? '';
    $fee_type = $_GET['fee_type'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Student Fees with filters
    $params = [];
    $where_conditions = [];

    if ($is_student) {
        $where_conditions[] = "sf.student_id = ?";
        $params[] = $student_id;
    }

    if ($status) {
        $where_conditions[] = "sf.status = ?";
        $params[] = $status;
    }

    if ($fee_type) {
        $where_conditions[] = "ft.id = ?";
        $params[] = $fee_type;
    }

    if ($date_from) {
        $where_conditions[] = "sf.due_date >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $where_conditions[] = "sf.due_date <= ?";
        $params[] = $date_to;
    }

    $where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

    $sql = "SELECT sf.*, s.first_name, s.last_name, 
                   fs.title as structure_title, ft.name as fee_type_name,
                   (SELECT SUM(amount_paid) FROM fee_payments WHERE student_fee_id = sf.id) as total_paid
            FROM student_fees sf
            JOIN students s ON sf.student_id = s.id
            JOIN fee_structures fs ON sf.structure_id = fs.id
            JOIN fee_types ft ON fs.type_id = ft.id" . 
            $where_clause . " ORDER BY sf.due_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $student_fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Payments
    $payments_sql = "
        SELECT fp.*, sf.student_id,
               s.first_name, s.last_name,
               fs.title as structure_title,
               ft.name as fee_type_name,
               u.username as recorded_by
        FROM fee_payments fp
        JOIN student_fees sf ON fp.student_fee_id = sf.id
        JOIN students s ON sf.student_id = s.id
        JOIN fee_structures fs ON sf.structure_id = fs.id
        JOIN fee_types ft ON fs.type_id = ft.id
        LEFT JOIN users u ON fp.created_by = u.id
    ";

    if ($is_student) {
        $payments_sql .= " WHERE sf.student_id = ?";
        $stmt = $pdo->prepare($payments_sql . " ORDER BY fp.payment_date DESC LIMIT 10");
        $stmt->execute([$student_id]);
    } else {
        $stmt = $pdo->query($payments_sql . " ORDER BY fp.payment_date DESC LIMIT 10");
    }
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Students list for fee assignment
    if ($can_manage_fees) {
        $stmt = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
}

$message = get_message();
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4">
    <h1 class="mt-4">Fee Management</h1>
    
    <?php display_message($message); ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview">Overview</a>
        </li>
        <?php if ($can_manage_fees): ?>
        <li class="nav-item">
            <a class="nav-link" id="fee-types-tab" data-bs-toggle="tab" href="#fee-types">Fee Types</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="fee-structures-tab" data-bs-toggle="tab" href="#fee-structures">Fee Structures</a>
        </li>
        <?php endif; ?>
        <?php if ($can_view_payments): ?>
        <li class="nav-item">
            <a class="nav-link" id="payments-tab" data-bs-toggle="tab" href="#payments">Recent Payments</a>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-list me-2"></i>Overview
                            </div>
                            <div class="d-flex gap-2">
                                <a href="export_pdf.php<?php echo !empty($_GET) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="export_excel.php<?php echo !empty($_GET) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-file-excel me-1"></i>Export Excel
                                </a>
                                <?php if ($can_manage_fees): ?>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignFeeModal">
                                    <i class="fas fa-plus"></i> Assign Fee
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filter Form -->
                            <form method="get" class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <select name="status" class="form-select">
                                            <option value="">All Statuses</option>
                                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                            <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="fee_type" class="form-select">
                                            <option value="">All Fee Types</option>
                                            <?php foreach ($fee_types as $type): ?>
                                                <option value="<?php echo $type['id']; ?>" <?php echo $fee_type == $type['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="date_from" class="form-control" placeholder="From Date" value="<?php echo $date_from; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="date_to" class="form-control" placeholder="To Date" value="<?php echo $date_to; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter me-1"></i>Filter
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <?php if (!$is_student): ?>
                                            <th>Student</th>
                                            <?php endif; ?>
                                            <th>Fee Type</th>
                                            <th>Structure</th>
                                            <th>Total Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Outstanding</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($student_fees as $fee): ?>
                                        <tr>
                                            <?php if (!$is_student): ?>
                                            <td><?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo htmlspecialchars($fee['fee_type_name']); ?></td>
                                            <td><?php echo htmlspecialchars($fee['structure_title']); ?></td>
                                            <td>Rs. <?php echo number_format($fee['total_amount'], 2); ?></td>
                                            <td>Rs. <?php echo number_format($fee['total_paid'] ?? 0, 2); ?></td>
                                            <td>Rs. <?php echo number_format($fee['outstanding_amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo match($fee['status']) {
                                                        'paid' => 'success',
                                                        'partial' => 'warning',
                                                        'overdue' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($fee['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view.php?id=<?php echo $fee['id']; ?>" 
                                                       class="btn btn-info" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($can_manage_fees): ?>
                                                    <a href="edit.php?id=<?php echo $fee['id']; ?>" 
                                                       class="btn btn-primary"
                                                       title="Edit Fee">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-danger"
                                                            title="Delete Fee"
                                                            onclick="deleteFee(<?php echo $fee['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($can_record_payments && $fee['status'] != 'paid'): ?>
                                                    <button class="btn btn-success" 
                                                            onclick="preparePayment(<?php echo $fee['id']; ?>, <?php echo $fee['outstanding_amount']; ?>)"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#recordPaymentModal"
                                                            title="Record Payment">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($can_manage_fees): ?>
        <!-- Fee Types Tab -->
        <div class="tab-pane fade" id="fee-types">
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Add Fee Type</h5>
                        </div>
                        <div class="card-body">
                            <form action="index.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="action" value="add_fee_type">
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Add Fee Type</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Fee Types List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fee_types as $type): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($type['name']); ?></td>
                                            <td><?php echo htmlspecialchars($type['description']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($type['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fee Structures Tab -->
        <div class="tab-pane fade" id="fee-structures">
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Add Fee Structure</h5>
                        </div>
                        <div class="card-body">
                            <form action="index.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="action" value="add_fee_structure">
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="type_id" class="form-label">Fee Type</label>
                                    <select class="form-select" id="type_id" name="type_id" required>
                                        <option value="">Select Fee Type</option>
                                        <?php foreach ($fee_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>">
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount</label>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="applicable_class" class="form-label">Applicable Class</label>
                                    <input type="text" class="form-control" id="applicable_class" name="applicable_class">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="applicable_year" class="form-label">Applicable Year</label>
                                    <input type="number" class="form-control" id="applicable_year" name="applicable_year" min="2000">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Add Fee Structure</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Fee Structures List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Class</th>
                                            <th>Year</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fee_structures as $structure): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($structure['title']); ?></td>
                                            <td><?php echo htmlspecialchars($structure['type_name']); ?></td>
                                            <td>Rs. <?php echo number_format($structure['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($structure['applicable_class'] ?? 'Any'); ?></td>
                                            <td><?php echo htmlspecialchars($structure['applicable_year'] ?? 'Any'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($can_view_payments): ?>
        <!-- Payments Tab -->
        <div class="tab-pane fade" id="payments">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Payments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <?php if (!$is_student): ?>
                                    <th>Student</th>
                                    <?php endif; ?>
                                    <th>Fee Type</th>
                                    <th>Structure</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Date</th>
                                    <?php if (!$is_student): ?>
                                    <th>Recorded By</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                <tr>
                                    <?php if (!$is_student): ?>
                                    <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($payment['fee_type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['structure_title']); ?></td>
                                    <td>Rs. <?php echo number_format($payment['amount_paid'], 2); ?></td>
                                    <td><?php echo ucfirst($payment['method']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['reference'] ?? '-'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                    <?php if (!$is_student): ?>
                                    <td><?php echo htmlspecialchars($payment['recorded_by']); ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($can_manage_fees): ?>
    <!-- Assign Fee Modal -->
    <div class="modal fade" id="assignFeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Fee to Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="index.php" method="post" id="assignFeeForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="assign_fee">
                        
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="structure_id" class="form-label">Fee Structure</label>
                            <select class="form-select" id="structure_id" name="structure_id" required>
                                <option value="">Select Fee Structure</option>
                                <?php foreach ($fee_structures as $structure): ?>
                                <option value="<?php echo $structure['id']; ?>">
                                    <?php echo htmlspecialchars($structure['title'] . ' (' . $structure['type_name'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="assignFeeForm" class="btn btn-primary">Assign Fee</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($can_record_payments): ?>
    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="index.php" method="post" id="recordPaymentForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="record_payment">
                        <input type="hidden" name="student_fee_id" id="payment_student_fee_id">
                        
                        <div class="mb-3">
                            <label for="amount_paid" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="amount_paid" name="amount_paid" step="0.01" min="0" required>
                            <div class="form-text">Maximum amount: $<span id="max_payment_amount">0.00</span></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="method" class="form-label">Payment Method</label>
                            <select class="form-select" id="method" name="method" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reference" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference" name="reference">
                            <div class="form-text">Optional: Transaction ID, receipt number, etc.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="recordPaymentForm" class="btn btn-primary">Record Payment</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteFee(feeId) {
    if (confirm('Are you sure you want to cancel/void this fee record? If payments exist, it will be marked as void; otherwise, it will be cancelled. This action cannot be undone.')) {
        window.location.href = 'delete.php?id=' + feeId;
    }
}
</script>
<script>
function preparePayment(feeId, maxAmount) {
    document.getElementById('payment_student_fee_id').value = feeId;
    document.getElementById('max_payment_amount').textContent = maxAmount.toFixed(2);
    document.getElementById('amount_paid').max = maxAmount;
    document.getElementById('amount_paid').value = maxAmount;
}
</script>

</body>
</html>
