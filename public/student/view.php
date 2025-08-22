<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';

$pdo = get_pdo_connection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role_name'];
$notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($notification_id === 0) {
    header("Location: list.php");
    exit;
}

try {
    // --- Fetch the notification and verify access ---
    $hasTargetRole = database_column_exists($pdo, 'notifications', 'target_role');
    $hasTargetUserId = database_column_exists($pdo, 'notifications', 'target_user_id');
    $hasCreatedBy = database_column_exists($pdo, 'notifications', 'created_by');

    $accessParts = [];
    $params = [$notification_id];
    
    if ($user_role === 'admin') {
        // Admin can view all notifications
        $accessParts[] = "1=1";
    } else {
        // Non-admin users can only view notifications targeted to their role or themselves
        if ($hasTargetRole) {
            $accessParts[] = "n.target_role = 'all' OR n.target_role = ?";
            $params[] = $_SESSION['role_name'];
        }
        if ($hasTargetUserId) {
            $accessParts[] = "n.target_user_id = ?";
            $params[] = $user_id;
        }
    }
    $accessSql = empty($accessParts) ? '1=1' : '(' . implode(' OR ', $accessParts) . ')';

    $creatorJoinSql = $hasCreatedBy ? 'LEFT JOIN users u ON n.created_by = u.id' : '';
    $creatorSelect = $hasCreatedBy ? 'u.username as creator_name' : "'' as creator_name";

    $sql = "SELECT n.*, $creatorSelect
            FROM notifications n
            $creatorJoinSql
            WHERE n.id = ? AND $accessSql";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notification) {
        header("Location: list.php?error=not_found");
        exit;
    }

    // --- Mark the notification as read (if table exists) ---
    if (database_table_exists($pdo, 'notification_reads')) {
        $read_sql = "INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)";
        $read_stmt = $pdo->prepare($read_sql);
        $read_stmt->execute([$notification_id, $user_id]);
    }

} catch (PDOException $e) {
    error_log("Notification view failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

$csrf_token = function_exists('generate_csrf_token') ? generate_csrf_token() : '';

?>

<div id="page-content-wrapper">
    <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0">
            <i class="fas fa-bell text-primary me-2"></i>Notification Details
        </h2>
        <a href="notifications.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center">
                <h4 class="mb-0"><?php echo htmlspecialchars($notification['title']); ?></h4>
                <span class="badge bg-<?php echo $notification['type'] === 'urgent' ? 'danger' : ($notification['type'] === 'important' ? 'warning' : 'info'); ?> ms-3">
                    <?php echo htmlspecialchars(ucfirst($notification['type'])); ?>
                </span>
            </div>
            <?php if ($_SESSION['role_name'] === 'admin'): ?>
                <div class="action-buttons">
                    <a href="delete.php?id=<?php echo $notification['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" 
                       class="btn btn-outline-danger btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this notification? This action cannot be undone.');">
                        <i class="fas fa-trash-alt me-1"></i>Delete
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-body p-4">
            <div class="meta-info mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center text-muted">
                            <i class="fas fa-user me-2"></i>
                            <span>By: <strong class="text-dark"><?php echo htmlspecialchars($notification['creator_name']); ?></strong></span>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex align-items-center text-muted justify-content-md-end">
                            <i class="far fa-clock me-2"></i>
                            <span>Posted: <strong class="text-dark"><?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?></strong></span>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-users me-1"></i>
                        Audience: <?php echo htmlspecialchars(ucfirst($notification['target_role'])); ?>
                    </span>
                </div>
            </div>

            <div class="message-content border-top pt-4">
                <p class="card-text lead"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
?>
