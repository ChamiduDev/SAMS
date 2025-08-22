<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';
require_once '../../config/config.php';

$pdo = get_pdo_connection();
$fee_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = '';

try {
    // Get student ID from the logged-in user
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_id = $stmt->fetchColumn();

    if (!$student_id) {
        die("Student record not found.");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($csrf_token)) {
            die("Invalid CSRF token.");
        }

        $due_date = trim($_POST['due_date']);
        $total_amount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);

        // Validate input
        $errors = [];
        if (empty($due_date) || !strtotime($due_date)) {
            $errors[] = "Invalid due date.";
        }
        if ($total_amount <= 0) {
            $errors[] = "Total amount must be greater than zero.";
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE student_fees 
                    SET due_date = ?, total_amount = ?, outstanding_amount = total_amount - COALESCE((
                        SELECT SUM(amount_paid) FROM fee_payments WHERE student_fee_id = student_fees.id
                    ), 0)
                    WHERE id = ? AND student_id = ?
                ");
                $stmt->execute([$due_date, $total_amount, $fee_id, $student_id]);
                
                $message = "Fee record updated successfully.";
                header("Location: view.php?id=" . $fee_id);
                exit();
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }

    // Fetch current fee details
    $sql = "
        SELECT sf.*, fs.title as structure_title, ft.name as fee_type_name,
               (SELECT COALESCE(SUM(amount_paid), 0) FROM fee_payments WHERE student_fee_id = sf.id) as total_paid
        FROM student_fees sf
        JOIN fee_structures fs ON sf.structure_id = fs.id
        JOIN fee_types ft ON fs.type_id = ft.id
        WHERE sf.id = ? AND sf.student_id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fee_id, $student_id]);
    $fee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fee) {
        die("Fee record not found or access denied.");
    }

} catch (PDOException $e) {
    error_log("Fee fetch failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

$csrf_token = generate_csrf_token();
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4">
        <div class="row">
            <div class="col-12">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h3 mb-0">Edit Fee</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="fees.php">Fees</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Fee</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <a href="view.php?id=<?php echo $fee_id; ?>" class="btn btn-secondary me-2">
                            <i class="fas fa-eye me-2"></i>View Details
                        </a>
                        <a href="fees.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Fees
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Edit Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Edit Fee Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="edit.php?id=<?php echo $fee_id; ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fee Type</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($fee['fee_type_name']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Structure</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($fee['structure_title']); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="due_date" class="form-label">Due Date</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date"
                                               value="<?php echo date('Y-m-d', strtotime($fee['due_date'])); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="total_amount" class="form-label">Total Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="total_amount" name="total_amount"
                                                   value="<?php echo $fee['total_amount']; ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Amount Paid</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo number_format($fee['total_paid'], 2); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Outstanding Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo number_format($fee['outstanding_amount'], 2); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="history.back()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/student/footer.php'; ?>
