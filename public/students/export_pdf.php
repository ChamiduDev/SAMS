<?php
// Start output buffering to prevent any accidental output
ob_start();

// Include necessary files
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/utils.php';
require_once '../../fpdf/fpdf.php';

// Initialize session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check permissions
if (!has_role('admin') && !hasPermission('students_view')) {
    set_message('error', 'You do not have permission to export student data.');
    header('Location: list.php');
    exit();
}

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

$pdo = get_pdo_connection();

// Get search parameter if any
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$search_sql = '';
$params = [];

if (!empty($search_query)) {
    $search_term = '%' . $search_query . '%';
    $search_sql = " WHERE (
        s.student_id LIKE ? OR 
        s.first_name LIKE ? OR 
        s.last_name LIKE ? OR 
        s.year LIKE ? OR
        s.gender LIKE ? OR
        s.contact_no LIKE ? OR
        c.name LIKE ? OR
        sub.name LIKE ?
    ) AND s.status = 'active'";
    $params = array_fill(0, 8, $search_term);
} else {
    $search_sql = " WHERE s.status = 'active'";
}

// Fetch students data
$sql = "SELECT s.id, s.student_id, s.first_name, s.last_name, s.year, s.gender, s.contact_no,
        GROUP_CONCAT(DISTINCT c.name) as courses,
        GROUP_CONCAT(DISTINCT sub.name) as subjects
        FROM students s
        LEFT JOIN student_course_subjects scs ON s.id = scs.student_id
        LEFT JOIN courses c ON scs.course_id = c.id
        LEFT JOIN subjects sub ON scs.subject_id = sub.id" . 
        $search_sql . 
        " GROUP BY s.id ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

class StudentsPDF extends FPDF {
    function Header() {
        // Add school logo if exists
        // $this->Image('path_to_logo.png', 10, 10, 30);
        
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Students List', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Ln(10);
        
        // Column headers
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 7, 'Student ID', 1);
        $this->Cell(60, 7, 'Name', 1);
        $this->Cell(20, 7, 'Year', 1);
        $this->Cell(20, 7, 'Gender', 1);
        $this->Cell(30, 7, 'Contact', 1);
        $this->Cell(0, 7, 'Courses', 1);
        $this->Ln();
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new StudentsPDF();
$pdf->AddPage('L', 'A4'); // Landscape orientation
$pdf->SetFont('Arial', '', 9);

foreach ($students as $student) {
    // Check if we need to add a new page
    if ($pdf->GetY() > 250) {
        $pdf->AddPage('L', 'A4');
    }
    
    $pdf->Cell(30, 6, $student['student_id'], 1);
    $pdf->Cell(60, 6, $student['first_name'] . ' ' . $student['last_name'], 1);
    $pdf->Cell(20, 6, $student['year'], 1);
    $pdf->Cell(20, 6, $student['gender'], 1);
    $pdf->Cell(30, 6, $student['contact_no'], 1);
    $pdf->Cell(0, 6, $student['courses'], 1);
    $pdf->Ln();
}

// Output PDF
$pdf->Output('D', 'Students_List.pdf');
