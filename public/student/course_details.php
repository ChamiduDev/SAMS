<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';
$pdo = get_pdo_connection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: courses.php');
    exit;
}

try {
    // Get course and enrollment details
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            sc.enrollment_date,
            sc.status as enrollment_status
        FROM courses c
        JOIN student_courses sc ON c.id = sc.course_id
        JOIN students s ON sc.student_id = s.id
        WHERE c.id = ? AND s.user_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        header('Location: courses.php');
        exit;
    }

    // Get subjects with detailed information
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            (
                SELECT COUNT(DISTINCT e.id)
                FROM exams e
                WHERE e.subject_id = s.id
                AND e.exam_date >= CURDATE()
            ) as upcoming_exams,
            (
                SELECT COUNT(DISTINCT a.date)
                FROM attendance a
                WHERE a.student_id = sc.student_id
                AND a.subject_id = s.id
            ) as total_classes,
            (
                SELECT COUNT(DISTINCT a.date)
                FROM attendance a
                WHERE a.student_id = sc.student_id
                AND a.subject_id = s.id
                AND a.status = 'present'
            ) as classes_attended,
            (
                SELECT ROUND(AVG(g.marks_obtained / g.total_marks * 100), 1)
                FROM exam_grades g
                JOIN exams e ON g.exam_id = e.id
                WHERE e.subject_id = s.id
                AND g.student_id = sc.student_id
            ) as average_grade
        FROM subjects s
        JOIN student_courses sc ON sc.course_id = s.course_id
        JOIN students st ON sc.student_id = st.id
        WHERE s.course_id = ? AND st.user_id = ?
        ORDER BY s.name
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Course details fetch failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

require_once '../includes/student_sidebar.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h4 class="mb-3">Course Details</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../student_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="courses.php">Courses</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($course['code']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Course Information -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($course['name']); ?>
                            <small class="text-muted">(<?php echo htmlspecialchars($course['code']); ?>)</small>
                        </h5>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($course['description']); ?></p>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="mb-2">Enrollment Details</h6>
                                    <p class="mb-1">
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                                        Enrolled: <?php echo date('F j, Y', strtotime($course['enrollment_date'])); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        Status: 
                                        <span class="badge bg-<?php echo $course['enrollment_status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($course['enrollment_status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <h6 class="mb-3">Course Overview</h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Total Subjects:</span>
                                <span class="badge bg-primary"><?php echo count($subjects); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Total Credits:</span>
                                <span class="badge bg-info">
                                    <?php 
                                    echo array_reduce($subjects, function($carry, $subject) {
                                        return $carry + $subject['credits'];
                                    }, 0);
                                    ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Upcoming Exams:</span>
                                <span class="badge bg-warning">
                                    <?php 
                                    echo array_reduce($subjects, function($carry, $subject) {
                                        return $carry + $subject['upcoming_exams'];
                                    }, 0);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subjects List -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Course Subjects</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Subject Details</th>
                                <th>Credits</th>
                                <th>Attendance</th>
                                <th>Performance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $subject): ?>
                                <?php 
                                $attendance_percentage = $subject['total_classes'] > 0 
                                    ? round(($subject['classes_attended'] / $subject['total_classes']) * 100, 1)
                                    : 0;
                                ?>
                                <tr>
                                    <td style="min-width: 250px;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
                                            <small class="text-muted d-block">
                                                Code: <?php echo htmlspecialchars($subject['code']); ?>
                                            </small>
                                            <?php if ($subject['description']): ?>
                                                <small class="text-muted d-block">
                                                    <?php echo htmlspecialchars($subject['description']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $subject['credits']; ?> Credits
                                        </span>
                                    </td>
                                    <td style="min-width: 150px;">
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="flex-grow-1 me-2">
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar <?php 
                                                        echo $attendance_percentage >= 75 ? 'bg-success' : 
                                                            ($attendance_percentage >= 60 ? 'bg-warning' : 'bg-danger'); 
                                                        ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $attendance_percentage; ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="small">
                                                <?php echo $attendance_percentage; ?>%
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $subject['classes_attended']; ?>/<?php echo $subject['total_classes']; ?> Classes
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($subject['average_grade'] !== null): ?>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <span class="badge bg-<?php 
                                                        echo $subject['average_grade'] >= 80 ? 'success' : 
                                                            ($subject['average_grade'] >= 60 ? 'warning' : 'danger'); 
                                                        ?>">
                                                        <?php echo $subject['average_grade']; ?>%
                                                    </span>
                                                </div>
                                                <small class="text-muted">Average</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No Grades</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="attendance.php?subject=<?php echo $subject['id']; ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-calendar-check me-1"></i>Attendance
                                            </a>
                                            <a href="exams.php?subject=<?php echo $subject['id']; ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-file-alt me-1"></i>Exams
                                            </a>
                                            <a href="results.php?subject=<?php echo $subject['id']; ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-chart-line me-1"></i>Results
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress {
    background-color: #e9ecef;
    border-radius: 3px;
}

.btn-group .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

.table > :not(caption) > * > * {
    padding: 1rem;
}

.badge {
    font-weight: 500;
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
}
</style>

<?php require_once '../includes/footer.php'; ?>
