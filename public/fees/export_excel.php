<?php
// Start output buffering to prevent any accidental output
ob_start();

// Include necessary files
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/utils.php';

// Initialize session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check permissions
if (!has_role('admin') && !hasPermission('fees_view')) {
    set_message('error', 'You do not have permission to export fees data.');
    header('Location: index.php');
    exit();
}

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$fee_type = $_GET['fee_type'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$pdo = get_pdo_connection();

// Build the query with filters
$where_conditions = [];
$params = [];

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

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT sf.*, s.first_name, s.last_name, 
               fs.title as structure_title, ft.name as fee_type_name,
               (SELECT SUM(amount_paid) FROM fee_payments WHERE student_fee_id = sf.id) as total_paid
        FROM student_fees sf
        JOIN students s ON sf.student_id = s.id
        JOIN fee_structures fs ON sf.structure_id = fs.id
        JOIN fee_types ft ON fs.type_id = ft.id
        $where_clause
        ORDER BY sf.due_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals for summary
$total_amount = 0;
$total_paid = 0;
$total_outstanding = 0;

foreach ($fees as $fee) {
    $total_amount += $fee['total_amount'];
    $total_paid += ($fee['total_paid'] ?? 0);
    $total_outstanding += $fee['outstanding_amount'];
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Fees_Report.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add report title and generation date
fputcsv($output, ['Fees Report']);
fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
fputcsv($output, []);  // Empty line

// Add filter information if any filters are applied
if ($status || $fee_type || $date_from || $date_to) {
    $filter_info = ['Filters applied:'];
    if ($status) $filter_info[] = "Status: $status";
    if ($fee_type) $filter_info[] = "Fee Type: $fee_type";
    if ($date_from) $filter_info[] = "From: $date_from";
    if ($date_to) $filter_info[] = "To: $date_to";
    fputcsv($output, $filter_info);
    fputcsv($output, []);  // Empty line
}

// Output the column headings
fputcsv($output, [
    'Student Name',
    'Fee Type',
    'Structure',
    'Total Amount',
    'Amount Paid',
    'Outstanding',
    'Due Date',
    'Status'
]);

// Output the data
foreach ($fees as $fee) {
    fputcsv($output, [
        $fee['first_name'] . ' ' . $fee['last_name'],
        $fee['fee_type_name'],
        $fee['structure_title'],
        number_format($fee['total_amount'], 2),
        number_format($fee['total_paid'] ?? 0, 2),
        number_format($fee['outstanding_amount'], 2),
        date('Y-m-d', strtotime($fee['due_date'])),
        ucfirst($fee['status'])
    ]);
}

// Add empty line before summary
fputcsv($output, []);

// Add summary section
fputcsv($output, ['Summary']);
fputcsv($output, ['Total Amount', number_format($total_amount, 2)]);
fputcsv($output, ['Total Paid', number_format($total_paid, 2)]);
fputcsv($output, ['Total Outstanding', number_format($total_outstanding, 2)]);

fclose($output);
