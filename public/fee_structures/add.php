<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$fee_types = [];
$title = '';
$type_id = '';
$amount = '';
$applicable_class = '';
$applicable_year = '';

// Fetch fee types for dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM fee_types ORDER BY name");
    $fee_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching fee types: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: add.php');
        exit();
    }

    $title = trim($_POST['title']);
    $type_id = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $applicable_class = trim($_POST['applicable_class']);
    $applicable_year = filter_input(INPUT_POST, 'applicable_year', FILTER_VALIDATE_INT);

    // Validation
    if (empty($title)) { $errors[] = 'Title is required.'; }
    if (!$type_id) { $errors[] = 'Fee Type is required.'; }
    if ($amount === false || $amount <= 0) { $errors[] = 'Amount must be a positive number.'; }
    // applicable_class and applicable_year can be empty

    // Check if type_id exists
    if ($type_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_types WHERE id = ?");
        $stmt->execute([$type_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Selected Fee Type does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO fee_structures (title, type_id, amount, applicable_class, applicable_year) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $type_id, $amount, ($applicable_class ?: null), ($applicable_year ?: null)]);

            set_message('success', 'Fee Structure added successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error adding fee structure: ' . $e->getMessage());
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
                <h2>Add New Fee Structure</h2>
                <p class="text-muted">Create a new fee structure for students</p>
            </div>
            <div>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Fee Structure Details
            </div>
            <div class="card-body">
                <form action="add.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Structure Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($title); ?>" 
                                       placeholder="Enter fee structure title"
                                       required>
                                <div class="form-text">Give a descriptive name for this fee structure</div>
                            </div>

                            <div class="mb-3">
                                <label for="type_id" class="form-label">Fee Type</label>
                                <select class="form-select" id="type_id" name="type_id" required>
                                    <option value="">Select Fee Type</option>
                                    <?php foreach ($fee_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['id']); ?>" 
                                                <?php echo ($type_id == $type['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose the category this fee belongs to</div>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           value="<?php echo htmlspecialchars($amount); ?>" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                </div>
                                <div class="form-text">Enter the fee amount in dollars</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="applicable_class" class="form-label">Applicable Class</label>
                                <select class="form-select" id="applicable_class" name="applicable_class">
                                    <option value="">All Classes</option>
                                    <option value="Class 1" <?php echo ($applicable_class == 'Class 1') ? 'selected' : ''; ?>>Class 1</option>
                                    <option value="Class 2" <?php echo ($applicable_class == 'Class 2') ? 'selected' : ''; ?>>Class 2</option>
                                    <option value="Class 3" <?php echo ($applicable_class == 'Class 3') ? 'selected' : ''; ?>>Class 3</option>
                                </select>
                                <div class="form-text">Select a specific class or leave blank for all classes</div>
                            </div>

                            <div class="mb-3">
                                <label for="applicable_year" class="form-label">Applicable Year</label>
                                <select class="form-select" id="applicable_year" name="applicable_year">
                                    <option value="">All Years</option>
                                    <?php 
                                    $current_year = date('Y');
                                    for($i = 0; $i < 5; $i++): 
                                        $year = $current_year + $i;
                                    ?>
                                        <option value="<?php echo $year; ?>" 
                                                <?php echo ($applicable_year == $year) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div class="form-text">Select a specific year or leave blank for all years</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Fee Structure
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
