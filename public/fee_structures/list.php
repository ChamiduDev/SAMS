<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$fee_structures = [];
$edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);

// Handle Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: list.php');
        exit();
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $title = trim($_POST['title']);
    $type_id = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $applicable_class = trim($_POST['applicable_class']);
    $applicable_year = filter_input(INPUT_POST, 'applicable_year', FILTER_VALIDATE_INT);

    $errors = [];
    if (!$id) { $errors[] = 'Invalid Fee Structure ID.'; }
    if (empty($title)) { $errors[] = 'Title is required.'; }
    if (!$type_id) { $errors[] = 'Fee Type is required.'; }
    if ($amount === false || $amount <= 0) { $errors[] = 'Amount must be a positive number.'; }

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
            $stmt = $pdo->prepare("UPDATE fee_structures SET title = ?, type_id = ?, amount = ?, applicable_class = ?, applicable_year = ? WHERE id = ?");
            $stmt->execute([$title, $type_id, $amount, ($applicable_class ?: null), ($applicable_year ?: null), $id]);

            set_message('success', 'Fee Structure updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error updating fee structure: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            set_message('error', $error);
        }
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM fee_structures WHERE id = ?");
            $stmt->execute([$delete_id]);
            if ($stmt->rowCount() > 0) {
                set_message('success', 'Fee Structure deleted successfully.');
            } else {
                set_message('error', 'Fee Structure not found.');
            }
        } catch (PDOException $e) {
            set_message('error', 'Database error deleting fee structure: ' . $e->getMessage());
        }
    }
    header('Location: list.php');
    exit();
}

// Fetch all fee structures
try {
    $stmt = $pdo->query("SELECT fs.id, fs.title, ft.name AS type_name, fs.amount, fs.applicable_class, fs.applicable_year FROM fee_structures fs JOIN fee_types ft ON fs.type_id = ft.id ORDER BY fs.title");
    $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching fee structures: ' . $e->getMessage());
}

// Fetch fee types for dropdown (for edit form)
$fee_types = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM fee_types ORDER BY name");
    $fee_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching fee types for edit: ' . $e->getMessage());
}

$csrf_token = generate_csrf_token();
$message = get_message();

// Pre-fill form for editing
$edit_entry = null;
if ($edit_id) {
    foreach ($fee_structures as $structure) {
        if ($structure['id'] == $edit_id) {
            $edit_entry = $structure;
            break;
        }
    }
    if (!$edit_entry) {
        set_message('error', 'Entry for editing not found.');
        header('Location: list.php');
        exit();
    }
}

?>



    <div class="container-fluid page-container">
        <div class="page-header">
            <div>
                <h2>Fee Structures</h2>
                <p class="text-muted">Manage and organize fee structures for different classes and years</p>
            </div>
            <div>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Fee Structure
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="filters-section">
            <form id="filterForm" class="form-row">
                <div class="form-group">
                    <label for="filterClass" class="form-label">Filter by Class</label>
                    <select class="form-select" id="filterClass">
                        <option value="">All Classes</option>
                        <option value="Class 1">Class 1</option>
                        <option value="Class 2">Class 2</option>
                        <option value="Class 3">Class 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterYear" class="form-label">Filter by Year</label>
                    <select class="form-select" id="filterYear">
                        <option value="">All Years</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterType" class="form-label">Filter by Fee Type</label>
                    <select class="form-select" id="filterType">
                        <option value="">All Types</option>
                        <?php foreach ($fee_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['id']); ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($edit_entry): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-edit"></i> Edit Fee Structure</span>
                        <a href="list.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="list.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_entry['id']); ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($edit_entry['title']); ?>" placeholder="Enter fee structure title" required>
                                </div>

                                <div class="mb-3">
                                    <label for="type_id" class="form-label">Fee Type</label>
                                    <select class="form-select" id="type_id" name="type_id" required>
                                        <option value="">Select Fee Type</option>
                                        <?php foreach ($fee_types as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type['id']); ?>" <?php echo ($edit_entry['type_id'] == $type['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rs.</span>
                                        <input type="number" class="form-control" id="amount" name="amount" value="<?php echo htmlspecialchars($edit_entry['amount']); ?>" step="0.01" min="0" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="applicable_class" class="form-label">Applicable Class</label>
                                    <select class="form-select" id="applicable_class" name="applicable_class">
                                        <option value="">All Classes</option>
                                        <option value="Class 1" <?php echo ($edit_entry['applicable_class'] == 'Class 1') ? 'selected' : ''; ?>>Class 1</option>
                                        <option value="Class 2" <?php echo ($edit_entry['applicable_class'] == 'Class 2') ? 'selected' : ''; ?>>Class 2</option>
                                        <option value="Class 3" <?php echo ($edit_entry['applicable_class'] == 'Class 3') ? 'selected' : ''; ?>>Class 3</option>
                                    </select>
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
                                            <option value="<?php echo $year; ?>" <?php echo ($edit_entry['applicable_year'] == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Fee Structure
                            </button>
                            <a href="list.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($fee_structures)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h4>No Fee Structures</h4>
                    <p class="text-muted">No fee structures have been defined yet.</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Fee Structure
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="25%">Title</th>
                                <th width="20%">Fee Type</th>
                                <th width="15%">Amount</th>
                                <th width="15%">Class</th>
                                <th width="10%">Year</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fee_structures as $structure): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($structure['id']); ?></td>
                                    <td data-label="Title"><?php echo htmlspecialchars($structure['title']); ?></td>
                                    <td data-label="Fee Type">
                                        <span class="badge bg-info text-dark">
                                            <?php echo htmlspecialchars($structure['type_name']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Amount">
                                        <strong>Rs. <?php echo number_format($structure['amount'], 2); ?></strong>
                                    </td>
                                    <td data-label="Class">
                                        <?php if ($structure['applicable_class']): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($structure['applicable_class']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">All Classes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Year">
                                        <?php if ($structure['applicable_year']): ?>
                                            <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($structure['applicable_year']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">All Years</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="action-buttons">
                                            <a href="list.php?edit_id=<?php echo htmlspecialchars($structure['id']); ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="list.php?delete_id=<?php echo htmlspecialchars($structure['id']); ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this fee structure? This will also delete associated student fees and payments.');"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    
