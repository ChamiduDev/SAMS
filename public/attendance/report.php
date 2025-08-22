<?php
require_once '../includes/header.php';

// Include FPDF only when exporting to PDF
if (isset($_GET['export_pdf']) || isset($_GET['export_csv'])) {
    ob_end_clean(); // Clear any existing output
    if (isset($_GET['export_pdf'])) {
        require_once '../../fpdf/fpdf.php';
    }
}

$pdo = get_pdo_connection();

// Get user role and ID from session
$user_role = $_SESSION['role_name'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Helper functions for Excel export
function getCourseNameById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT name FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() ?: 'Unknown Course';
}

function getSubjectNameById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() ?: 'Unknown Subject';
}

function getStudentNameById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM students WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() ?: 'Unknown Student';
}

function calculateTotals($data) {
    $totals = [
        'total_classes' => 0,
        'total_present' => 0,
        'total_absent' => 0,
        'total_late' => 0,
        'avg_attendance' => 0
    ];
    
    foreach ($data as $row) {
        $totals['total_classes'] += $row['total_classes'];
        $totals['total_present'] += $row['present'];
        $totals['total_absent'] += $row['absent'];
        $totals['total_late'] += $row['late'];
    }
    
    if ($totals['total_classes'] > 0) {
        $totals['avg_attendance'] = round(($totals['total_present'] / $totals['total_classes']) * 100, 2);
    }
    
    return $totals;
}

$courses = [];
$subjects = [];
$students_list = [];
$report_data = [];
$report_type = $_GET['report_type'] ?? '';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';
$filter_course_id = $_GET['course_id'] ?? '';
$filter_subject_id = $_GET['subject_id'] ?? '';
$filter_student_id = $_GET['student_id'] ?? '';

// Fetch courses for filter
try {
    $stmt = $pdo->query("SELECT id, name FROM courses ORDER BY name");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}

// Fetch students for filter
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name");
    $students_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching students for filter: " . $e->getMessage());
}

