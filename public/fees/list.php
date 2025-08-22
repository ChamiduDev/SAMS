<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role_name'];

$student_fees = [];
$total_fees = 0;
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

$students_list = [];
$fee_structures_list = [];

// Fetch filter options
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name");
    $students_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT id, title FROM fee_structures ORDER BY title");
    $fee_structures_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching filter options: ' . $e->getMessage());
}

// Filter parameters
$filter_student_id = $_GET['student_id'] ?? '';
$filter_structure_id = $_GET['structure_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query
$sql_base = "SELECT sf.id, sf.total_amount, sf.outstanding_amount, sf.due_date, sf.status,
                    s.first_name, s.last_name,
                    fs.title AS structure_title, ft.name AS fee_type_name
             FROM student_fees sf
             JOIN students s ON sf.student_id = s.id
             JOIN fee_structures fs ON sf.structure_id = fs.id
             JOIN fee_types ft ON fs.type_id = ft.id
             WHERE 1=1";
$count_sql_base = "SELECT COUNT(*) FROM student_fees sf
                   JOIN students s ON sf.student_id = s.id
                   JOIN fee_structures fs ON sf.structure_id = fs.id
                   JOIN fee_types ft ON fs.type_id = ft.id
                   WHERE 1=1";

$params = [];

// Apply filters
if ($filter_student_id) {
    $sql_base .= " AND sf.student_id = ?";
    $count_sql_base .= " AND sf.student_id = ?";
    $params[] = $filter_student_id;
}
if ($filter_structure_id) {
    $sql_base .= " AND sf.structure_id = ?";
    $count_sql_base .= " AND sf.structure_id = ?";
    $params[] = $filter_structure_id;
}
if ($filter_status) {
    $sql_base .= " AND sf.status = ?";
    $count_sql_base .= " AND sf.status = ?";
    $params[] = $filter_status;
}

// Role-based access
if (has_role('student')) {
    $stmt_student_id = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt_student_id->execute([$user_id]);
    $current_student_id = $stmt_student_id->fetchColumn();
    if ($current_student_id) {
        $sql_base .= " AND sf.student_id = ?";
        $count_sql_base .= " AND sf.student_id = ?";
        $params[] = $current_student_id;
    } else {
        $sql_base .= " AND 0=1"; // No student record found for this user
        $count_sql_base .= " AND 0=1";
    }
} elseif (has_role('parent')) {
    $stmt_children_ids = $pdo->prepare("SELECT id FROM students WHERE parent_user_id = ?"); // Assuming parent_user_id
    $stmt_children_ids->execute([$user_id]);
    $children_ids = $stmt_children_ids->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($children_ids)) {
        $placeholders = implode(',', array_fill(0, count($children_ids), '?'));
        $sql_base .= " AND sf.student_id IN ($placeholders)";
        $count_sql_base .= " AND sf.student_id IN ($placeholders)";
        $params = array_merge($params, $children_ids);
    } else {
        $sql_base .= " AND 0=1";
        $count_sql_base .= " AND 0=1";
    }
}

// Search query
if ($search_query) {
    $search_term = '%' . $search_query . '%';
    $sql_base .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR fs.title LIKE ? OR ft.name LIKE ?)";
    $count_sql_base .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR fs.title LIKE ? OR ft.name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Get total count
try {
    $stmt_count = $pdo->prepare($count_sql_base);
    $stmt_count->execute($params);
    $total_fees = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    set_message('error', 'Database error counting student fees: ' . $e->getMessage());
}

$total_pages = ceil($total_fees / $records_per_page);

// Fetch results
$sql = $sql_base . " ORDER BY sf.due_date ASC, s.last_name ASC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $student_fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching student fees: ' . $e->getMessage());
}

