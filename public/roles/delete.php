<?php
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Check if user is logged in and is an admin
// auth_check.php will be modified later to handle permissions
// For now, a simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$roleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($roleId === 0) {
    header('Location: list.php?error=Role ID not provided.');
    exit();
}

// Prevent deletion of the default admin role (assuming ID 1 is admin)
if ($roleId === 1) {
    header('Location: list.php?error=Cannot delete the default admin role.');
    exit();
}

try {
    $pdo->beginTransaction();

    // Delete role permissions first
    $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);

    // Update users who have this role to a default role (e.g., student role ID 3)
    // Or you might want to set their role_id to NULL or prompt admin to reassign
    $stmt = $pdo->prepare("UPDATE users SET role_id = 3 WHERE role_id = ?"); // Assuming 3 is student role
    $stmt->execute([$roleId]);

    // Delete the role
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);

    $pdo->commit();
    header('Location: list.php?success=Role deleted successfully.');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: list.php?error=Error deleting role: ' . urlencode($e->getMessage()));
    exit();
}