// Fetch subjects based on selected course for filter dropdown (if course is selected)
if ($filter_course_id) {
    try {
        if ($user_role === 'teacher') {
            $stmt_subjects = $pdo->prepare("SELECT id, name FROM subjects WHERE course_id = ? AND teacher_id = ? ORDER BY name");
            $stmt_subjects->execute([$filter_course_id, $user_id]);
        } else {
            $stmt_subjects = $pdo->prepare("SELECT id, name FROM subjects WHERE course_id = ? ORDER BY name");
            $stmt_subjects->execute([$filter_course_id]);
        }
        $subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching subjects for filter: " . $e->getMessage());
    }
}

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['generate_report']) || isset($_GET['export_csv']) || isset($_GET['export_pdf']))) {
    $sql_base = "SELECT
                    a.student_id,
                    s.first_name as student_first_name,
                    s.last_name as student_last_name,
                    a.subject_id,
                    sub.name as subject_name,
                    c.name as course_name,
                    a.status,
                    a.date
                FROM
                    attendance a
                JOIN
                    students s ON a.student_id = s.id
                JOIN
                    subjects sub ON a.subject_id = sub.id
                JOIN
                    courses c ON sub.course_id = c.id
                WHERE 1=1";
    $params = [];

    if ($filter_start_date && isValidDate($filter_start_date)) {
        $sql_base .= " AND a.date >= ?";
        $params[] = $filter_start_date;
    }
    if ($filter_end_date && isValidDate($filter_end_date)) {
        $sql_base .= " AND a.date <= ?";
        $params[] = $filter_end_date;
    }
    if ($filter_course_id) {
        $sql_base .= " AND c.id = ?";
        $params[] = $filter_course_id;
    }
    if ($filter_subject_id) {
        $sql_base .= " AND sub.id = ?";
        $params[] = $filter_subject_id;
    }
    if ($filter_student_id) {
        $sql_base .= " AND s.id = ?";
        $params[] = $filter_student_id;
    }

    try {
        $stmt = $pdo->prepare($sql_base);
        $stmt->execute($params);
        $raw_attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process raw data into report format
        if ($report_type === 'student_summary') {
            $report_data = process_student_summary($raw_attendance_data);
        } elseif ($report_type === 'subject_summary') {
            $report_data = process_subject_summary($raw_attendance_data);
        }

        // Handle CSV Export
        if (isset($_GET['export_csv']) && !empty($report_data)) {
            // Clean any output that might have been sent
            if (ob_get_length()) ob_end_clean();
            
            $filename = "attendance_report_" . $report_type . "_" . date('Ymd_His') . ".xls";
            
            // Set headers for Excel output
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper Excel encoding
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add report title and date
            fputcsv($output, ['Attendance Report']);
            fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
            fputcsv($output, []); // Empty line
            
            // Add filter information if any filters are applied
            if ($filter_start_date || $filter_end_date || $filter_course_id || $filter_subject_id || $filter_student_id) {
                $filters = ['Filters:'];
                if ($filter_start_date) $filters[] = "Start Date: $filter_start_date";
                if ($filter_end_date) $filters[] = "End Date: $filter_end_date";
                if ($filter_course_id) $filters[] = "Course: " . getCourseNameById($pdo, $filter_course_id);
                if ($filter_subject_id) $filters[] = "Subject: " . getSubjectNameById($pdo, $filter_subject_id);
                if ($filter_student_id) $filters[] = "Student: " . getStudentNameById($pdo, $filter_student_id);
                fputcsv($output, $filters);
                fputcsv($output, []); // Empty line
            }

            if ($report_type === 'student_summary') {
                // Add headers
                fputcsv($output, ['Student Name', 'Course', 'Total Classes', 'Present', 'Absent', 'Late', 'Attendance Percentage']);
                
                // Add data
                foreach ($report_data as $row) {
                    fputcsv($output, [
                        $row['student_name'],
                        $row['course_name'],
                        $row['total_classes'],
                        $row['present'],
                        $row['absent'],
                        $row['late'],
                        $row['percentage'] . '%'
                    ]);
                }
            } elseif ($report_type === 'subject_summary') {
                // Add headers
                fputcsv($output, ['Subject Name', 'Course', 'Total Classes', 'Present', 'Absent', 'Late', 'Attendance Percentage']);
                
                // Add data
                foreach ($report_data as $row) {
                    fputcsv($output, [
                        $row['subject_name'],
                        $row['course_name'],
                        $row['total_classes'],
                        $row['present'],
                        $row['absent'],
                        $row['late'],
                        $row['percentage'] . '%'
                    ]);
                }
            }
            
            // Add empty line before summary
            fputcsv($output, []);
            
            // Add summary information
            $totals = calculateTotals($report_data);
            fputcsv($output, ['Summary']);
            fputcsv($output, ['Total Classes', $totals['total_classes']]);
            fputcsv($output, ['Total Present', $totals['total_present']]);
            fputcsv($output, ['Total Absent', $totals['total_absent']]);
            fputcsv($output, ['Total Late', $totals['total_late']]);
            fputcsv($output, ['Average Attendance', $totals['avg_attendance'] . '%']);
            
            fclose($output);
            exit();
        }

        // Handle PDF Export
        if (isset($_GET['export_pdf']) && !empty($report_data)) {
            ob_end_clean(); // Clean any output buffer
            require_once '../../fpdf/fpdf.php'; // Include FPDF here
            
            $pdf = new FPDF();
            $pdf->AddPage();
            // die('Page added'); // Debugging line 3
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Attendance Report', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 12);
            $pdf->Ln(10);

            if ($report_type === 'student_summary') {
                $pdf->Cell(40, 10, 'Student Name', 1);
                $pdf->Cell(40, 10, 'Course', 1);
                $pdf->Cell(30, 10, 'Total Classes', 1);
                $pdf->Cell(20, 10, 'Present', 1);
                $pdf->Cell(20, 10, 'Absent', 1);
                $pdf->Cell(20, 10, 'Late', 1);
                $pdf->Cell(30, 10, 'Attendance %', 1);
                $pdf->Ln();
                foreach ($report_data as $row) {
                    $pdf->Cell(40, 10, $row['student_name'], 1);
                    $pdf->Cell(40, 10, $row['course_name'], 1);
                    $pdf->Cell(30, 10, $row['total_classes'], 1);
                    $pdf->Cell(20, 10, $row['present'], 1);
                    $pdf->Cell(20, 10, $row['absent'], 1);
                    $pdf->Cell(20, 10, $row['late'], 1);
                    $pdf->Cell(30, 10, $row['percentage'] . '%', 1);
                    $pdf->Ln();
                }
            } elseif ($report_type === 'subject_summary') {
                $pdf->Cell(40, 10, 'Subject Name', 1);
                $pdf->Cell(40, 10, 'Course', 1);
                $pdf->Cell(30, 10, 'Total Classes', 1);
                $pdf->Cell(20, 10, 'Present', 1);
                $pdf->Cell(20, 10, 'Absent', 1);
                $pdf->Cell(20, 10, 'Late', 1);
                $pdf->Cell(30, 10, 'Attendance %', 1);
                $pdf->Ln();
                foreach ($report_data as $row) {
                    $pdf->Cell(40, 10, $row['subject_name'], 1);
                    $pdf->Cell(40, 10, $row['course_name'], 1);
                    $pdf->Cell(30, 10, $row['total_classes'], 1);
                    $pdf->Cell(20, 10, $row['present'], 1);
                    $pdf->Cell(20, 10, $row['absent'], 1);
                    $pdf->Cell(20, 10, $row['late'], 1);
                    $pdf->Cell(30, 10, $row['percentage'] . '%', 1);
                    $pdf->Ln();
                }
            }

            // die('Before Output'); // Debugging line 4
            $pdf->Output('D', "attendance_report_" . $report_type . "_" . date('Ymd_His') . ".pdf");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Error generating report: " . $e->getMessage());
        echo "<div class=\"alert alert-danger\">Error generating report: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

function process_student_summary($data) {
    $summary = [];
    foreach ($data as $record) {
        $key = $record['student_id'] . '-' . $record['course_name']; // Group by student and course
        if (!isset($summary[$key])) {
            $summary[$key] = [
                'student_name' => $record['student_first_name'] . ' ' . $record['student_last_name'],
                'course_name' => $record['course_name'],
                'total_classes' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'percentage' => 0
            ];
        }
        $summary[$key]['total_classes']++;
        if ($record['status'] === 'present') {
            $summary[$key]['present']++;
        } elseif ($record['status'] === 'absent') {
            $summary[$key]['absent']++;
        } elseif ($record['status'] === 'late') {
            $summary[$key]['late']++;
        }
    }

    foreach ($summary as &$row) {
        if ($row['total_classes'] > 0) {
            $row['percentage'] = round(($row['present'] + $row['late']) / $row['total_classes'] * 100, 2);
        }
    }
    return array_values($summary);
}

function process_subject_summary($data) {
    $summary = [];
    foreach ($data as $record) {
        $key = $record['subject_id'] . '-' . $record['course_name']; // Group by subject and course
        if (!isset($summary[$key])) {
            $summary[$key] = [
                'subject_name' => $record['subject_name'],
                'course_name' => $record['course_name'],
                'total_classes' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'percentage' => 0
            ];
        }
        $summary[$key]['total_classes']++;
        if ($record['status'] === 'present') {
            $summary[$key]['present']++;
        }
        elseif ($record['status'] === 'absent') {
            $summary[$key]['absent']++;
        }
        elseif ($record['status'] === 'late') {
            $summary[$key]['late']++;
        }
    }

    foreach ($summary as &$row) {
        if ($row['total_classes'] > 0) {
            $row['percentage'] = round(($row['present'] + $row['late']) / $row['total_classes'] * 100, 2);
        }
    }
    return array_values($summary);
}

?>


    <div class="container-fluid px-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0">
                    <i class="fas fa-chart-bar me-2"></i>Attendance Reports
                </h5>
                <a href="list.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Records
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="report.php">
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-primary">
                                <i class="fas fa-filter me-2"></i>Report Filters
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="report_type" class="form-label">Report Type</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-file-alt"></i>
                                        </span>
                                        <select class="form-select" id="report_type" name="report_type" required>
                                            <option value="">Select Report Type</option>
                                            <option value="student_summary" <?php echo ($report_type === 'student_summary') ? 'selected' : ''; ?>>Student Summary</option>
                                            <option value="subject_summary" <?php echo ($report_type === 'subject_summary') ? 'selected' : ''; ?>>Subject Summary</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo htmlspecialchars($filter_start_date); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                               value="<?php echo htmlspecialchars($filter_end_date); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="course_id" class="form-label">Course</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-graduation-cap"></i>
                                        </span>
                                        <select class="form-select" id="course_id" name="course_id">
                                            <option value="">All Courses</option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?php echo htmlspecialchars($course['id']); ?>" <?php echo ($filter_course_id == $course['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($course['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="subject_id" class="form-label">Subject</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-book"></i>
                                        </span>
                                        <select class="form-select" id="subject_id" name="subject_id">
                                            <option value="">All Subjects</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo htmlspecialchars($subject['id']); ?>" <?php echo ($filter_subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($subject['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="student_id" class="form-label">Student</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-user-graduate"></i>
                                        </span>
                                        <select class="form-select" id="student_id" name="student_id">
                                            <option value="">All Students</option>
                                            <?php foreach ($students_list as $student): ?>
                                                <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo ($filter_student_id == $student['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary" name="generate_report">
                                        <i class="fas fa-sync-alt me-2"></i>Generate Report
                                    </button>
                                    <?php if (!empty($report_data)): ?>
                                        <button type="submit" class="btn btn-success" name="export_csv" value="1">
                                            <i class="fas fa-file-csv me-2"></i>Export CSV
                                        </button>
                                        <button type="submit" class="btn btn-danger" name="export_pdf" value="1">
                                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                                        </button>
                                    <?php endif; ?>
                                    <a href="report.php" class="btn btn-light">
                                        <i class="fas fa-times me-2"></i>Clear Filters
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($report_data)): ?>
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-primary">
                                <i class="fas fa-chart-line me-2"></i>Report Results
                            </h6>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <?php if ($report_type === 'student_summary'): ?>
                                            <tr>
                                                <th class="py-3">Student Name</th>
                                                <th class="py-3">Course</th>
                                                <th class="py-3">Total Classes</th>
                                                <th class="py-3">Present</th>
                                                <th class="py-3">Absent</th>
                                                <th class="py-3">Late</th>
                                                <th class="py-3">Attendance %</th>
                                            </tr>
                                        <?php elseif ($report_type === 'subject_summary'): ?>
                                            <tr>
                                                <th class="py-3">Subject Name</th>
                                                <th class="py-3">Course</th>
                                                <th class="py-3">Total Classes</th>
                                                <th class="py-3">Present</th>
                                                <th class="py-3">Absent</th>
                                                <th class="py-3">Late</th>
                                                <th class="py-3">Attendance %</th>
                                            </tr>
                                        <?php endif; ?>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <?php if ($report_type === 'student_summary'): ?>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-user-graduate text-primary me-2"></i>
                                                            <?php echo htmlspecialchars($row['student_name']); ?>
                                                        </div>
                                                    </td>
                                                <?php elseif ($report_type === 'subject_summary'): ?>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-book text-primary me-2"></i>
                                                            <?php echo htmlspecialchars($row['subject_name']); ?>
                                                        </div>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-graduation-cap me-1"></i>
                                                        <?php echo htmlspecialchars($row['course_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($row['total_classes']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>
                                                        <?php echo htmlspecialchars($row['present']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>
                                                        <?php echo htmlspecialchars($row['absent']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo htmlspecialchars($row['late']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $percentage = $row['percentage'];
                                                    $badge_class = 'bg-danger';
                                                    if ($percentage >= 90) {
                                                        $badge_class = 'bg-success';
                                                    } elseif ($percentage >= 75) {
                                                        $badge_class = 'bg-warning text-dark';
                                                    } elseif ($percentage >= 60) {
                                                        $badge_class = 'bg-info';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <i class="fas fa-percentage me-1"></i>
                                                        <?php echo htmlspecialchars($row['percentage']); ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif (isset($_GET['generate_report'])): ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>No data found for the selected report criteria.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    