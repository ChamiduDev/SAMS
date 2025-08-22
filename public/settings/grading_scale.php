<?php
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Include header
include_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$grading_scales = [];
$edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);

// Fetch all grading scale entries
try {
    $stmt = $pdo->query("SELECT id, min_percent, max_percent, grade_label, grade_point FROM grading_scale ORDER BY min_percent DESC");
    $grading_scales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching grading scale: ' . $e->getMessage());
}

// Handle Add/Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: grading_scale.php');
        exit();
    }

    $min_percent = filter_input(INPUT_POST, 'min_percent', FILTER_VALIDATE_FLOAT);
    $max_percent = filter_input(INPUT_POST, 'max_percent', FILTER_VALIDATE_FLOAT);
    $grade_label = trim($_POST['grade_label']);
    $grade_point = filter_input(INPUT_POST, 'grade_point', FILTER_VALIDATE_FLOAT);
    $form_action = $_POST['form_action'] ?? 'add';
    $record_id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);

    // Validation
    if ($min_percent === false || $min_percent < 0 || $min_percent > 100) { $errors[] = 'Min Percent must be between 0 and 100.'; }
    if ($max_percent === false || $max_percent < 0 || $max_percent > 100) { $errors[] = 'Max Percent must be between 0 and 100.'; }
    if ($min_percent >= $max_percent) { $errors[] = 'Min Percent must be less than Max Percent.'; }
    if (empty($grade_label)) { $errors[] = 'Grade Label is required.'; }
    // Grade Point can be null, so no empty check

    // Check for overlapping ranges (simplified check)
    if (empty($errors)) {
        foreach ($grading_scales as $scale) {
            if (($form_action === 'edit' && $scale['id'] == $record_id)) {
                continue; // Skip current record during edit
            }
            // Check for overlap
            if (!(($max_percent <= $scale['min_percent']) || ($min_percent >= $scale['max_percent']))) {
                $errors[] = 'Grading scale range overlaps with existing entry: ' . $scale['grade_label'] . ' (' . $scale['min_percent'] . '-' . $scale['max_percent'] . ').';
                break;
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($form_action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO grading_scale (min_percent, max_percent, grade_label, grade_point) VALUES (?, ?, ?, ?)");
                $stmt->execute([$min_percent, $max_percent, $grade_label, $grade_point]);
                set_message('success', 'Grading scale entry added successfully.');
            } elseif ($form_action === 'edit' && $record_id) {
                $stmt = $pdo->prepare("UPDATE grading_scale SET min_percent = ?, max_percent = ?, grade_label = ?, grade_point = ? WHERE id = ?");
                $stmt->execute([$min_percent, $max_percent, $grade_label, $grade_point, $record_id]);
                set_message('success', 'Grading scale entry updated successfully.');
            }
            header('Location: grading_scale.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error: ' . $e->getMessage());
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
            $stmt = $pdo->prepare("DELETE FROM grading_scale WHERE id = ?");
            $stmt->execute([$delete_id]);
            if ($stmt->rowCount() > 0) {
                set_message('success', 'Grading scale entry deleted successfully.');
            } else {
                set_message('error', 'Grading scale entry not found.');
            }
        } catch (PDOException $e) {
            set_message('error', 'Database error deleting entry: ' . $e->getMessage());
        }
    }
    header('Location: grading_scale.php');
    exit();
}

$csrf_token = generate_csrf_token();
$message = get_message();

// Pre-fill form for editing
$edit_entry = null;
if ($edit_id) {
    foreach ($grading_scales as $scale) {
        if ($scale['id'] == $edit_id) {
            $edit_entry = $scale;
            break;
        }
    }
    if (!$edit_entry) {
        set_message('error', 'Entry for editing not found.');
        header('Location: grading_scale.php');
        exit();
    }
}

