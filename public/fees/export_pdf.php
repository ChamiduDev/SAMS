<?php
// Start output buffering to prevent any accidental output
ob_start();

// Include necessary files
require_once '../../fpdf/fpdf.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/utils.php';

// Initialize session
session_start();
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

// Clear any existing output buffers and start fresh
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

// Create PDF
class FeesPDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Fees Overview Report', 0, 1, 'C');
        $this->Ln(10);
        
        // Column headers
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(60, 7, 'Student Name', 1);
        $this->Cell(40, 7, 'Fee Type', 1);
        $this->Cell(30, 7, 'Amount', 1);
        $this->Cell(30, 7, 'Paid', 1);
        $this->Cell(30, 7, 'Status', 1);
        $this->Ln();
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new FeesPDF();
$pdf->AddPage('P', 'A4');
$pdf->SetFont('Arial', '', 10);

foreach ($fees as $fee) {
    $pdf->Cell(60, 7, $fee['first_name'] . ' ' . $fee['last_name'], 1);
    $pdf->Cell(40, 7, $fee['fee_type_name'], 1);
    $pdf->Cell(30, 7, 'Rs. ' . number_format($fee['total_amount'], 2), 1);
    $pdf->Cell(30, 7, 'Rs. ' . number_format($fee['total_paid'] ?? 0, 2), 1);
    $pdf->Cell(30, 7, ucfirst($fee['status']), 1);
    $pdf->Ln();
}

$pdf->Output('Fees_Report.pdf', 'D');
