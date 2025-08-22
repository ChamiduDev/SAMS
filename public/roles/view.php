<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/utils.php';

// Ensure we have a session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'admin') {
    header('Location: ' . BASE_URL . 'public/login.php');
    exit();
}

$roleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($roleId === 0) {
    header('Location: list.php?error=Role ID not provided.');
    exit();
}

// Fetch role details
$stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$role) {
    header('Location: list.php?error=Role not found.');
    exit();
}

// Fetch all permissions grouped by category
$stmt = $pdo->query("SELECT name, description, category FROM permissions ORDER BY category, name");
$allPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$permissionsByCategory = [];
foreach ($allPermissions as $permission) {
    $permissionsByCategory[$permission['category']][] = $permission;
}

// Fetch permissions for this role
$stmt = $pdo->prepare("SELECT permission FROM role_permissions WHERE role_id = ?");
$stmt->execute([$roleId]);
$rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Set page title for header
$pageTitle = "View Role";

// Include header and sidebar with proper error handling
$headerPath = __DIR__ . '/../includes/header.php';
$sidebarPath = __DIR__ . '/../includes/sidebar.php';

if (!file_exists($headerPath)) {
    die('Header file not found. Path: ' . $headerPath);
}

include_once $headerPath;
?>
<div class="main-content">
    <div class="container">
        <?php if ($role): ?>
            <h2>Role: <?php echo htmlspecialchars($role['name']); ?></h2>
            <h4>Permissions</h4>
            <?php if (!empty($permissionsByCategory)): ?>
                <div class="accordion" id="permissionsAccordion">
                    <?php foreach ($permissionsByCategory as $category => $categoryPermissions): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#category<?php echo md5($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </button>
                            </h2>
                            <div id="category<?php echo md5($category); ?>" class="accordion-collapse collapse" 
                                 data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body">
                                    <ul class="list-unstyled">
                                        <?php foreach ($categoryPermissions as $permission): ?>
                                            <?php if (in_array($permission['name'], $rolePermissions)): ?>
                                                <li>
                                                    <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $permission['name']))); ?></strong>
                                                    <?php if ($permission['description']): ?>
                                                        <span class="text-muted">- <?php echo htmlspecialchars($permission['description']); ?></span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No permissions found for this role.</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger">Role not found.</div>
        <?php endif; ?>
        <a href="list.php" class="btn btn-secondary mt-3">Back to Roles</a>
    </div>
</div>

<?php 
$footerPath = __DIR__ . '/../includes/footer.php';
if (!file_exists($footerPath)) {
    die('Footer file not found. Path: ' . $footerPath);
}
include_once $footerPath; 
?>
