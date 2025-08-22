<?php
require_once '../../config/config.php';
require_once '../../config/utils.php';
require_once '../includes/auth_check.php';

// Check if user has permission to add roles
if (!has_permission('roles_add')) {
    header('Location: ../unauthorized.php');
    exit();
}

$pageTitle = "Add New Role";
include_once '../includes/header.php';
include_once '../includes/sidebar.php';

// Fetch all permissions grouped by category
$stmt = $pdo->query("SELECT name, description, category FROM permissions ORDER BY category, name");
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group permissions by category
$permissionsByCategory = [];
foreach ($permissions as $permission) {
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

            // Insert role
            $stmt = $pdo->prepare("INSERT INTO roles (name) VALUES (?)");
            $stmt->execute([$roleName]);
            $roleId = $pdo->lastInsertId();

            // Insert permissions
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
            $success = "Role '" . htmlspecialchars($roleName) . "' added successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error adding role: " . $e->getMessage();
        }
    }
}
?>

<div class="main-content">
    <div class="container">
        <h2>Add New Role</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="add.php" method="POST">
            <div class="form-group">
                <label for="role_name">Role Name:</label>
                <input type="text" class="form-control" id="role_name" name="role_name" required>
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
                                                   value="<?php echo $permission['name']; ?>">
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
            <button type="submit" class="btn btn-primary">Add Role</button>
            <a href="list.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
