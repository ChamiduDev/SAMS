<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$student_fee_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$student_fee_id) {
    set_message('error', 'Invalid student fee ID.');
    header('Location: list.php');
    exit();
}

$student_fee = null;
$errors = [];

// Fetch student fee details
try {
    $stmt = $pdo->prepare("SELECT sf.id, sf.student_id, sf.structure_id, sf.total_amount, sf.outstanding_amount, sf.due_date, sf.status, s.first_name, s.last_name, fs.title AS structure_title, ft.name AS fee_type_name FROM student_fees sf JOIN students s ON sf.student_id = s.id JOIN fee_structures fs ON sf.structure_id = fs.id JOIN fee_types ft ON fs.type_id = ft.id WHERE sf.id = ?");
    $stmt->execute([$student_fee_id]);
    $student_fee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_fee) {
        set_message('error', 'Student fee record not found.');
        header('Location: list.php');
        exit();
    }
} catch (PDOException $e) {
    set_message('error', 'Database error fetching student fee details: ' . $e->getMessage());
    header('Location: list.php');
    exit();
}

// Populate form fields with existing data
$total_amount = $student_fee['total_amount'];
$outstanding_amount = $student_fee['outstanding_amount'];
$due_date = $student_fee['due_date'];
$status = $student_fee['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: edit.php?id=' . $student_fee_id);
        exit();
    }

    $total_amount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
    $outstanding_amount = filter_input(INPUT_POST, 'outstanding_amount', FILTER_VALIDATE_FLOAT);
    $due_date = trim($_POST['due_date']);
    $status = trim($_POST['status']);

    // Validation
    if ($total_amount === false || $total_amount <= 0) { $errors[] = 'Total Amount must be a positive number.'; }
    if ($outstanding_amount === false || $outstanding_amount < 0 || $outstanding_amount > $total_amount) { $errors[] = 'Outstanding Amount must be between 0 and Total Amount.'; }
    if (empty($due_date)) { $errors[] = 'Due Date is required.'; } elseif (!isValidDate($due_date)) { $errors[] = 'Invalid Due Date format.'; }
    if (!in_array($status, ['pending', 'partial', 'paid', 'overdue'])) { $errors[] = 'Invalid Status selected.'; }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE student_fees SET total_amount = ?, outstanding_amount = ?, due_date = ?, status = ? WHERE id = ?");
            $stmt->execute([$total_amount, $outstanding_amount, $due_date, $status, $student_fee_id]);

            set_message('success', 'Student fee updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error updating student fee: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            set_message('error', $error);
        }
    }
}

$csrf_token = generate_csrf_token();
$message = get_message();
?>



    <div class="container mt-4">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h2 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Edit Fee Details
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
                <div class="card shadow-sm border-0">
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
                        </div>

                        <form action="edit.php?id=<?php echo htmlspecialchars($student_fee_id); ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" 
                                               class="form-control form-control-lg" 
                                               id="total_amount" 
                                               name="total_amount" 
                                               value="<?php echo htmlspecialchars($total_amount); ?>" 
                                               step="0.01" 
                                               min="0" 
                                               required>
                                        <label for="total_amount">
                                            <i class="fas fa-dollar-sign me-2"></i>Total Amount
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" 
                                               class="form-control form-control-lg" 
                                               id="outstanding_amount" 
                                               name="outstanding_amount" 
                                               value="<?php echo htmlspecialchars($outstanding_amount); ?>" 
                                               step="0.01" 
                                               min="0" 
                                               required>
                                        <label for="outstanding_amount">
                                            <i class="fas fa-money-bill-wave me-2"></i>Outstanding Amount
                                        </label>
                                    </div>
                                    <div class="progress mt-2" style="height: 4px;">
                                        <div class="progress-bar" 
                                             role="progressbar" 
                                             style="width: <?php echo ($outstanding_amount / $total_amount) * 100; ?>%"
                                             aria-valuenow="<?php echo ($outstanding_amount / $total_amount) * 100; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" 
                                               class="form-control form-control-lg" 
                                               id="due_date" 
                                               name="due_date" 
                                               value="<?php echo htmlspecialchars($due_date); ?>" 
                                               required>
                                        <label for="due_date">
                                            <i class="fas fa-calendar-alt me-2"></i>Due Date
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select form-select-lg" id="status" name="status" required>
                                            <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="partial" <?php echo ($status == 'partial') ? 'selected' : ''; ?>>Partial</option>
                                            <option value="paid" <?php echo ($status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                            <option value="overdue" <?php echo ($status == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                                        </select>
                                        <label for="status">
                                            <i class="fas fa-info-circle me-2"></i>Status
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="list.php" class="btn btn-light">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Fee
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title d-flex align-items-center mb-4">
                            <i class="fas fa-chart-pie text-primary me-2"></i>
                            Fee Overview
                        </h5>

                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <div class="progress rounded-circle" style="width: 120px; height: 120px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: <?php echo (($total_amount - $outstanding_amount) / $total_amount) * 100; ?>%" 
                                         aria-valuenow="<?php echo (($total_amount - $outstanding_amount) / $total_amount) * 100; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                    <h3 class="mb-0">
                                        <?php echo number_format((($total_amount - $outstanding_amount) / $total_amount) * 100); ?>%
                                    </h3>
                                    <small class="text-muted">Paid</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Amount</span>
                            <span class="fw-bold" id="preview_total">Rs. <?php echo number_format($total_amount, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Amount Paid</span>
                            <span class="fw-bold text-success" id="preview_paid">
                                Rs. <?php echo number_format($total_amount - $outstanding_amount, 2); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Outstanding</span>
                            <span class="fw-bold text-danger" id="preview_outstanding">
                                Rs. <?php echo number_format($outstanding_amount, 2); ?>
                            </span>
                        </div>

                        <hr class="my-4">

                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <span class="badge bg-secondary text-uppercase fs-6">
                                    <i class="fas fa-circle me-2"></i>
                                    <span id="preview_status"><?php echo ucfirst($status); ?></span>
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

    
