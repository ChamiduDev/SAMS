<?php
// notifications/get_notifications.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/utils.php';

function get_user_notifications($pdo, $user_id, $user_role, $limit = null) {
    try {
        $whereParts = [];
        $params = [];
        
        // Check if the notifications table has the necessary columns
        $hasTargetRole = database_column_exists($pdo, 'notifications', 'target_role');
        $hasTargetUserId = database_column_exists($pdo, 'notifications', 'target_user_id');
        
        if ($hasTargetRole) {
            $whereParts[] = "(n.target_role = 'all' OR n.target_role = :user_role)";
            $params[':user_role'] = $user_role;
        }
        
        if ($hasTargetUserId) {
            $whereParts[] = "(n.target_user_id IS NULL OR n.target_user_id = :user_id)";
            $params[':user_id'] = $user_id;
        }
        
        $whereSql = empty($whereParts) ? '1=1' : implode(' AND ', $whereParts);
        $limitSql = $limit ? " LIMIT " . intval($limit) : "";
        
        $sql = "SELECT n.*, 
                CASE WHEN nr.id IS NULL THEN 1 ELSE 0 END as is_unread
                FROM notifications n
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = :reader_id
                WHERE $whereSql
                ORDER BY n.created_at DESC" . $limitSql;
        
        $params[':reader_id'] = $user_id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        return [];
    }
}

function get_unread_count($pdo, $user_id, $user_role) {
    try {
        $whereParts = [];
        $params = [];
        
        $hasTargetRole = database_column_exists($pdo, 'notifications', 'target_role');
        $hasTargetUserId = database_column_exists($pdo, 'notifications', 'target_user_id');
        
        if ($hasTargetRole) {
            $whereParts[] = "(n.target_role = 'all' OR n.target_role = :user_role)";
            $params[':user_role'] = $user_role;
        }
        
        if ($hasTargetUserId) {
            $whereParts[] = "(n.target_user_id IS NULL OR n.target_user_id = :user_id)";
            $params[':user_id'] = $user_id;
        }
        
        $whereSql = empty($whereParts) ? '1=1' : implode(' AND ', $whereParts);
        
        $sql = "SELECT COUNT(DISTINCT n.id) as unread_count
                FROM notifications n
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = :reader_id
                WHERE $whereSql AND nr.id IS NULL";
        
        $params[':reader_id'] = $user_id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['unread_count'];
    } catch (PDOException $e) {
        error_log("Error counting unread notifications: " . $e->getMessage());
        return 0;
    }
}

function mark_notification_as_read($pdo, $notification_id, $user_id) {
    try {
        // First, check if the notification exists and is accessible to the user
        $notification = get_notification_by_id($pdo, $notification_id, $user_id);
        if (!$notification) {
            return false;
        }
        
        // Check if already marked as read
        $stmt = $pdo->prepare("SELECT id FROM notification_reads WHERE notification_id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        if ($stmt->fetch()) {
            return true; // Already marked as read
        }
        
        // Mark as read
        $stmt = $pdo->prepare("INSERT INTO notification_reads (notification_id, user_id, read_at) VALUES (?, ?, NOW())");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

function get_notification_by_id($pdo, $notification_id, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
        $stmt->execute([$notification_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching notification: " . $e->getMessage());
        return null;
    }
}
