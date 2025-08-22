<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';

$pdo = get_pdo_connection();

try {
    // Get all subjects for the student
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            s.id,
            s.name as subject_name,
            s.code as subject_code
        FROM subjects s
        JOIN student_courses sc ON s.course_id = sc.course_id
        WHERE sc.student_id = ?
        ORDER BY s.name
    ");
    $stmt->execute([$student['id']]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get exam results grouped by subject
    $stmt = $pdo->prepare("
        SELECT 
            e.id as exam_id,
            e.title as exam_title,
            e.exam_date,
            e.total_marks,
            e.weight,
            s.id as subject_id,
            s.name as subject_name,
            s.code as subject_code,
            er.marks_obtained,
            er.remarks,
            u.username as marked_by_name,
            ROUND((er.marks_obtained / e.total_marks) * 100, 2) as percentage,
            gs.grade_label,
            gs.grade_point
        FROM exam_results er
        JOIN exams e ON er.exam_id = e.id
        JOIN subjects s ON e.subject_id = s.id
        LEFT JOIN users u ON er.marked_by = u.id
        LEFT JOIN grading_scale gs ON 
            (er.marks_obtained / e.total_marks * 100) BETWEEN gs.min_percent AND gs.max_percent
        WHERE er.student_id = ?
        ORDER BY s.name, e.exam_date DESC
    ");
    $stmt->execute([$student['id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group results by subject
    $results_by_subject = [];
    $overall_stats = [
        'total_exams' => 0,
        'total_marks' => 0,
        'obtained_marks' => 0,
        'highest_percent' => 0,
        'lowest_percent' => 100
    ];

    foreach ($results as $result) {
        $subject_id = $result['subject_id'];
        if (!isset($results_by_subject[$subject_id])) {
            $results_by_subject[$subject_id] = [
                'subject_name' => $result['subject_name'],
                'subject_code' => $result['subject_code'],
                'exams' => [],
                'stats' => [
                    'total_exams' => 0,
                    'average_percent' => 0,
                    'highest_percent' => 0,
                    'lowest_percent' => 100
                ]
            ];
        }

        $results_by_subject[$subject_id]['exams'][] = $result;
        
        // Update subject statistics
        $results_by_subject[$subject_id]['stats']['total_exams']++;
        $results_by_subject[$subject_id]['stats']['average_percent'] = 
            ($results_by_subject[$subject_id]['stats']['average_percent'] * 
             ($results_by_subject[$subject_id]['stats']['total_exams'] - 1) +
             $result['percentage']) / $results_by_subject[$subject_id]['stats']['total_exams'];
        
        $results_by_subject[$subject_id]['stats']['highest_percent'] = 
            max($results_by_subject[$subject_id]['stats']['highest_percent'], $result['percentage']);
        $results_by_subject[$subject_id]['stats']['lowest_percent'] = 
            min($results_by_subject[$subject_id]['stats']['lowest_percent'], $result['percentage']);

        // Update overall statistics
        $overall_stats['total_exams']++;
        $overall_stats['total_marks'] += $result['total_marks'];
        $overall_stats['obtained_marks'] += $result['marks_obtained'];
        $overall_stats['highest_percent'] = max($overall_stats['highest_percent'], $result['percentage']);
        $overall_stats['lowest_percent'] = min($overall_stats['lowest_percent'], $result['percentage']);
    }

    // Calculate overall percentage
    $overall_stats['average_percent'] = $overall_stats['total_marks'] > 0 
        ? ($overall_stats['obtained_marks'] / $overall_stats['total_marks']) * 100 
        : 0;

} catch (PDOException $e) {
    error_log("Results fetch failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

// Function to get grade color class
function get_grade_color($percentage) {
    if ($percentage >= 90) return 'success';
    if ($percentage >= 80) return 'primary';
    if ($percentage >= 70) return 'info';
    if ($percentage >= 60) return 'warning';
    return 'danger';
}
?>

<div id="page-content-wrapper">
    <div class="container mt-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">
                <i class="fas fa-chart-bar text-primary me-2"></i>Academic Results
            </h2>
        </div>

        <!-- Overall Performance Card -->
        <?php if ($overall_stats['total_exams'] > 0): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Overall Performance</h5>
                <div class="row g-4 mt-2">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stats-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                    <i class="fas fa-calculator fa-fw"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="small text-muted">Average Score</div>
                                <div class="h5 mb-0">
                                    <?php echo number_format($overall_stats['average_percent'], 1); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stats-icon bg-success bg-opacity-10 text-success rounded p-3">
                                    <i class="fas fa-arrow-up fa-fw"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="small text-muted">Highest Score</div>
                                <div class="h5 mb-0">
                                    <?php echo number_format($overall_stats['highest_percent'], 1); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stats-icon bg-warning bg-opacity-10 text-warning rounded p-3">
                                    <i class="fas fa-arrow-down fa-fw"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="small text-muted">Lowest Score</div>
                                <div class="h5 mb-0">
                                    <?php echo number_format($overall_stats['lowest_percent'], 1); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stats-icon bg-info bg-opacity-10 text-info rounded p-3">
                                    <i class="fas fa-edit fa-fw"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="small text-muted">Total Exams</div>
                                <div class="h5 mb-0">
                                    <?php echo $overall_stats['total_exams']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Results by Subject -->
        <?php if (empty($results_by_subject)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No exam results available yet.
            </div>
        <?php else: ?>
            <?php foreach ($results_by_subject as $subject_id => $subject_data): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <?php echo htmlspecialchars($subject_data['subject_name']); ?>
                                <small class="text-muted ms-2">(<?php echo htmlspecialchars($subject_data['subject_code']); ?>)</small>
                            </h5>
                            <span class="badge bg-primary">
                                Average: <?php echo number_format($subject_data['stats']['average_percent'], 1); ?>%
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Date</th>
                                        <th>Marks</th>
                                        <th>Percentage</th>
                                        <th>Grade</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subject_data['exams'] as $exam): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($exam['exam_title']); ?></strong>
                                                <?php if ($exam['weight'] != 100): ?>
                                                    <small class="text-muted">(Weight: <?php echo $exam['weight']; ?>%)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($exam['exam_date'])); ?></td>
                                            <td>
                                                <?php echo $exam['marks_obtained']; ?> / <?php echo $exam['total_marks']; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $grade_color = get_grade_color($exam['percentage']);
                                                ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-<?php echo $grade_color; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $exam['percentage']; ?>%">
                                                        </div>
                                                    </div>
                                                    <span class="ms-2 text-<?php echo $grade_color; ?>">
                                                        <?php echo number_format($exam['percentage'], 1); ?>%
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($exam['grade_label']): ?>
                                                    <span class="badge bg-<?php echo get_grade_color($exam['percentage']); ?>">
                                                        <?php echo $exam['grade_label']; ?>
                                                        <?php if ($exam['grade_point']): ?>
                                                            (<?php echo number_format($exam['grade_point'], 2); ?>)
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($exam['remarks']): ?>
                                                    <small><?php echo htmlspecialchars($exam['remarks']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
