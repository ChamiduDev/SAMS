<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();
if (!function_exists('generate_csrf_token') || !function_exists('verify_csrf_token')) {
    die("Required security components are not available. Please check your configuration.");
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $type = $_POST['type'];
        $target_role = $_POST['target_role'];

        if (empty($title) || empty($message)) {
            $error = "Title and message cannot be empty.";
        } elseif (strlen($title) > 150) {
            $error = "Title cannot exceed 150 characters.";
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO notifications (title, message, type, target_role, created_by) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bindParam(1, $title, PDO::PARAM_STR);
                $stmt->bindParam(2, $message, PDO::PARAM_STR);
                $stmt->bindParam(3, $type, PDO::PARAM_STR);
                $stmt->bindParam(4, $target_role, PDO::PARAM_STR);
                $stmt->bindParam(5, $_SESSION['user_id'], PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $success = "Notification created successfully!";
                } else {
                    $error = "Failed to create notification. Please try again.";
                }
            } catch (PDOException $e) {
                error_log("Notification creation failed: " . $e->getMessage());
                $error = "A database error occurred.";
            }
        }
    }
}

$csrf_token = generate_csrf_token();

include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-bell text-primary me-2"></i>Create New Notification
        </h2>
        <a href="list.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <a href="list.php" class="btn btn-sm btn-success ms-auto">View All Notifications</a>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="create.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="mb-4">
                    <label for="title" class="form-label text-muted fw-bold">
                        <i class="fas fa-heading me-2"></i>Notification Title
                    </label>
                    <input type="text" class="form-control form-control-lg" id="title" name="title" 
                           required maxlength="150" placeholder="Enter a clear, concise title">
                    <div class="form-text">Maximum 150 characters</div>
                </div>

                <div class="mb-4">
                    <label for="message" class="form-label text-muted fw-bold">
                        <i class="fas fa-comment-alt me-2"></i>Message Content
                    </label>
                    <textarea class="form-control" id="message" name="message" rows="6" required
                              placeholder="Enter the detailed message content"></textarea>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label for="type" class="form-label text-muted fw-bold">
                            <i class="fas fa-tag me-2"></i>Notification Type
                        </label>
                        <select class="form-select" id="type" name="type">
                            <option value="general">📢 General Notice</option>
                            <option value="exam">📝 Exam Related</option>
                            <option value="fees">💰 Fees Update</option>
                            <option value="attendance">📅 Attendance Notice</option>
                            <option value="event">🎉 Event Announcement</option>
                            <option value="urgent">⚠️ Urgent Alert</option>
                            <option value="important">❗ Important Update</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="target_role" class="form-label text-muted fw-bold">
                            <i class="fas fa-users me-2"></i>Target Audience
                        </label>
                        <select class="form-select" id="target_role" name="target_role">
                            <option value="all">👥 All Users</option>
                            <option value="student">🎓 All Students</option>
                            <option value="parent">👨‍👩‍👧‍👦 All Parents</option>
                            <option value="teacher">👨‍🏫 All Teachers</option>
                            <?php if ($_SESSION['role_name'] === 'admin'): ?>
                                <option value="admin">👨‍💼 Admins Only</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 gap-2">
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
