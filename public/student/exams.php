<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';

$pdo = get_pdo_connection();
$current_date = date('Y-m-d');

// Initialize variables
$subjects = [];
$exams = [];
$grouped_exams = [
    'today' => [],
    'upcoming' => [],
    'past' => []
];

try {
    // Get student's courses and subjects
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            c.id as course_id,
            c.name as course_name,
            c.code as course_code,
            s.id as subject_id,
            s.name as subject_name,
            s.code as subject_code
        FROM students st
        JOIN student_courses sc ON st.id = sc.student_id
        JOIN courses c ON sc.course_id = c.id
        LEFT JOIN subjects s ON s.course_id = c.id
        WHERE st.id = ?
        AND s.id IS NOT NULL
        ORDER BY c.name, s.name
    ");
    $stmt->execute([$student['id']]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get subject IDs for the student
    $subject_ids = array_unique(array_column($subjects, 'subject_id'));

    if (!empty($subject_ids)) {
        // Get exams
        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.title,
                e.exam_date,
                e.total_marks,
                e.weight,
                s.name as subject_name,
                s.code as subject_code,
                c.name as course_name,
                CASE 
                    WHEN e.exam_date < CURDATE() THEN 'past'
                    WHEN e.exam_date = CURDATE() THEN 'today'
                    ELSE 'upcoming'
                END as exam_status,
                er.marks_obtained,
                er.id as result_id
            FROM exams e
            JOIN subjects s ON e.subject_id = s.id
            JOIN courses c ON s.course_id = c.id
            LEFT JOIN exam_results er ON e.id = er.exam_id AND er.student_id = ?
            WHERE e.subject_id IN ($placeholders)
            ORDER BY e.exam_date ASC, e.id ASC
        ");
        
        // Combine student_id and subject_ids for the query
        $params = array_merge([$student['id']], $subject_ids);
        $stmt->execute($params);
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group exams by status and month
        foreach ($exams as $exam) {
            $month_year = date('F Y', strtotime($exam['exam_date']));
            $grouped_exams[$exam['exam_status']][$month_year][] = $exam;
        }
    }
} catch (PDOException $e) {
    error_log("Exams fetch failed: " . $e->getMessage());
    $error_message = "Failed to fetch exam details. Please try again later.";
}
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4 py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h4 class="mb-3">Examinations</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../student_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Examinations</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($subjects)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="fas fa-book-open text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mb-3">No Courses Found</h5>
                    <p class="text-muted mb-0">You are not enrolled in any courses yet. Please contact your administrator.</p>
                </div>
            </div>
        <?php elseif (empty($exams)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mb-3">No Exams Scheduled</h5>
                    <p class="text-muted mb-0">No exams have been scheduled for your courses yet. Check back later.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Today's Exams -->
            <?php if (!empty($grouped_exams['today'])): ?>
                <div class="card border-danger border-start border-4 shadow-sm mb-4">
                    <div class="card-header bg-danger bg-opacity-10 border-0">
                        <h5 class="card-title mb-0 text-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>Today's Exams
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($grouped_exams['today'] as $month => $month_exams): ?>
                            <?php foreach ($month_exams as $exam): ?>
                                <div class="alert alert-danger bg-danger bg-opacity-10 mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                            <p class="mb-0">
                                                <i class="fas fa-book me-1"></i>
                                                <?php echo htmlspecialchars($exam['subject_name']); ?>
                                                (<?php echo htmlspecialchars($exam['subject_code']); ?>)
                                            </p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="mb-0">
                                                <i class="fas fa-graduation-cap me-1"></i>
                                                <?php echo htmlspecialchars($exam['course_name']); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-3 text-md-end">
                                            <span class="badge bg-danger">
                                                <i class="fas fa-star me-1"></i>
                                                <?php echo $exam['total_marks']; ?> Marks
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Upcoming Exams -->
            <?php if (!empty($grouped_exams['upcoming'])): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary bg-opacity-10 border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Upcoming Exams
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($grouped_exams['upcoming'] as $month => $month_exams): ?>
                            <h6 class="text-primary mb-3"><?php echo $month; ?></h6>
                            <?php foreach ($month_exams as $exam): ?>
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="calendar-date me-3 text-center">
                                                        <div class="bg-white rounded p-2 shadow-sm">
                                                            <small class="text-primary"><?php echo date('M', strtotime($exam['exam_date'])); ?></small>
                                                            <h4 class="mb-0"><?php echo date('d', strtotime($exam['exam_date'])); ?></h4>
                                                            <small class="text-muted"><?php echo date('D', strtotime($exam['exam_date'])); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                                <p class="mb-0 small">
                                                    <i class="fas fa-book me-1"></i>
                                                    <?php echo htmlspecialchars($exam['subject_name']); ?>
                                                    (<?php echo htmlspecialchars($exam['subject_code']); ?>)
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-graduation-cap me-1"></i>
                                                    <?php echo htmlspecialchars($exam['course_name']); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-md-end">
                                                <div class="mb-2">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-star me-1"></i>
                                                        <?php echo $exam['total_marks']; ?> Marks
                                                    </span>
                                                </div>
                                                <?php 
                                                $days_until = (strtotime($exam['exam_date']) - time()) / (60 * 60 * 24);
                                                if ($days_until <= 7): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo ceil($days_until); ?> days left
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Past Exams -->
            <?php if (!empty($grouped_exams['past'])): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary bg-opacity-10 border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Past Exams
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Subject</th>
                                        <th>Exam</th>
                                        <th>Marks</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grouped_exams['past'] as $month => $month_exams): ?>
                                        <tr class="table-light">
                                            <td colspan="5" class="fw-bold"><?php echo $month; ?></td>
                                        </tr>
                                        <?php foreach ($month_exams as $exam): ?>
                                            <tr>
                                                <td class="text-nowrap">
                                                    <?php echo date('d M Y', strtotime($exam['exam_date'])); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($exam['subject_code']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($exam['subject_name']); ?>
                                                    </small>
                                                </td>
                                                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo $exam['total_marks']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($exam['result_id']): ?>
                                                        <a href="results.php" class="btn btn-sm btn-outline-primary">
                                                            View Result
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.calendar-date h4 {
    font-size: 24px;
    line-height: 1;
    margin: 5px 0;
    color: var(--bs-primary);
}

.table > :not(:first-child) {
    border-top: none;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}
</style>
