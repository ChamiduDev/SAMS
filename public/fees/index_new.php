<?php
require_once '../includes/header.php';
$pdo = get_pdo_connection();

// Check permissions
$can_manage_fees = has_role('admin') || hasPermission('fees_edit');
$can_view_payments = hasPermission('payments_list');
$can_record_payments = hasPermission('payments_record');
$is_student = has_role('student');

// Get filter parameters
$status = $_GET['status'] ?? '';
$fee_type = $_GET['fee_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Get current user's student ID if they are a student
$student_id = null;
if ($is_student) {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_id = $stmt->fetchColumn();
}

try {
    // Fee Types
    $stmt = $pdo->query("SELECT * FROM fee_types ORDER BY name");
    $fee_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fee Structures
    $stmt = $pdo->query("
        SELECT fs.*, ft.name as type_name 
        FROM fee_structures fs 
        JOIN fee_types ft ON fs.type_id = ft.id 
        ORDER BY fs.title
    ");
    $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Student Fees with filters
    $params = [];
    $where_conditions = [];

    if ($is_student) {
        $where_conditions[] = "sf.student_id = ?";
        $params[] = $student_id;
    }

    if ($status) {
        $where_conditions[] = "sf.status = ?";
        $params[] = $status;
    }

    if ($fee_type) {
        $where_conditions[] = "ft.id = ?";
        $params[] = $fee_type;
    }

    if ($date_from) {
        $where_conditions[] = "sf.due_date >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $where_conditions[] = "sf.due_date <= ?";
        $params[] = $date_to;
    }

    $where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

    $sql = "SELECT sf.*, s.first_name, s.last_name, 
                   fs.title as structure_title, ft.name as fee_type_name,
                   (SELECT SUM(amount_paid) FROM fee_payments WHERE student_fee_id = sf.id) as total_paid
            FROM student_fees sf
            JOIN students s ON sf.student_id = s.id
            JOIN fee_structures fs ON sf.structure_id = fs.id
            JOIN fee_types ft ON fs.type_id = ft.id" . 
            $where_clause . " ORDER BY sf.due_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $student_fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rest of your existing code...
} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
}

?>

<!-- Add this inside your card where the overview table is -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-list me-2"></i>Overview
        </div>
        <div class="d-flex gap-2">
            <a href="export_pdf.php<?php echo !empty($_GET) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i>Export PDF
            </a>
            <a href="export_excel.php<?php echo !empty($_GET) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-excel me-1"></i>Export Excel
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form method="get" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>>Partial</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="fee_type" class="form-select">
                        <option value="">All Fee Types</option>
                        <?php foreach ($fee_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo $fee_type == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" placeholder="From Date" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" placeholder="To Date" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <!-- Your existing table code here -->