?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-chart-line text-primary me-2"></i>Grading Scale Settings
        </h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'danger'; ?> d-flex align-items-center">
            <i class="fas fa-<?php echo strpos($message, 'success') !== false ? 'check' : 'exclamation'; ?>-circle me-2"></i>
            <?php display_message($message); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Card -->
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-<?php echo $edit_entry ? 'edit' : 'plus'; ?> me-2 text-primary"></i>
                        <?php echo $edit_entry ? 'Edit Grading Entry' : 'Add New Grading Entry'; ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="grading_scale.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="form_action" value="<?php echo $edit_entry ? 'edit' : 'add'; ?>">
                        <?php if ($edit_entry): ?>
                            <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($edit_entry['id']); ?>">
                        <?php endif; ?>

                        <div class="row g-4">
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="min_percent" class="form-label text-muted fw-bold">
                                        <i class="fas fa-percentage me-2"></i>Minimum Percentage
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control form-control-lg" id="min_percent" 
                                               name="min_percent" value="<?php echo htmlspecialchars($edit_entry['min_percent'] ?? ''); ?>" 
                                               step="0.01" min="0" max="100" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Minimum percentage for this grade</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="max_percent" class="form-label text-muted fw-bold">
                                        <i class="fas fa-percentage me-2"></i>Maximum Percentage
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control form-control-lg" id="max_percent" 
                                               name="max_percent" value="<?php echo htmlspecialchars($edit_entry['max_percent'] ?? ''); ?>" 
                                               step="0.01" min="0" max="100" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Maximum percentage for this grade</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="grade_label" class="form-label text-muted fw-bold">
                                        <i class="fas fa-tag me-2"></i>Grade Label
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="grade_label" 
                                           name="grade_label" value="<?php echo htmlspecialchars($edit_entry['grade_label'] ?? ''); ?>" 
                                           required maxlength="10" placeholder="e.g., A+, B">
                                    <div class="form-text">Letter grade or symbol</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="grade_point" class="form-label text-muted fw-bold">
                                        <i class="fas fa-star me-2"></i>Grade Point
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="grade_point" 
                                           name="grade_point" value="<?php echo htmlspecialchars($edit_entry['grade_point'] ?? ''); ?>" 
                                           step="0.01" min="0" placeholder="e.g., 4.0">
                                    <div class="form-text">Numerical equivalent (optional)</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <?php if ($edit_entry): ?>
                                <a href="grading_scale.php" class="btn btn-light btn-lg px-4">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg px-4">
                                    <i class="fas fa-save me-2"></i>Update Entry
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary btn-lg px-4">
                                    <i class="fas fa-plus-circle me-2"></i>Add Entry
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Grading Scale Table -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-list text-primary me-2"></i>Current Grading Scale
                    </h5>
                </div>
                
                <?php if (empty($grading_scales)): ?>
                    <div class="card-body p-4">
                        <div class="alert alert-info d-flex align-items-center mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            No grading scale entries defined yet.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="fw-semibold">Range</th>
                                    <th class="fw-semibold">Grade</th>
                                    <th class="fw-semibold">Points</th>
                                    <th class="fw-semibold text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grading_scales as $scale): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo htmlspecialchars($scale['max_percent']); ?>%"></div>
                                                </div>
                                                <span class="ms-2">
                                                    <?php echo htmlspecialchars($scale['min_percent']); ?>% - <?php echo htmlspecialchars($scale['max_percent']); ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <?php echo htmlspecialchars($scale['grade_label']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($scale['grade_point']): ?>
                                                <i class="fas fa-star text-warning me-1"></i>
                                                <?php echo htmlspecialchars($scale['grade_point']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="grading_scale.php?edit_id=<?php echo htmlspecialchars($scale['id']); ?>" 
                                                   class="btn btn-sm btn-warning-subtle text-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger-subtle text-danger"
                                                        onclick="confirmDelete(<?php echo htmlspecialchars($scale['id']); ?>, '<?php echo htmlspecialchars($scale['grade_label']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title">Delete Grade Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="avatar-initial rounded-circle bg-danger-subtle text-danger mx-auto mb-3" 
                     style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-trash-alt fa-2x"></i>
                </div>
                <h5 class="mb-2">Confirm Deletion</h5>
                <p class="text-muted mb-0">Are you sure you want to delete grade <strong id="gradeLabel"></strong>?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <a href="#" id="confirmDeleteButton" class="btn btn-danger px-4">
                    <i class="fas fa-trash-alt me-2"></i>Delete Grade
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, gradeLabel) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('gradeLabel').textContent = gradeLabel;
    document.getElementById('confirmDeleteButton').href = `grading_scale.php?delete_id=${id}`;
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>
