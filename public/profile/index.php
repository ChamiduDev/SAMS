<?php
require_once '../includes/header.php';
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = get_pdo_connection();

try {
    // Fetch user information
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        set_message('error', 'User not found.');
        header('Location: dashboard.php');
        exit();
    }

    // For teachers, get their assigned subjects
    $subjects = [];
    if ($user['role_name'] === 'teacher') {
        $stmt = $pdo->prepare("
            SELECT s.*, c.name as course_name 
            FROM subjects s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE s.teacher_id = ?
        ");
        $stmt->execute([$user_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
    header('Location: dashboard.php');
    exit();
}

$message = get_message();
?>

<style>
.profile-container {
    min-height: calc(100vh - 120px);
    display: flex;
    align-items: center;
    padding: 2rem 0;
}

.profile-content {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.card {
    border: none;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.card-body {
    padding: 2rem 1.5rem;
}

.card-footer {
    background-color: transparent;
    border-top: 1px solid #dee2e6;
    padding: 1.5rem;
}

.avatar-initial {
    width: 120px;
    height: 120px;
    font-size: 3rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    margin-bottom: 1.5rem;
}

.profile-name {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #212529;
}

.profile-info {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.95rem;
}

.info-value {
    color: #495057;
    font-weight: 500;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.625rem 1.25rem;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.btn:last-child {
    margin-right: 0;
}

.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
    padding: 1rem 1.25rem;
}

.table td {
    padding: 1rem 1.25rem;
    vertical-align: middle;
}

.table-responsive {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.badge {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
}

.modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 12px 12px 0 0;
    padding: 1.5rem 2rem;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1.5rem 2rem;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem 1rem;
    font-size: 1rem;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.breadcrumb {
    background-color: transparent;
    padding: 0;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 1rem;
}

.empty-subjects {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .profile-container {
        padding: 1rem 0;
    }
    
    .card-body {
        padding: 1.5rem 1rem;
    }
    
    .btn {
        width: 100%;
        margin-right: 0;
        margin-bottom: 0.75rem;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>

<div class="profile-container">
    <div class="container">
        <div class="profile-content">
            
            <?php display_message($message); ?>

            <div class="row justify-content-center">
                <!-- User Information Card -->
                <div class="col-xl-4 col-lg-5 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header text-center">
                            <h5 class="card-title mb-0">User Information</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="avatar-initial rounded-circle bg-primary text-white mx-auto d-flex align-items-center justify-content-center">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <h4 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h4>
                            <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($user['role_name']); ?></span>
                            
                            <div class="profile-info">
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Role:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['role_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Member Since:</span>
                                    <span class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </button>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <?php if ($user['role_name'] === 'teacher'): ?>
                <div class="col-xl-7 col-lg-7">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-book me-2"></i>My Subjects
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($subjects)): ?>
                                <div class="empty-subjects">
                                    <i class="fas fa-book-open fa-3x mb-3 text-muted"></i>
                                    <p class="fs-5 mb-0">No subjects assigned yet.</p>
                                    <p class="text-muted">Contact your administrator to get subjects assigned.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Subject Name</th>
                                                <th>Subject Code</th>
                                                <th>Course</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subjects as $subject): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-subtle text-primary">
                                                            <?php echo htmlspecialchars($subject['code']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($subject['course_name']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="update_profile.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        <div class="invalid-feedback">Please enter a username.</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="update_password.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <div class="invalid-feedback">Please enter your current password.</div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="invalid-feedback">Please enter a new password.</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">Please confirm your new password.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Password confirmation validation
document.getElementById('changePasswordModal').querySelector('form').addEventListener('submit', function(event) {
    var newPassword = document.getElementById('new_password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        event.preventDefault();
        alert('New passwords do not match!');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>