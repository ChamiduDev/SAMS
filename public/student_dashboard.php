<?php
require_once __DIR__ . '/includes/student/header.php';
require_once __DIR__ . '/includes/student/sidebar.php';

// Get enrolled courses
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        GROUP_CONCAT(DISTINCT s.name) as subjects,
        COUNT(DISTINCT s.id) as subject_count
    FROM courses c
    JOIN student_courses sc ON c.id = sc.course_id
    LEFT JOIN subjects s ON c.id = s.course_id
    WHERE sc.student_id = ?
    GROUP BY c.id
");
$stmt->execute([$student['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent attendance records
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        s.name as subject_name,
        s.code as subject_code
    FROM attendance a
    JOIN subjects s ON a.subject_id = s.id
    WHERE a.student_id = ?
    ORDER BY a.date DESC
    LIMIT 5
");
$stmt->execute([$student['id']]);
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent exam results
$stmt = $pdo->prepare("
    SELECT 
        e.title as exam_name,
        e.exam_date,
        e.total_marks,
        er.marks_obtained as obtained_marks,
        s.name as subject_name,
        (er.marks_obtained / e.total_marks * 100) as percentage
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    JOIN subjects s ON e.subject_id = s.id
    WHERE er.student_id = ?
    ORDER BY e.exam_date DESC
    LIMIT 5
");
$stmt->execute([$student['id']]);
$recent_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notifications
require_once __DIR__ . '/notifications/get_notifications.php';
$notifications = get_user_notifications($pdo, $_SESSION['user_id'], $_SESSION['role_name'], 5);
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4 py-4">
       

    <!-- Quick Stats -->
    <div class="row mb-4">
        <!-- Courses -->
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Enrolled Courses</h6>
                            <div class="h2 mb-0"><?php echo count($courses); ?></div>
                        </div>
                        <div class="ms-2">
                            <i class="fas fa-graduation-cap fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Attendance -->
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Overall Attendance</h6>
                            <div class="h2 mb-0">
                                <?php
                                $attendance_query = $pdo->prepare("
                                    SELECT 
                                        COUNT(*) as total,
                                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                                    FROM attendance 
                                    WHERE student_id = ?
                                ");
                                $attendance_query->execute([$student['id']]);
                                $attendance_stats = $attendance_query->fetch(PDO::FETCH_ASSOC);
                                
                                $attendance_percentage = 0;
                                if ($attendance_stats['total'] > 0) {
                                    $attendance_percentage = (($attendance_stats['present'] + ($attendance_stats['late'] * 0.5)) / $attendance_stats['total']) * 100;
                                }
                                echo number_format($attendance_percentage, 1) . '%';
                                ?>
                            </div>
                        </div>
                        <div class="ms-2">
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Academic Performance -->
        <div class="col-md-3">
            <div class="card bg-warning text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Average Grade</h6>
                            <div class="h2 mb-0">
                                <?php
                                $grades_query = $pdo->prepare("
                                    SELECT AVG(marks_obtained / total_marks * 100) as avg_grade
                                    FROM exam_results er
                                    JOIN exams e ON er.exam_id = e.id
                                    WHERE student_id = ?
                                ");
                                $grades_query->execute([$student['id']]);
                                $avg_grade = $grades_query->fetchColumn();
                                echo $avg_grade ? number_format($avg_grade, 1) . '%' : 'N/A';
                                ?>
                            </div>
                        </div>
                        <div class="ms-2">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="col-md-3">
            <div class="card bg-danger text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Unread Notifications</h6>
                            <div class="h2 mb-0"><?php echo count($notifications); ?></div>
                        </div>
                        <div class="ms-2">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row mb-4">
        <!-- Recent Attendance -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Attendance</h5>
                        <a href="<?php echo BASE_URL; ?>public/student/attendance.php" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_attendance)): ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-calendar fa-3x mb-3"></i>
                            <p>No recent attendance records</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_attendance as $record): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($record['subject_name']); ?>
                                                <small class="text-muted">(<?php echo htmlspecialchars($record['subject_code']); ?>)</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $record['status'] === 'present' ? 'success' : 
                                                        ($record['status'] === 'late' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Exam Results -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Exam Results</h5>
                        <a href="<?php echo BASE_URL; ?>public/exams/student_results.php" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_exams)): ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-file-alt fa-3x mb-3"></i>
                            <p>No recent exam results</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Subject</th>
                                        <th>Score</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_exams as $exam): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                            <td>
                                                <?php echo $exam['obtained_marks']; ?>/<?php echo $exam['total_marks']; ?>
                                                (<?php echo number_format($exam['percentage'], 1); ?>%)
                                            </td>
                                            <td>
                                                <?php
                                                $grade = '';
                                                if ($exam['percentage'] >= 75) $grade = 'A';
                                                elseif ($exam['percentage'] >= 65) $grade = 'B';
                                                elseif ($exam['percentage'] >= 50) $grade = 'C';
                                                else $grade = 'F';
                                                ?>
                                                <span class="badge bg-<?php 
                                                    echo $grade === 'A' ? 'success' : 
                                                        ($grade === 'F' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo $grade; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Enrolled Courses</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($courses)): ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-books fa-3x mb-3"></i>
                            <p>No courses enrolled</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($courses as $course): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <?php echo htmlspecialchars($course['name']); ?>
                                                <small class="text-muted">(<?php echo htmlspecialchars($course['code']); ?>)</small>
                                            </h6>
                                            <p class="card-text small">
                                                <strong>Subjects:</strong> <?php echo htmlspecialchars($course['subjects']); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary"><?php echo $course['subject_count']; ?> subjects</span>
                                                <a href="student/courses.php" class="btn btn-sm btn-outline-primary">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/student/footer.php'; ?>
