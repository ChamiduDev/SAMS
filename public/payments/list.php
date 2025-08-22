<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$payments = [];
$total_payments = 0;
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
$filter_method = $_GET['method'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query
$sql_base = "SELECT fp.id, fp.amount_paid, fp.payment_date, fp.method, fp.reference,
                    s.first_name, s.last_name,
                    fs.title AS structure_title,
                    u.username AS created_by_username
             FROM fee_payments fp
             JOIN student_fees sf ON fp.student_fee_id = sf.id
             JOIN students s ON sf.student_id = s.id
             JOIN fee_structures fs ON sf.structure_id = fs.id
             LEFT JOIN users u ON fp.created_by = u.id
             WHERE 1=1";
$count_sql_base = "SELECT COUNT(*) FROM fee_payments fp
                   JOIN student_fees sf ON fp.student_fee_id = sf.id
                   JOIN students s ON sf.student_id = s.id
                   JOIN fee_structures fs ON sf.structure_id = fs.id
                   WHERE 1=1";

$params = [];

// Apply filters
if ($filter_student_id) {
    $sql_base .= " AND s.id = ?";
    $count_sql_base .= " AND s.id = ?";
    $params[] = $filter_student_id;
}
if ($filter_structure_id) {
    $sql_base .= " AND fs.id = ?";
    $count_sql_base .= " AND fs.id = ?";
    $params[] = $filter_structure_id;
}
if ($filter_method) {
    $sql_base .= " AND fp.method = ?";
    $count_sql_base .= " AND fp.method = ?";
    $params[] = $filter_method;
}

// Search query
if ($search_query) {
    $search_term = '%' . $search_query . '%';
    $sql_base .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR fs.title LIKE ? OR fp.reference LIKE ?)";
    $count_sql_base .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR fs.title LIKE ? OR fp.reference LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Get total count
try {
    $stmt_count = $pdo->prepare($count_sql_base);
    $stmt_count->execute($params);
    $total_payments = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    set_message('error', 'Database error counting payments: ' . $e->getMessage());
}

$total_pages = ceil($total_payments / $records_per_page);

// Fetch results
$sql = $sql_base . " ORDER BY fp.payment_date DESC, fp.created_at DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching payments: ' . $e->getMessage());
}

