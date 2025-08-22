<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: list.php');
        exit();
    }

    $user_id_to_delete = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($user_id_to_delete <= 0) {
        set_message('error', 'Invalid user ID for deletion.');
        header('Location: list.php');
        exit();
    }

    // Prevent deleting the currently logged-in admin
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id_to_delete) {
        set_message('error', 'You cannot delete your own account.');
        header('Location: list.php');
        exit();
    }

    try {
        // Check if the user to be deleted is an admin
        $stmt = $pdo->prepare("SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$user_id_to_delete]);
        $user_role_name = $stmt->fetchColumn();

        if ($user_role_name === 'admin') {
            // Prevent deleting the last remaining admin account
            $stmt = $pdo->query("SELECT COUNT(u.id) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'admin'");
            $admin_count = $stmt->fetchColumn();

            if ($admin_count <= 1) {
                set_message('error', 'Cannot delete the last administrator account.');
                header('Location: list.php');
                exit();
            }
        }

        // Proceed with deletion
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id_to_delete]);

        if ($stmt->rowCount() > 0) {
            set_message('success', 'User deleted successfully.');
        } else {
            set_message('error', 'User not found or could not be deleted.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Database error: ' . $e->getMessage());
    }

    header('Location: list.php');
    exit();
} else {
    set_message('error', 'Invalid request method.');
    header('Location: list.php');
    exit();
}
?>