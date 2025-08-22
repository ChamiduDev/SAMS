<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$students = [];
$fee_structures = [];
$selected_student_id = '';
$selected_structure_id = '';
$total_amount = '';
$due_date = '';

// Fetch students for dropdown
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching students: ' . $e->getMessage());
}

// Fetch fee structures for dropdown
try {
    $stmt = $pdo->query("SELECT fs.id, fs.title, ft.name AS type_name, fs.amount FROM fee_structures fs JOIN fee_types ft ON fs.type_id = ft.id ORDER BY fs.title");
    $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching fee structures: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: assign.php');
        exit();
    }

    $selected_student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $selected_structure_id = filter_input(INPUT_POST, 'structure_id', FILTER_VALIDATE_INT);
    $total_amount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
    $due_date = trim($_POST['due_date']);

    // Validation
    if (!$selected_student_id) { $errors[] = 'Student is required.'; }
    if (!$selected_structure_id) { $errors[] = 'Fee Structure is required.'; }
    if ($total_amount === false || $total_amount <= 0) { $errors[] = 'Total Amount must be a positive number.'; }
    if (empty($due_date)) { $errors[] = 'Due Date is required.'; } elseif (!isValidDate($due_date)) { $errors[] = 'Invalid Due Date format.'; }

    // Check if student_id and structure_id exist
    if ($selected_student_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE id = ?");
        $stmt->execute([$selected_student_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Selected Student does not exist.';
        }
    }
    if ($selected_structure_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_structures WHERE id = ?");
        $stmt->execute([$selected_structure_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Selected Fee Structure does not exist.';
        }
    }

    // Check for duplicate assignment (same student, same structure)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_fees WHERE student_id = ? AND structure_id = ?");
        $stmt->execute([$selected_student_id, $selected_structure_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'This fee structure has already been assigned to this student.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO student_fees (student_id, structure_id, total_amount, outstanding_amount, due_date, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$selected_student_id, $selected_structure_id, $total_amount, $total_amount, $due_date, 'pending']);

            set_message('success', 'Fee assigned successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error assigning fee: ' . $e->getMessage());
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
                    <i class="fas fa-file-invoice-dollar me-2"></i>Assign New Fee
                </h2>
            </div>
            <div class="col-auto">
                <a href="list.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Fees
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form action="assign.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select form-select-lg" id="student_id" name="student_id" required>
                                            <option value="">Select Student</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo ($selected_student_id == $student['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="student_id">
                                            <i class="fas fa-user-graduate me-2"></i>Student
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select form-select-lg" id="structure_id" name="structure_id" required>
                                            <option value="">Select Fee Structure</option>
                                            <?php foreach ($fee_structures as $structure): ?>
                                                <option value="<?php echo htmlspecialchars($structure['id']); ?>" 
                                                        data-amount="<?php echo htmlspecialchars($structure['amount']); ?>"
                                                        <?php echo ($selected_structure_id == $structure['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($structure['title']) . ' (' . htmlspecialchars($structure['type_name']) . ' - Rs. ' . number_format($structure['amount'], 2) . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="structure_id">
                                            <i class="fas fa-file-invoice me-2"></i>Fee Structure
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control form-control-lg" id="total_amount" name="total_amount" value="<?php echo htmlspecialchars($total_amount); ?>" step="0.01" min="0" required>
                                        <label for="total_amount">
                                            <i class="fas fa-dollar-sign me-2"></i>Total Amount Due
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control form-control-lg" id="due_date" name="due_date" value="<?php echo htmlspecialchars($due_date); ?>" required>
                                        <label for="due_date">
                                            <i class="fas fa-calendar-alt me-2"></i>Due Date
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
                                            <i class="fas fa-save me-2"></i>Assign Fee
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-4" id="previewCard" style="display: none;">
                    <div class="card-body p-4">
                        <h5 class="card-title">
                            <i class="fas fa-receipt me-2"></i>Fee Preview
                        </h5>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Student</p>
                                <p class="fw-bold" id="previewStudent">-</p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Fee Structure</p>
                                <p class="fw-bold" id="previewStructure">-</p>
                            </div>
                            <div class="col-md-6 mt-3">
                                <p class="text-muted mb-1">Amount Due</p>
                                <p class="fw-bold" id="previewAmount">-</p>
                            </div>
                            <div class="col-md-6 mt-3">
                                <p class="text-muted mb-1">Due Date</p>
                                <p class="fw-bold" id="previewDate">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
