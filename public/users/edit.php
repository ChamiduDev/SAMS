<?php
// Start output buffering at the very beginning
ob_start();

// Include configuration and utility files first
require_once '../../config/config.php';
require_once '../../config/utils.php';

$pdo = get_pdo_connection();

// Include header
include_once '../includes/header.php';

$errors = [];
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;

// Fetch user details for pre-filling the form
if ($user_id > 0) {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.role_id, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        set_message('error', 'User not found.');
        header('Location: list.php');
        exit();
    }
} else {
    set_message('error', 'Invalid user ID.');
    header('Location: list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: edit.php?id=' . $user_id);
        exit();
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $roleName = $_POST['role'];

    // Input Validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    if (empty($roleName)) {
        $errors[] = 'Role is required.';
    }

    $roleId = null;
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$roleName]);
        $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($roleData) {
            $roleId = $roleData['id'];
        } else {
            $errors[] = 'Invalid role selected.';
        }
    }

    // Check for duplicate username or email, excluding current user
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username or Email already exists for another user.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?");
            $stmt->execute([$username, $email, $roleId, $user_id]);

            set_message('success', 'User updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error: ' . $e->getMessage());
            header('Location: edit.php?id=' . $user_id);
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
            <i class="fas fa-user-edit text-primary me-2"></i>Edit User
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
                <div class="avatar-initial rounded-circle bg-primary-subtle text-primary me-3" 
                     style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-1">Editing User Account</h5>
                    <p class="text-muted mb-0">User ID: <?php echo htmlspecialchars($user_id); ?></p>
                </div>
            </div>

            <form action="edit.php?id=<?php echo htmlspecialchars($user_id); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username" class="form-label text-muted fw-bold">
                                <i class="fas fa-user me-2"></i>Username
                            </label>
                            <input type="text" class="form-control form-control-lg" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" required
                                   placeholder="Enter username">
                            <div class="form-text">Choose a unique username for the account</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email" class="form-label text-muted fw-bold">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required
                                   placeholder="Enter email address">
                            <div class="form-text">Active email address for account recovery</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label for="role" class="form-label text-muted fw-bold">
                                <i class="fas fa-user-tag me-2"></i>User Role
                            </label>
                            <select class="form-select form-select-lg" id="role" name="role" required>
                                <option value="">Select a role</option>
                                <?php
                                $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($roles as $r) {
                                    echo '<option value="' . htmlspecialchars($r['name']) . '" ' . (($user['role_name'] ?? '') == $r['name'] ? 'selected' : '') . '>' . ucfirst(htmlspecialchars($r['name'])) . '</option>';
                                }
                                ?>
                            </select>
                            <div class="form-text">Select the appropriate role for the user</div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="list.php" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-save me-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>