$message = get_message();
?>



    <div class="container mt-4">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h2 class="mb-0">
                    <i class="fas fa-money-check-alt me-2"></i>Student Fees
                </h2>
            </div>
            <?php if (has_role('admin')): ?>
            <div class="col-auto">
                <a href="assign.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Assign New Fee
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php display_message($message); ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="list.php">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select" id="student_id" name="student_id">
                                    <option value="">All Students</option>
                                    <?php foreach ($students_list as $student): ?>
                                        <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo ($filter_student_id == $student['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="student_id">Student</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select" id="structure_id" name="structure_id">
                                    <option value="">All Structures</option>
                                    <?php foreach ($fee_structures_list as $structure): ?>
                                        <option value="<?php echo htmlspecialchars($structure['id']); ?>" <?php echo ($filter_structure_id == $structure['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($structure['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="structure_id">Fee Structure</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="partial" <?php echo ($filter_status == 'partial') ? 'selected' : ''; ?>>Partial</option>
                                    <option value="paid" <?php echo ($filter_status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                    <option value="overdue" <?php echo ($filter_status == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                                </select>
                                <label for="status">Status</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search">
                                <label for="search">Search</label>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <a href="list.php" class="btn btn-light">
                                <i class="fas fa-undo me-2"></i>Clear
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($student_fees)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No student fees found matching your criteria.</h5>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-4">Student</th>
                                <th class="border-0">Fee Structure</th>
                                <th class="border-0">Type</th>
                                <th class="border-0 text-end">Total</th>
                                <th class="border-0 text-end">Outstanding</th>
                                <th class="border-0">Due Date</th>
                                <th class="border-0">Status</th>
                                <th class="border-0 text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student_fees as $fee): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-graduate text-muted me-2"></i>
                                            <?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-invoice-dollar text-muted me-2"></i>
                                            <?php echo htmlspecialchars($fee['structure_title']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($fee['fee_type_name']); ?></td>
                                    <td class="text-end">Rs.<?php echo number_format($fee['total_amount'], 2); ?></td>
                                    <td class="text-end">
                                        <span class="badge <?php 
                                            echo $fee['outstanding_amount'] <= 0 ? 'bg-success' : 
                                                ($fee['outstanding_amount'] < $fee['total_amount'] ? 'bg-warning' : 'bg-danger'); 
                                        ?>">
                                            Rs.<?php echo number_format($fee['outstanding_amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar text-muted me-2"></i>
                                            <?php 
                                                $due_date = new DateTime($fee['due_date']);
                                                echo $due_date->format('M d, Y'); 
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php
                                            echo $fee['status'] === 'paid' ? 'bg-success' :
                                                ($fee['status'] === 'partial' ? 'bg-warning' :
                                                ($fee['status'] === 'overdue' ? 'bg-danger' : 'bg-secondary'));
                                        ?> text-uppercase">
                                            <i class="fas <?php
                                                echo $fee['status'] === 'paid' ? 'fa-check-circle' :
                                                    ($fee['status'] === 'partial' ? 'fa-clock' :
                                                    ($fee['status'] === 'overdue' ? 'fa-exclamation-circle' : 'fa-hourglass-start'));
                                            ?> me-1"></i>
                                            <?php echo htmlspecialchars(ucfirst($fee['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if (has_role('admin')): ?>
                                            <a href="edit.php?id=<?php echo htmlspecialchars($fee['id']); ?>" 
                                               class="btn btn-sm btn-outline-primary me-1" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view.php?id=<?php echo htmlspecialchars($fee['id']); ?>" 
                                               class="btn btn-sm btn-outline-info me-1" 
                                               title="View Payments">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="if(confirm('Are you sure you want to delete this assigned fee? This will also delete associated payments.')) window.location.href='delete.php?id=<?php echo htmlspecialchars($fee['id']); ?>';"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php elseif (has_role('student') || has_role('parent')): ?>
                                            <a href="view.php?id=<?php echo htmlspecialchars($fee['id']); ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View Details">
                                                <i class="fas fa-eye me-1"></i>
                                                View Details
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-transparent pt-0">
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_structure_id) ? '&structure_id=' . urlencode($filter_structure_id) : ''; ?><?php echo !empty($filter_status) ? '&status=' . urlencode($filter_status) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_structure_id) ? '&structure_id=' . urlencode($filter_structure_id) : ''; ?><?php echo !empty($filter_status) ? '&status=' . urlencode($filter_status) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_structure_id) ? '&structure_id=' . urlencode($filter_structure_id) : ''; ?><?php echo !empty($filter_status) ? '&status=' . urlencode($filter_status) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    
