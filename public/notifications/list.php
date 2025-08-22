<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role_name'];

// --- Pagination Logic ---
$limit = 10; // Notifications per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;

try {
    // --- Fetch Total Count for Pagination ---
    $hasTargetRole = database_column_exists($pdo, 'notifications', 'target_role');
    $hasTargetUserId = database_column_exists($pdo, 'notifications', 'target_user_id');

    $whereParts = [];
    $params = [];
    
    if ($user_role === 'admin') {
        // Admin sees all notifications
        $whereSql = '1=1';
    } else {
        // Non-admin users only see notifications targeted to their role or themselves
        if ($hasTargetRole) {
            $whereParts[] = "n.target_role = 'all' OR n.target_role = ?";
            $params[] = $_SESSION['role_name'];
        }
        if ($hasTargetUserId) {
            $whereParts[] = "n.target_user_id = ?";
            $params[] = $user_id;
        }
        $whereSql = empty($whereParts) ? '1=1' : '(' . implode(' OR ', $whereParts) . ')';
    }

    $count_sql = "SELECT COUNT(n.id) FROM notifications n WHERE $whereSql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // --- Fetch Notifications for the current page ---
    $joinReads = database_table_exists($pdo, 'notification_reads');
    $hasCreatedBy = database_column_exists($pdo, 'notifications', 'created_by');
    $hasCreatedAt = database_column_exists($pdo, 'notifications', 'created_at');

    $readJoinSql = $joinReads ? 'LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = ?' : '';
    $readSelect = $joinReads ? 'nr.id as read_id,' : 'NULL as read_id,';
    $creatorJoinSql = $hasCreatedBy ? 'LEFT JOIN users u ON n.created_by = u.id' : '';
    $creatorSelect = $hasCreatedBy ? 'u.username as creator_name' : "'' as creator_name";
    $orderBy = $hasCreatedAt ? 'n.created_at DESC' : 'n.id DESC';

    // Reuse where built above
    $sql = "SELECT n.*, $readSelect $creatorSelect
            FROM notifications n
            $creatorJoinSql
            $readJoinSql
            WHERE $whereSql
            ORDER BY $orderBy
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $bindIndex = 1;
    if ($joinReads) {
        $stmt->bindValue($bindIndex++, $user_id, PDO::PARAM_INT);
    }
    foreach ($params as $paramIndex => $paramValue) {
        // Map param types: role is string, user_id is int; detect by index order
        if ($hasTargetRole && $paramIndex === 0) {
            $stmt->bindValue($bindIndex++, $paramValue, PDO::PARAM_STR);
        } else {
            $stmt->bindValue($bindIndex++, $paramValue, PDO::PARAM_INT);
        }
    }
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Notification list failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-bell text-primary me-2"></i>Notifications
        </h2>
        <?php if (in_array($_SESSION['role_name'], ['admin', 'teacher'])): ?>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Create New Notification
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info d-flex align-items-center">
            <i class="fas fa-info-circle me-2"></i>
            <div>You have no notifications at this time.</div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <?php 
                        $is_unread = is_null($notification['read_id']);
                        $type_class = $notification['type'] === 'urgent' ? 'danger' : 
                                    ($notification['type'] === 'important' ? 'warning' : 'info');
                    ?>
                    <a href="view.php?id=<?php echo $notification['id']; ?>" 
                       class="list-group-item list-group-item-action p-3 <?php echo $is_unread ? 'bg-light' : ''; ?>">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0 <?php echo $is_unread ? 'text-primary' : ''; ?>">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php if ($is_unread): ?>
                                            <span class="badge bg-primary ms-2">New</span>
                                        <?php endif; ?>
                                    </h5>
                                    <div class="text-muted small">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('M j, Y, g:i a', strtotime($notification['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center text-muted small">
                                    <div class="me-3">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($notification['creator_name']); ?>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $type_class; ?> text-white">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars(ucfirst($notification['type'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                    <?php if ($start_page > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif;
                endif;

                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor;

                if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                    </li>
                <?php endif;

                if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
include_once '../includes/footer.php';
?>
