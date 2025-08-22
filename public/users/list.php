<?php
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Include header
include_once '../includes/header.php';

$pdo = get_pdo_connection();

// Pagination settings
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search_query = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$search_sql = '';
// $search_params is no longer needed here as we will use named parameters

if (!empty($search_query)) {
    $search_sql = " WHERE username LIKE :search_username OR email LIKE :search_email OR r.name LIKE :search_role_name";
}

// Get total number of users for pagination
$stmt_count = $pdo->prepare("SELECT COUNT(u.id) FROM users u JOIN roles r ON u.role_id = r.id" . $search_sql);
if (!empty($search_query)) {
    $stmt_count->bindValue(':search_username', "%" . $search_query . "%", PDO::PARAM_STR);
    $stmt_count->bindValue(':search_email', "%" . $search_query . "%", PDO::PARAM_STR);
    $stmt_count->bindValue(':search_role_name', "%" . $search_query . "%", PDO::PARAM_STR);
}
$stmt_count->execute();
$total_users = $stmt_count->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Fetch users with pagination and search
$sql = "SELECT u.id, u.username, u.email, r.name as role_name, u.created_at FROM users u JOIN roles r ON u.role_id = r.id" . $search_sql . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

if (!empty($search_query)) {
    $stmt->bindValue(':search_username', "%" . $search_query . "%", PDO::PARAM_STR);
    $stmt->bindValue(':search_email', "%" . $search_query . "%", PDO::PARAM_STR);
    $stmt->bindValue(':search_role_name', "%" . $search_query . "%", PDO::PARAM_STR);
}

// Bind limit and offset parameters as integers
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = get_message();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-users text-primary me-2"></i>User Management
        </h2>
            <div class="d-flex gap-2">
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </a>
                <button onclick="window.location.reload(true)" class="btn btn-secondary">
                    <i class="fas fa-sync-alt me-2"></i>Refresh List
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'danger'; ?> d-flex align-items-center">
                <i class="fas fa-<?php echo strpos($message, 'success') !== false ? 'check' : 'exclamation'; ?>-circle me-2"></i>
                <?php display_message($message); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <form action="list.php" method="GET" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                   placeholder="Search by username, email, or role" 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search Users
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($users)): ?>
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                No users found matching your search criteria.
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="fw-semibold">ID</th>
                                <th class="fw-semibold">Username</th>
                                <th class="fw-semibold">Email</th>
                                <th class="fw-semibold">Role</th>
                                <th class="fw-semibold">Created Date</th>
                                <th class="fw-semibold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="align-middle"><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initial rounded-circle bg-primary-subtle text-primary me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <i class="fas fa-envelope text-muted me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-<?php 
                                            echo $user['role_name'] === 'admin' ? 'danger' : 
                                                ($user['role_name'] === 'teacher' ? 'success' : 
                                                ($user['role_name'] === 'parent' ? 'warning' : 'info')); 
                                        ?>-subtle text-<?php 
                                            echo $user['role_name'] === 'admin' ? 'danger' : 
                                                ($user['role_name'] === 'teacher' ? 'success' : 
                                                ($user['role_name'] === 'parent' ? 'warning' : 'info')); 
                                        ?>">
                                            <i class="fas fa-<?php 
                                                echo $user['role_name'] === 'admin' ? 'user-shield' : 
                                                    ($user['role_name'] === 'teacher' ? 'chalkboard-teacher' : 
                                                    ($user['role_name'] === 'parent' ? 'user-friends' : 'user-graduate')); 
                                            ?> me-1"></i>
                                            <?php echo ucfirst(htmlspecialchars($user['role_name'])); ?>
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <i class="far fa-calendar-alt text-muted me-2"></i>
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="edit.php?id=<?php echo htmlspecialchars($user['id']); ?>" 
                                               class="btn btn-sm btn-warning-subtle text-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="reset_password.php?id=<?php echo htmlspecialchars($user['id']); ?>" 
                                               class="btn btn-sm btn-info-subtle text-info">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger-subtle text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal" 
                                                    data-user-id="<?php echo htmlspecialchars($user['id']); ?>" 
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete user <strong id="modalUsername"></strong> (ID: <span id="modalUserId"></span>)? This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteForm" action="delete.php" method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" id="deleteUserId">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        var deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-user-id');
            var username = button.getAttribute('data-username');

            var modalUserId = deleteModal.querySelector('#modalUserId');
            var modalUsername = deleteModal.querySelector('#modalUsername');
            var deleteUserIdInput = deleteModal.querySelector('#deleteUserId');

            modalUserId.textContent = userId;
            modalUsername.textContent = username;
            deleteUserIdInput.value = userId;
        });
    </script>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
// Force a fresh page load when coming from add/edit/delete
if (document.referrer.includes('add.php') || 
    document.referrer.includes('edit.php') || 
    document.referrer.includes('delete.php')) {
    window.location.reload(true);
}

// Add event listener for the DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    // Check if we need to refresh (coming from add/edit/delete)
    if (window.location.href.includes('refresh=')) {
        // Remove the refresh parameter and reload
        let newUrl = window.location.href.split('?')[0];
        window.history.replaceState({}, document.title, newUrl);
        window.location.reload(true);
    }
});
</script>