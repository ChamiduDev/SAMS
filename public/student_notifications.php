<?php
require_once '../config/config.php';
require_once '../config/utils.php';

// Check if student is logged in
if (!isset($_SESSION['student_portal_id'])) {
    header('Location: student_portal.php');
    exit;
}

$student_id = $_SESSION['student_portal_id'];
$notifications = [];
$error_message = '';

// Get database connection
$pdo = get_pdo_connection();

// Check if notifications tables exist
try {
    // Try to create tables if they don't exist
    $sql = file_get_contents(__DIR__ . '/../notifications_tables.sql');
    $pdo->exec($sql);
    
    // Query to fetch notifications for the student
    $stmt = $pdo->prepare("
        SELECT DISTINCT n.* 
        FROM notifications n
        LEFT JOIN student_notifications sn ON n.id = sn.notification_id
        WHERE (n.audience = 'all') 
           OR (n.audience = 'specific' AND sn.student_id = ?)
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$student_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "The notifications system is currently being set up. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Notifications - SAMS</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .notification {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .notification-date {
            color: #6c757d;
            font-size: 0.9em;
        }
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <h1>Your Notifications</h1>
        
        <?php if ($error_message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif (empty($notifications)): ?>
            <p>No notifications available.</p>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification">
                    <div class="notification-title">
                        <?php echo htmlspecialchars($notification['title']); ?>
                    </div>
                    <div class="notification-content">
                        <?php echo htmlspecialchars($notification['message']); ?>
                    </div>
                    <div class="notification-date">
                        <?php echo date('F j, Y g:i A', strtotime($notification['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <p><a href="student_portal.php">&larr; Back to Portal</a></p>
    </div>
</body>
</html>
