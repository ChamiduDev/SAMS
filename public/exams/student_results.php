<?php
require_once '../includes/header.php';
$pdo = get_pdo_connection();

// Get student information
$stmt = $pdo->prepare("
    SELECT s.*, u.email 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.user_id = ? AND s.status = 'active'
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    set_message('error', 'Student record not found.');
    header('Location: ' . BASE_URL . 'public/dashboard.php');
    exit();
}

// Fetch all exam results for the student
$stmt = $pdo->prepare("
    SELECT 
        e.title as exam_name,
        e.exam_date,
        e.total_marks,
        eg.obtained_marks,
        s.name as subject_name,
        s.code as subject_code,
        c.name as course_name,
        c.code as course_code,
        (eg.obtained_marks / e.total_marks * 100) as percentage,
        CASE 
            WHEN (eg.obtained_marks / e.total_marks * 100) >= 75 THEN 'A'
            WHEN (eg.obtained_marks / e.total_marks * 100) >= 65 THEN 'B'
            WHEN (eg.obtained_marks / e.total_marks * 100) >= 50 THEN 'C'
            ELSE 'F'
        END as grade
    FROM exam_grades eg
    JOIN exams e ON eg.exam_id = e.id
    JOIN subjects s ON e.subject_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE eg.student_id = ?
    ORDER BY e.exam_date DESC, s.name ASC
");
$stmt->execute([$student['id']]);
$exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group results by subject for better organization
$results_by_subject = [];
foreach ($exam_results as $result) {
    $subject_key = $result['subject_name'];
    if (!isset($results_by_subject[$subject_key])) {
        $results_by_subject[$subject_key] = [
            'subject_name' => $result['subject_name'],
            'subject_code' => $result['subject_code'],
            'course_name' => $result['course_name'],
            'total_marks' => 0,
            'obtained_marks' => 0,
            'exams' => []
        ];
    }
    $results_by_subject[$subject_key]['exams'][] = $result;
    $results_by_subject[$subject_key]['total_marks'] += $result['total_marks'];
    $results_by_subject[$subject_key]['obtained_marks'] += $result['obtained_marks'];
}
?>

<div class="container-fluid px-4">
    <!-- Student Info Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                    <p class="mb-0 text-muted">
                        Student ID: <?php echo htmlspecialchars($student['student_id']); ?> | 
                        Year: <?php echo htmlspecialchars($student['year']); ?>
                    </p>
                </div>
                <a href="<?php echo BASE_URL; ?>public/dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Results Overview -->
    <div class="row">
        <?php if (empty($exam_results)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No exam results available yet.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($results_by_subject as $subject_data): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <?php echo htmlspecialchars($subject_data['subject_name']); ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($subject_data['subject_code']); ?>)</small>
                                </h5>
                                <?php
                                $subject_percentage = ($subject_data['total_marks'] > 0) 
                                    ? ($subject_data['obtained_marks'] / $subject_data['total_marks'] * 100)
                                    : 0;
                                ?>
                                <span class="badge bg-<?php echo $subject_percentage >= 75 ? 'success' : ($subject_percentage >= 50 ? 'warning' : 'danger'); ?>">
                                    <?php echo number_format($subject_percentage, 1); ?>%
                                </span>
                            </div>
                            <small class="text-muted"><?php echo htmlspecialchars($subject_data['course_name']); ?></small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Exam</th>
                                            <th>Date</th>
                                            <th>Marks</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subject_data['exams'] as $exam): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($exam['exam_date'])); ?></td>
                                                <td>
                                                    <?php echo $exam['obtained_marks']; ?>/<?php echo $exam['total_marks']; ?>
                                                    (<?php echo number_format($exam['percentage'], 1); ?>%)
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $exam['grade'] === 'A' ? 'success' : ($exam['grade'] === 'F' ? 'danger' : 'warning'); ?>">
                                                        <?php echo $exam['grade']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light fw-bold">
                                            <td colspan="2">Subject Total</td>
                                            <td>
                                                <?php echo $subject_data['obtained_marks']; ?>/<?php echo $subject_data['total_marks']; ?>
                                                (<?php echo number_format($subject_percentage, 1); ?>%)
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $subject_percentage >= 75 ? 'success' : ($subject_percentage >= 50 ? 'warning' : 'danger'); ?>">
                                                    <?php
                                                    if ($subject_percentage >= 75) echo 'A';
                                                    elseif ($subject_percentage >= 65) echo 'B';
                                                    elseif ($subject_percentage >= 50) echo 'C';
                                                    else echo 'F';
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
