<?php
// Set content type to JSON for AJAX response
header('Content-Type: application/json');

require_once '../includes/header.php';

$pdo = get_pdo_connection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role_name'];

try {
    // --- Database Query ---
    $hasTargetRole = database_column_exists($pdo, 'notifications', 'target_role');
    $hasTargetUserId = database_column_exists($pdo, 'notifications', 'target_user_id');
    $readsExists = database_table_exists($pdo, 'notification_reads');

    $whereParts = [];
    $params = [];
    if ($hasTargetRole) {
        $whereParts[] = "n.target_role = 'all' OR n.target_role = :user_role_name";
        $params[':user_role_name'] = $_SESSION['role_name'];
    }
    if ($hasTargetUserId) {
        $whereParts[] = "n.target_user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    $whereSql = empty($whereParts) ? '1=1' : '(' . implode(' OR ', $whereParts) . ')';
    $notExistsReads = $readsExists ? 'AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id = n.id AND nr.user_id = :user_id)' : '';
    if ($readsExists) {
        $params[':user_id'] = $user_id; // ensure present for NOT EXISTS clause
    }

    $sql = "SELECT COUNT(n.id) FROM notifications n WHERE $whereSql $notExistsReads";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $unread_count = $stmt->fetchColumn();

    // --- Send JSON Response ---
    echo json_encode(['success' => true, 'unread_count' => (int)$unread_count]);

} catch (PDOException $e) {
    error_log("AJAX unread count failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database query failed.']);
}

?>
