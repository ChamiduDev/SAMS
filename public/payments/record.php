<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$student_fee_id = filter_input(INPUT_GET, 'student_fee_id', FILTER_VALIDATE_INT);

if (!$student_fee_id) {
    set_message('error', 'Invalid student fee ID.');
    header('Location: ../fees/list.php');
    exit();
}

$student_fee = null;
$errors = [];
$amount_paid = '';
$payment_date = date('Y-m-d');
$method = 'cash';
$reference = '';

// Fetch student fee details
try {
    $stmt = $pdo->prepare("SELECT sf.id, sf.total_amount, sf.outstanding_amount, sf.due_date, sf.status, s.first_name, s.last_name, fs.title AS structure_title, ft.name AS fee_type_name FROM student_fees sf JOIN students s ON sf.student_id = s.id JOIN fee_structures fs ON sf.structure_id = fs.id JOIN fee_types ft ON fs.type_id = ft.id WHERE sf.id = ?");
    $stmt->execute([$student_fee_id]);
    $student_fee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_fee) {
        set_message('error', 'Student fee record not found.');
        header('Location: ../fees/list.php');
        exit();
    }
} catch (PDOException $e) {
    set_message('error', 'Database error fetching student fee details: ' . $e->getMessage());
    header('Location: ../fees/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: record.php?student_fee_id=' . $student_fee_id);
        exit();
    }

    $amount_paid = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
    $payment_date = trim($_POST['payment_date']);
    $method = trim($_POST['method']);
    $reference = trim($_POST['reference']);

    // Validation
    if ($amount_paid === false || $amount_paid <= 0) { $errors[] = 'Amount Paid must be a positive number.'; }
    if ($amount_paid > $student_fee['outstanding_amount']) { $errors[] = 'Amount Paid cannot exceed outstanding amount.'; }
    if (empty($payment_date)) { $errors[] = 'Payment Date is required.'; } elseif (!isValidDate($payment_date)) { $errors[] = 'Invalid Payment Date format.'; }
    if (!in_array($method, ['cash', 'card', 'bank_transfer', 'online'])) { $errors[] = 'Invalid Payment Method selected.'; }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert payment record
            $stmt = $pdo->prepare("INSERT INTO fee_payments (student_fee_id, amount_paid, payment_date, method, reference, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_fee_id, $amount_paid, $payment_date, $method, ($reference ?: null), $_SESSION['user_id']]);

            // Update outstanding amount and status in student_fees
            $new_outstanding = $student_fee['outstanding_amount'] - $amount_paid;
            $new_status = 'partial';
            if ($new_outstanding <= 0) {
                $new_status = 'paid';
                $new_outstanding = 0; // Ensure it's not negative due to float precision
            }

            $stmt = $pdo->prepare("UPDATE student_fees SET outstanding_amount = ?, status = ? WHERE id = ?");
            $stmt->execute([$new_outstanding, $new_status, $student_fee_id]);

            $pdo->commit();
            set_message('success', 'Payment recorded successfully.');
            header('Location: ../fees/view.php?id=' . $student_fee_id);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_message('error', 'Database error recording payment: ' . $e->getMessage());
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



    <div class="container-fluid page-container">
        <div class="page-header">
            <div>
                <h2>Record Payment</h2>
                <p class="text-muted">Process a new payment for student fees</p>
            </div>
            <div>
                <a href="../fees/view.php?id=<?php echo htmlspecialchars($student_fee_id); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Fee Details
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <?php if ($student_fee): ?>
            <div class="row">
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-info-circle me-2"></i>Fee Details
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($student_fee['first_name'] . ' ' . $student_fee['last_name']); ?>
                                </h5>
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($student_fee['fee_type_name']); ?>
                                </span>
                            </div>

                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Fee Structure:</strong>
                                    <span><?php echo htmlspecialchars($student_fee['structure_title']); ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Total Amount:</strong>
                                    <span class="text-primary">$<?php echo number_format($student_fee['total_amount'], 2); ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Outstanding:</strong>
                                    <span class="text-danger">$<?php echo number_format($student_fee['outstanding_amount'], 2); ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Due Date:</strong>
                                    <span class="<?php echo strtotime($student_fee['due_date']) < time() ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo date('M d, Y', strtotime($student_fee['due_date'])); ?>
                                    </span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Status:</strong>
                                    <span class="status-pill status-<?php echo strtolower($student_fee['status']); ?>">
                                        <?php echo ucfirst($student_fee['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-money-bill-wave me-2"></i>Payment Information
                        </div>
                        <div class="card-body">
                            <form action="record.php?id=<?php echo htmlspecialchars($student_fee_id); ?>" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="amount_paid" class="form-label">Amount Paid</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="amount_paid" name="amount_paid" 
                                                       value="<?php echo htmlspecialchars($amount_paid); ?>" 
                                                       step="0.01" min="0" 
                                                       max="<?php echo htmlspecialchars($student_fee['outstanding_amount']); ?>" 
                                                       placeholder="0.00"
                                                       required>
                                            </div>
                                            <div class="form-text">Maximum amount: $<?php echo number_format($student_fee['outstanding_amount'], 2); ?></div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="payment_date" class="form-label">Payment Date</label>
                                            <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                                   value="<?php echo htmlspecialchars($payment_date); ?>" required>
                                            <div class="form-text">Select the date when the payment was made</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="method" class="form-label">Payment Method</label>
                                            <select class="form-select" id="method" name="method" required>
                                                <option value="">Select Payment Method</option>
                                                <option value="cash" <?php echo ($method == 'cash') ? 'selected' : ''; ?>>
                                                    <i class="fas fa-money-bill"></i> Cash
                                                </option>
                                                <option value="card" <?php echo ($method == 'card') ? 'selected' : ''; ?>>
                                                    <i class="fas fa-credit-card"></i> Card
                                                </option>
                                                <option value="bank_transfer" <?php echo ($method == 'bank_transfer') ? 'selected' : ''; ?>>
                                                    <i class="fas fa-university"></i> Bank Transfer
                                                </option>
                                                <option value="online" <?php echo ($method == 'online') ? 'selected' : ''; ?>>
                                                    <i class="fas fa-globe"></i> Online
                                                </option>
                                            </select>
                                            <div class="form-text">Choose how the payment was made</div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="reference" class="form-label">Reference Number</label>
                                            <input type="text" class="form-control" id="reference" name="reference" 
                                                   value="<?php echo htmlspecialchars($reference); ?>"
                                                   placeholder="Enter reference number if applicable">
                                            <div class="form-text">Optional: Add a reference number for tracking</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Record Payment
                                    </button>
                                    <a href="../fees/view.php?id=<?php echo htmlspecialchars($student_fee_id); ?>" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    
