<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$fee_types = [];
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
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    $errors = [];
    if (!$id) { $errors[] = 'Invalid Fee Type ID.'; }
    if (empty($name)) { $errors[] = 'Fee Type Name is required.'; }

    // Check for duplicate name (excluding current record)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_types WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Fee Type Name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE fee_types SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);

            set_message('success', 'Fee Type updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error updating fee type: ' . $e->getMessage());
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
            $stmt = $pdo->prepare("DELETE FROM fee_types WHERE id = ?");
            $stmt->execute([$delete_id]);
            if ($stmt->rowCount() > 0) {
                set_message('success', 'Fee Type deleted successfully.');
            } else {
                set_message('error', 'Fee Type not found.');
            }
        } catch (PDOException $e) {
            set_message('error', 'Database error deleting fee type: ' . $e->getMessage());
        }
    }
    header('Location: list.php');
    exit();
}

// Fetch all fee types
try {
    $stmt = $pdo->query("SELECT id, name, description FROM fee_types ORDER BY name");
    $fee_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching fee types: ' . $e->getMessage());
}

$csrf_token = generate_csrf_token();
$message = get_message();

// Pre-fill form for editing
$edit_entry = null;
if ($edit_id) {
    foreach ($fee_types as $type) {
        if ($type['id'] == $edit_id) {
            $edit_entry = $type;
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
                <h2>Fee Types</h2>
                <p class="text-muted">Manage different categories of fees in the system</p>
            </div>
            <div>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Fee Type
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="filters-section">
            <div class="form-row">
                <div class="form-group">
                    <label for="searchFeeType" class="form-label">Search Fee Types</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="searchFeeType" placeholder="Search by name or description...">
                    </div>
                </div>
            </div>
        </div>

        <?php if ($edit_entry): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-edit"></i> Edit Fee Type</span>
                        <a href="list.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="list.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_entry['id']); ?>">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-4">
                                    <label for="name" class="form-label">Fee Type Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($edit_entry['name']); ?>" 
                                           placeholder="Enter fee type name"
                                           required>
                                    <div class="form-text">
                                        Choose a unique and descriptive name for this fee type
                                    </div>
                                    <div class="invalid-feedback">
                                        Please enter a fee type name
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-4">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="4" placeholder="Enter a detailed description"><?php echo htmlspecialchars($edit_entry['description']); ?></textarea>
                                    <div class="form-text">
                                        Provide additional details about this fee type to help users understand its purpose
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Fee Type
                            </button>
                            <a href="list.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($fee_types)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                    <h4>No Fee Types</h4>
                    <p class="text-muted">No fee types have been defined yet.</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Fee Type
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
                                <th width="25%">Name</th>
                                <th width="50%">Description</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fee_types as $type): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($type['id']); ?></td>
                                    <td data-label="Name">
                                        <span class="fw-bold"><?php echo htmlspecialchars($type['name']); ?></span>
                                    </td>
                                    <td data-label="Description">
                                        <?php if ($type['description']): ?>
                                            <?php echo htmlspecialchars($type['description']); ?>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">No description provided</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="action-buttons">
                                            <a href="list.php?edit_id=<?php echo htmlspecialchars($type['id']); ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Edit Fee Type">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="list.php?delete_id=<?php echo htmlspecialchars($type['id']); ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this fee type? This will also delete associated fee structures and student fees.');"
                                               title="Delete Fee Type">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailsModal<?php echo $type['id']; ?>"
                                                    title="View Details">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </div>

                                        <!-- Details Modal -->
                                        <div class="modal fade" id="detailsModal<?php echo $type['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            Fee Type Details
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Name:</label>
                                                            <p><?php echo htmlspecialchars($type['name']); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Description:</label>
                                                            <p><?php echo $type['description'] ? htmlspecialchars($type['description']) : '<span class="text-muted">No description provided</span>'; ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Associated Fee Structures:</label>
                                                            <?php
                                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_structures WHERE type_id = ?");
                                                            $stmt->execute([$type['id']]);
                                                            $structureCount = $stmt->fetchColumn();
                                                            ?>
                                                            <p><?php echo $structureCount; ?> structure(s)</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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

    
