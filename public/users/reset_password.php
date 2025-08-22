<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$username = '';

// Fetch username for display
if ($user_id > 0) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        set_message('error', 'User not found.');
        header('Location: list.php');
        exit();
    }
    $username = $user_data['username'];
} else {
    set_message('error', 'Invalid user ID.');
    header('Location: list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: reset_password.php?id=' . $user_id);
        exit();
    }

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Input Validation
    if (empty($password)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'New password must be at least 6 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);

            set_message('success', 'Password reset successfully for user ' . htmlspecialchars($username) . '.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error: ' . $e->getMessage());
            header('Location: reset_password.php?id=' . $user_id);
            exit();
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

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-key text-primary me-2"></i>Reset Password
        </h2>
        <a href="list.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'danger'; ?> d-flex align-items-center">
            <i class="fas fa-<?php echo strpos($message, 'success') !== false ? 'check' : 'exclamation'; ?>-circle me-2"></i>
            <?php display_message($message); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <div class="avatar-initial rounded-circle bg-warning-subtle text-warning me-3" 
                     style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-lock fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-1">Reset Password for <?php echo htmlspecialchars($username); ?></h5>
                    <p class="text-muted mb-0">User ID: <?php echo htmlspecialchars($user_id); ?></p>
                </div>
            </div>

            <div class="alert alert-info d-flex">
                <i class="fas fa-info-circle me-2 mt-1"></i>
                <div>
                    <strong>Password Requirements:</strong>
                    <ul class="mb-0 ps-3">
                        <li>At least 6 characters long</li>
                        <li>Make sure to use a strong password</li>
                        <li>Remember to share the new password with the user securely</li>
                    </ul>
                </div>
            </div>

            <form action="reset_password.php?id=<?php echo htmlspecialchars($user_id); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password" class="form-label text-muted fw-bold">
                                <i class="fas fa-lock me-2"></i>New Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" 
                                       id="password" name="password" required
                                       placeholder="Enter new password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="confirm_password" class="form-label text-muted fw-bold">
                                <i class="fas fa-lock me-2"></i>Confirm Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" 
                                       id="confirm_password" name="confirm_password" required
                                       placeholder="Confirm new password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="list.php" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-warning btn-lg px-4">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Add password visibility toggle functionality
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
        const password = document.getElementById('confirm_password');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>