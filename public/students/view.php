<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$student_id_param = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$student = null;

if ($student_id_param > 0) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND status = 'active'");
    $stmt->execute([$student_id_param]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        set_message('error', 'Student not found or is deleted.');
        header('Location: list.php');
        exit();
    }
} else {
    set_message('error', 'Invalid student ID.');
    header('Location: list.php');
    exit();
}

$message = get_message();
?>



        <div class="row">
            <!-- Basic Information -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Student ID</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Full Name</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Date of Birth</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($student['dob'] ?? ''); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Gender</span>
                                <span class="badge bg-<?php echo $student['gender'] === 'M' ? 'primary-subtle text-primary' : 'info-subtle text-info'; ?>">
                                    <?php echo htmlspecialchars($student['gender'] === 'M' ? 'Male' : ($student['gender'] === 'F' ? 'Female' : 'Other')); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Class</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($student['class'] ?? ''); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Year</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($student['year'] ?? ''); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Created At</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($student['created_at'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Address</span>
                                <span class="fw-semibold text-end"><?php echo htmlspecialchars($student['address'] ?? ''); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Contact No</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($student['contact_no']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linked Data -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-link me-2"></i>Linked Data</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Attendance Records</span>
                                    <a href="#" class="btn btn-sm btn-primary-subtle text-primary">View</a>
                                </div>
                            </div>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Grades</span>
                                    <a href="#" class="btn btn-sm btn-primary-subtle text-primary">View</a>
                                </div>
                            </div>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Fee Information</span>
                                    <a href="#" class="btn btn-sm btn-primary-subtle text-primary">View</a>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>These sections are placeholders and require additional implementation.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="list.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                    <div class="d-flex gap-2">
                        <a href="edit.php?id=<?php echo htmlspecialchars($student['id']); ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Edit Student
                        </a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                data-student-id="<?php echo htmlspecialchars($student['id']); ?>" 
                                data-student-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
                            <i class="fas fa-trash me-2"></i>Delete Student
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal (re-used from list.php) -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete student <strong id="modalStudentName"></strong> (ID: <span id="modalStudentId"></span>)? This action will mark the student as deleted.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteForm" action="delete.php" method="POST" style="display: inline;">
                            <input type="hidden" name="student_id" id="deleteStudentId">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    