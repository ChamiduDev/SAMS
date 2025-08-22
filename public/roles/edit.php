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

$pageTitle = "Edit Role";
include_once '../includes/header.php';
include_once '../includes/sidebar.php';

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

// Fetch existing permissions for this role
$stmt = $pdo->prepare("SELECT permission FROM role_permissions WHERE role_id = ?");
$stmt->execute([$roleId]);
$existingPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all permissions grouped by category
$stmt = $pdo->query("SELECT name, description, category FROM permissions ORDER BY category, name");
$allPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$permissionsByCategory = [];
foreach ($allPermissions as $permission) {
    $permissionsByCategory[$permission['category']][] = $permission;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleName = $_POST['role_name'];
    $selectedPermissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    if (empty($roleName)) {
        $error = "Role name cannot be empty.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update role name
            $stmt = $pdo->prepare("UPDATE roles SET name = ? WHERE id = ?");
            $stmt->execute([$roleName, $roleId]);

            // Delete existing permissions for this role
            $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);

            // Insert new permissions
            if (!empty($selectedPermissions)) {
                $sql = "INSERT INTO role_permissions (role_id, permission) VALUES ";
                $values = [];
                $placeholders = [];
                foreach ($selectedPermissions as $permission) {
                    $placeholders[] = "(?, ?)";
                    $values[] = $roleId;
                    $values[] = $permission;
                }
                $sql .= implode(", ", $placeholders);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
            }

            $pdo->commit();
            
            // Mark this role's permissions as needing refresh
            mark_role_permissions_for_refresh($roleId);
            
            $success = "Role '" . htmlspecialchars($roleName) . "' updated successfully.";
            
            // Refresh existing permissions after update
            $stmt = $pdo->prepare("SELECT permission FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            $existingPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating role: " . $e->getMessage();
        }
    }
}
?>

<div class="main-content">
    <div class="container">
        <h2>Edit Role: <?php echo htmlspecialchars($role['name']); ?></h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="edit.php?id=<?php echo $roleId; ?>" method="POST">
            <div class="form-group">
                <label for="role_name">Role Name:</label>
                <input type="text" class="form-control" id="role_name" name="role_name" value="<?php echo htmlspecialchars($role['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Permissions:</label>
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
                                    <?php foreach ($categoryPermissions as $permission): ?>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" 
                                                   id="<?php echo $permission['name']; ?>" 
                                                   name="permissions[]" 
                                                   value="<?php echo $permission['name']; ?>"
                                                   <?php echo in_array($permission['name'], $existingPermissions) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="<?php echo $permission['name']; ?>"
                                                   title="<?php echo htmlspecialchars($permission['description']); ?>">
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $permission['name']))); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Role</button>
            <a href="list.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
