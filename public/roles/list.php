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

$pageTitle = "Manage Roles";
include_once '../includes/header.php';
include_once '../includes/sidebar.php';

// Fetch all roles
$stmt = $pdo->query("SELECT id, name FROM roles ORDER BY name");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="main-content">
    <div class="container">
        <h2>Manage Roles</h2>
        <a href="add.php" class="btn btn-primary mb-3">Add New Role</a>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if (empty($roles)): ?>
            <p>No roles found.</p>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Role Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($role['id']); ?></td>
                            <td><?php echo htmlspecialchars($role['name']); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this role? This will also remove all associated permissions.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
