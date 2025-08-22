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

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Students_List.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add report title and date
fputcsv($output, ['Students List']);
fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
fputcsv($output, []); // Empty line

// Add filter information if search was used
if ($search_query) {
    fputcsv($output, ['Search Filter: ' . $search_query]);
    fputcsv($output, []); // Empty line
}

// Output the column headings
fputcsv($output, [
    'Student ID',
    'First Name',
    'Last Name',
    'Year',
    'Gender',
    'Contact No',
    'Courses',
    'Subjects'
]);

// Output the data
foreach ($students as $student) {
    fputcsv($output, [
        $student['student_id'],
        $student['first_name'],
        $student['last_name'],
        $student['year'],
        $student['gender'],
        $student['contact_no'],
        $student['courses'],
        $student['subjects']
    ]);
}

// Add empty line and summary
fputcsv($output, []);
fputcsv($output, ['Total Students:', count($students)]);

fclose($output);