$message = get_message();
?>



    <div class="container-fluid page-container">
        <div class="page-header">
            <div>
                <h2>Payment History</h2>
                <p class="text-muted">View and manage all payment transactions</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" id="exportBtn">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="filters-section">
            <form method="GET" action="list.php" class="mb-0">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select" id="student_id" name="student_id">
                                <option value="">All Students</option>
                                <?php foreach ($students_list as $student): ?>
                                    <option value="<?php echo htmlspecialchars($student['id']); ?>" 
                                            <?php echo ($filter_student_id == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="structure_id" class="form-label">Fee Structure</label>
                            <select class="form-select" id="structure_id" name="structure_id">
                                <option value="">All Structures</option>
                                <?php foreach ($fee_structures_list as $structure): ?>
                                    <option value="<?php echo htmlspecialchars($structure['id']); ?>" 
                                            <?php echo ($filter_structure_id == $structure['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($structure['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="method" class="form-label">Payment Method</label>
                            <select class="form-select" id="method" name="method">
                                <option value="">All Methods</option>
                                <option value="cash" <?php echo ($filter_method == 'cash') ? 'selected' : ''; ?>>
                                    <i class="fas fa-money-bill"></i> Cash
                                </option>
                                <option value="card" <?php echo ($filter_method == 'card') ? 'selected' : ''; ?>>
                                    <i class="fas fa-credit-card"></i> Card
                                </option>
                                <option value="bank_transfer" <?php echo ($filter_method == 'bank_transfer') ? 'selected' : ''; ?>>
                                    <i class="fas fa-university"></i> Bank Transfer
                                </option>
                                <option value="online" <?php echo ($filter_method == 'online') ? 'selected' : ''; ?>>
                                    <i class="fas fa-globe"></i> Online
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_range" class="form-label">Date Range</label>
                            <select class="form-select" id="date_range" name="date_range">
                                <option value="">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="year">This Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="search" class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search_query); ?>" 
                                       placeholder="Search payments...">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2 d-flex justify-content-end gap-2">
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Total Payments</h6>
                        <h3 class="card-title mb-0">
                            <?php echo number_format($total_payments); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Total Amount</h6>
                        <h3 class="card-title mb-0">
                            $<?php 
                                $total_amount = array_sum(array_column($payments, 'amount_paid'));
                                echo number_format($total_amount, 2);
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Today's Payments</h6>
                        <h3 class="card-title mb-0">
                            <?php
                                $today_payments = count(array_filter($payments, function($p) {
                                    return date('Y-m-d', strtotime($p['payment_date'])) === date('Y-m-d');
                                }));
                                echo number_format($today_payments);
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Average Payment</h6>
                        <h3 class="card-title mb-0">
                            $<?php 
                                $avg_payment = $total_payments > 0 ? $total_amount / $total_payments : 0;
                                echo number_format($avg_payment, 2);
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($payments)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <h4>No Payments Found</h4>
                    <p class="text-muted">No payments match your current filter criteria.</p>
                    <a href="list.php" class="btn btn-primary">
                        <i class="fas fa-undo"></i> Clear Filters
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="20%">Student</th>
                                <th width="20%">Fee Structure</th>
                                <th width="15%">Amount</th>
                                <th width="15%">Date</th>
                                <th width="10%">Method</th>
                                <th width="10%">Reference</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td data-label="Student">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2">
                                                <?php 
                                                    $initials = strtoupper(substr($payment['first_name'], 0, 1) . substr($payment['last_name'], 0, 1));
                                                    echo $initials;
                                                ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($payment['id']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Fee Structure">
                                        <div class="text-wrap"><?php echo htmlspecialchars($payment['structure_title']); ?></div>
                                    </td>
                                    <td data-label="Amount">
                                        <div class="fw-bold text-success">
                                            $<?php echo number_format($payment['amount_paid'], 2); ?>
                                        </div>
                                    </td>
                                    <td data-label="Date">
                                        <div>
                                            <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('g:i A', strtotime($payment['payment_date'])); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td data-label="Method">
                                        <?php
                                            $method_badges = [
                                                'cash' => 'bg-success',
                                                'card' => 'bg-info',
                                                'bank_transfer' => 'bg-primary',
                                                'online' => 'bg-warning text-dark'
                                            ];
                                            $method_icons = [
                                                'cash' => 'money-bill',
                                                'card' => 'credit-card',
                                                'bank_transfer' => 'university',
                                                'online' => 'globe'
                                            ];
                                        ?>
                                        <span class="badge <?php echo $method_badges[$payment['method']]; ?>">
                                            <i class="fas fa-<?php echo $method_icons[$payment['method']]; ?> me-1"></i>
                                            <?php echo ucfirst($payment['method']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Reference">
                                        <?php if ($payment['reference']): ?>
                                            <span class="text-muted" title="<?php echo htmlspecialchars($payment['reference']); ?>">
                                                <?php echo substr(htmlspecialchars($payment['reference']), 0, 10) . '...'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="action-buttons">
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#paymentModal<?php echo $payment['id']; ?>"
                                                    title="View Details">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                            <a href="print_receipt.php?id=<?php echo htmlspecialchars($payment['id']); ?>" 
                                               class="btn btn-sm btn-primary"
                                               title="Print Receipt">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>

                                        <!-- Payment Details Modal -->
                                        <div class="modal fade" id="paymentModal<?php echo $payment['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-receipt me-2"></i>
                                                            Payment Details
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Student</label>
                                                            <p><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Fee Structure</label>
                                                            <p><?php echo htmlspecialchars($payment['structure_title']); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Amount</label>
                                                            <p class="text-success fw-bold">$<?php echo number_format($payment['amount_paid'], 2); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Payment Method</label>
                                                            <p>
                                                                <span class="badge <?php echo $method_badges[$payment['method']]; ?>">
                                                                    <i class="fas fa-<?php echo $method_icons[$payment['method']]; ?> me-1"></i>
                                                                    <?php echo ucfirst($payment['method']); ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Reference Number</label>
                                                            <p><?php echo $payment['reference'] ? htmlspecialchars($payment['reference']) : '<span class="text-muted">Not provided</span>'; ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Recorded By</label>
                                                            <p><?php echo htmlspecialchars($payment['created_by_username'] ?? 'System'); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="fw-bold">Date & Time</label>
                                                            <p><?php echo date('F j, Y g:i A', strtotime($payment['payment_date'])); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <a href="print_receipt.php?id=<?php echo htmlspecialchars($payment['id']); ?>" 
                                                           class="btn btn-primary">
                                                            <i class="fas fa-print me-1"></i> Print Receipt
                                                        </a>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i> Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer">
                    <nav aria-label="Page navigation" class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_payments); ?> 
                            of <?php echo $total_payments; ?> payments
                        </div>
                        <ul class="pagination mb-0">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_structure_id) ? '&structure_id=' . urlencode($filter_structure_id) : ''; ?><?php echo !empty($filter_method) ? '&method=' . urlencode($filter_method) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, min($current_page - 2, $total_pages - 4));
                            $end_page = min($total_pages, max($current_page + 2, 5));
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_structure_id) ? '&structure_id=' . urlencode($filter_structure_id) : ''; ?><?php echo !empty($filter_method) ? '&method=' . urlencode($filter_method) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor;

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_structure_id) ? '&structure_id=' . urlencode($filter_structure_id) : ''; ?><?php echo !empty($filter_method) ? '&method=' . urlencode($filter_method) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>

    
