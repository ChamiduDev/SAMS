<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';

// Debug information
error_log("Session user_id: " . print_r($_SESSION['user_id'] ?? 'not set', true));
error_log("Student data: " . print_r($student ?? 'not set', true));

// Verify student information is available
if (!isset($_SESSION['user_id'])) {
    die("Session expired. Please log in again.");
}

if (!isset($student)) {
    // Try to get student information again
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                u.email,
                u.username
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE u.id = ? AND s.status = 'active'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            die("Student record not found. Please contact administrator.");
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch student data: " . $e->getMessage());
        die("Database error occurred. Please try again later.");
    }
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!password_verify($current_password, $user['password'])) {
                $error_message = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "New passwords do not match.";
            } elseif (strlen($new_password) < 8) {
                $error_message = "New password must be at least 8 characters long.";
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $success_message = "Password updated successfully.";
            }
        }
        
        elseif (isset($_POST['update_email'])) {
            $new_email = filter_var($_POST['new_email'], FILTER_VALIDATE_EMAIL);
            if (!$new_email) {
                $error_message = "Invalid email format.";
            } else {
                // Check if email is already in use
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$new_email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $error_message = "This email is already in use.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $stmt->execute([$new_email, $_SESSION['user_id']]);
                    $success_message = "Email updated successfully.";
                }
            }
        }
        
        elseif (isset($_POST['update_notifications'])) {
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $exam_reminders = isset($_POST['exam_reminders']) ? 1 : 0;
            $attendance_alerts = isset($_POST['attendance_alerts']) ? 1 : 0;
            $fee_reminders = isset($_POST['fee_reminders']) ? 1 : 0;

            // Update notification preferences
            $stmt = $pdo->prepare("
                INSERT INTO user_preferences (user_id, email_notifications, exam_reminders, attendance_alerts, fee_reminders)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                email_notifications = VALUES(email_notifications),
                exam_reminders = VALUES(exam_reminders),
                attendance_alerts = VALUES(attendance_alerts),
                fee_reminders = VALUES(fee_reminders)
            ");
            $stmt->execute([$_SESSION['user_id'], $email_notifications, $exam_reminders, $attendance_alerts, $fee_reminders]);
            $success_message = "Notification preferences updated successfully.";
        }
    } catch (PDOException $e) {
        $error_message = "An error occurred. Please try again later.";
        error_log("Settings update error: " . $e->getMessage());
    }
}

// Get current notification preferences
try {
    // Check if user_preferences table exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
        AND table_name = 'user_preferences'
    ");
    $stmt->execute();
    $table_exists = $stmt->fetchColumn() > 0;

    if ($table_exists) {
        $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$table_exists || !$preferences) {
        $preferences = [
            'email_notifications' => 1,
            'exam_reminders' => 1,
            'attendance_alerts' => 1,
            'fee_reminders' => 1
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching preferences: " . $e->getMessage());
    $preferences = [];
}
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h4 class="mb-3">Account Settings</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../student_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Password Change -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-key text-primary me-2"></i>Change Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="passwordForm">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       required minlength="8">
                                <div class="form-text">Must be at least 8 characters long</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-envelope text-primary me-2"></i>Email Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="emailForm">
                            <div class="mb-3">
                                <label for="current_email" class="form-label">Current Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="new_email" class="form-label">New Email</label>
                                <input type="email" class="form-control" id="new_email" name="new_email" required>
                            </div>
                            <button type="submit" name="update_email" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Email
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password validation
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    }

    // Email validation
    const emailForm = document.getElementById('emailForm');
    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            const newEmail = document.getElementById('new_email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(newEmail)) {
                e.preventDefault();
                alert('Please enter a valid email address!');
            }
        });
    }
});
</script>

<style>
.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    margin-top: 0.25em;
}

.form-switch .form-check-input:checked {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.card {
    border: none;
    border-radius: 10px;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.form-control:focus {
    border-color: var(--accent-color);
    box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
}
</style>

<?php require_once '../includes/footer.php'; ?>
