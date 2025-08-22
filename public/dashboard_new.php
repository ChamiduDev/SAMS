<?php
require_once 'includes/header.php';
$pdo = get_pdo_connection();

// Get user role
$stmt = $pdo->prepare("SELECT r.name FROM roles r JOIN users u ON r.id = u.role_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_role = $stmt->fetchColumn();

// Initialize variables
$student = null;
$exam_results = [];
$pending_exams = [];
$payments = [];
$notifications = [];
$error = null;

// Include notifications functions
require_once __DIR__ . '/notifications/get_notifications.php';

// Fetch data based on user role
if ($user_role === 'student') {
    try {
        // Get student details
        $stmt = $pdo->prepare("
            SELECT s.*, u.email, u.username,
                   GROUP_CONCAT(DISTINCT c.name) as course_names,
                   GROUP_CONCAT(DISTINCT sub.name) as subject_names,
                   COUNT(DISTINCT c.id) as course_count,
                   COUNT(DISTINCT sub.id) as subject_count
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN student_course_subjects scs ON s.id = scs.student_id
            LEFT JOIN courses c ON scs.course_id = c.id
            LEFT JOIN subjects sub ON scs.subject_id = sub.id
            WHERE s.user_id = ? AND s.status = 'active'
            GROUP BY s.id
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Fetch exam results
            $stmt = $pdo->prepare("
                SELECT e.title as exam_name, eg.obtained_marks, e.total_marks, 
                       e.exam_date, s.name as subject_name, c.name as course_name
                FROM exam_grades eg
                JOIN exams e ON eg.exam_id = e.id
                JOIN subjects s ON e.subject_id = s.id
                JOIN courses c ON s.course_id = c.id
                WHERE eg.student_id = ?
                ORDER BY e.exam_date DESC
            ");
            $stmt->execute([$student['id']]);
            $exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch pending exams
            $stmt = $pdo->prepare("
                SELECT e.title, e.exam_date, e.total_marks, e.duration,
                       s.name as subject_name, c.name as course_name
                FROM exams e
                JOIN subjects s ON e.subject_id = s.id
                JOIN courses c ON s.course_id = c.id
                WHERE e.subject_id IN (
                    SELECT DISTINCT scs.subject_id 
                    FROM student_course_subjects scs 
                    WHERE scs.student_id = ?
                )
                AND e.exam_date >= CURDATE()
                AND NOT EXISTS (
                    SELECT 1 FROM exam_grades eg 
                    WHERE eg.exam_id = e.id 
                    AND eg.student_id = ?
                )
                ORDER BY e.exam_date ASC
            ");
            $stmt->execute([$student['id'], $student['id']]);
            $pending_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch payments
            $stmt = $pdo->prepare("
                SELECT p.amount, p.payment_date, p.status, 
                       ft.name as fee_type, fs.description,
                       fs.total_amount as fee_structure_amount
                FROM payments p
                JOIN fee_types ft ON p.fee_type_id = ft.id
                LEFT JOIN fee_structures fs ON ft.id = fs.fee_type_id
                WHERE p.student_id = ?
                ORDER BY p.payment_date DESC
            ");
            $stmt->execute([$student['id']]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Fetch notifications
        $notifications = get_user_notifications($pdo, $_SESSION['user_id'], $_SESSION['role_name'], 5);
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
} else {
    try {
        // Admin dashboard stats
        $total_students = $pdo->query("SELECT COUNT(id) FROM students")->fetchColumn();
        $total_teachers = $pdo->prepare("SELECT COUNT(u.id) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = ?")->execute(['teacher'])->fetchColumn();
        $total_courses = $pdo->query("SELECT COUNT(id) FROM courses")->fetchColumn();

        // Today's attendance
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
            FROM attendance
            WHERE date = CURRENT_DATE
        ");
        $stmt->execute();
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        $attendance_percentage = $attendance['total'] > 0 ? ($attendance['present'] / $attendance['total'] * 100) : 0;

        // Fetch notifications
        $notifications = get_user_notifications($pdo, $_SESSION['user_id'], $_SESSION['role_name'], 5);
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Include sidebar
require_once 'includes/sidebar.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($user_role === 'student' && $student): ?>
            <!-- Student Dashboard -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-primary text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1">Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</h4>
                                    <p class="mb-0">Student ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                                </div>
                                <div class="text-end">
                                    <div class="h6 mb-0">Academic Year</div>
                                    <div class="h4 mb-0"><?php echo htmlspecialchars($student['year']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <!-- Course Stats -->
                <div class="col-md-3">
                    <div class="card bg-success text-white shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Enrolled Courses</h6>
                                    <div class="h2 mb-0"><?php echo (int)$student['course_count']; ?></div>
                                </div>
                                <div class="ms-2">
                                    <i class="fas fa-graduation-cap fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam Stats -->
                <div class="col-md-3">
                    <div class="card bg-warning text-white shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Upcoming Exams</h6>
                                    <div class="h2 mb-0"><?php echo count($pending_exams); ?></div>
                                </div>
                                <div class="ms-2">
                                    <i class="fas fa-file-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Stats -->
                <div class="col-md-3">
                    <div class="card bg-info text-white shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Attendance</h6>
                                    <div class="h2 mb-0">
                                        <?php
                                        $attendance_query = $pdo->prepare("
                                            SELECT 
                                                COUNT(*) as total,
                                                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                                            FROM attendance 
                                            WHERE student_id = ?
                                        ");
                                        $attendance_query->execute([$student['id']]);
                                        $attendance_stats = $attendance_query->fetch(PDO::FETCH_ASSOC);
                                        
                                        $attendance_percentage = $attendance_stats['total'] > 0 
                                            ? round(($attendance_stats['present'] / $attendance_stats['total']) * 100) 
                                            : 0;
                                        echo $attendance_percentage . '%';
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

                <!-- Notifications -->
                <div class="col-md-3">
                    <div class="card bg-danger text-white shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Notifications</h6>
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

            <!-- Exam Results -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Exam Results</h5>
                            <a href="exams/student_results.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($exam_results)): ?>
                                <p class="text-muted">No exam results available.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Exam</th>
                                                <th>Date</th>
                                                <th>Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($exam_results, 0, 5) as $result): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($result['exam_date'])); ?></td>
                                                    <td>
                                                        <?php 
                                                        $percentage = ($result['obtained_marks'] / $result['total_marks']) * 100;
                                                        $grade_class = $percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="badge bg-<?php echo $grade_class; ?>">
                                                            <?php echo $result['obtained_marks']; ?>/<?php echo $result['total_marks']; ?>
                                                            (<?php echo number_format($percentage, 1); ?>%)
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

                <div class="col-lg-4">
                    <!-- Notifications Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Recent Notifications</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($notifications)): ?>
                                <p class="text-muted">No notifications available.</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item mb-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upcoming Exams -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Upcoming Exams</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pending_exams)): ?>
                                <p class="text-muted">No upcoming exams.</p>
                            <?php else: ?>
                                <?php foreach (array_slice($pending_exams, 0, 3) as $exam): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="date-box text-center">
                                                <span class="d-block text-muted small">
                                                    <?php echo date('M', strtotime($exam['exam_date'])); ?>
                                                </span>
                                                <span class="d-block h4 mb-0">
                                                    <?php echo date('d', strtotime($exam['exam_date'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                            <p class="mb-0 small text-muted">
                                                <?php echo htmlspecialchars($exam['subject_name']); ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="far fa-clock"></i>
                                                Duration: <?php echo $exam['duration']; ?> mins
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="row mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-0">Total Students</h6>
                                    <div class="h2 mb-0"><?php echo number_format($total_students); ?></div>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-user-graduate fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-0">Total Teachers</h6>
                                    <div class="h2 mb-0"><?php echo number_format($total_teachers); ?></div>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-chalkboard-teacher fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-0">Total Courses</h6>
                                    <div class="h2 mb-0"><?php echo number_format($total_courses); ?></div>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-book fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-0">Today's Attendance</h6>
                                    <div class="h2 mb-0"><?php echo number_format($attendance_percentage, 1); ?>%</div>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-calendar-check fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Access Buttons -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Quick Access</h5>
                            <div class="row g-3">
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="students/list.php" class="text-decoration-none">
                                        <div class="p-3 bg-light text-center rounded">
                                            <i class="fas fa-user-graduate fa-2x text-primary mb-2"></i>
                                            <div class="text-dark">Students</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="attendance/manage.php" class="text-decoration-none">
                                        <div class="p-3 bg-light text-center rounded">
                                            <i class="fas fa-clipboard-check fa-2x text-success mb-2"></i>
                                            <div class="text-dark">Attendance</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="exams/manage.php" class="text-decoration-none">
                                        <div class="p-3 bg-light text-center rounded">
                                            <i class="fas fa-file-alt fa-2x text-warning mb-2"></i>
                                            <div class="text-dark">Exams</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="courses/manage.php" class="text-decoration-none">
                                        <div class="p-3 bg-light text-center rounded">
                                            <i class="fas fa-book fa-2x text-info mb-2"></i>
                                            <div class="text-dark">Courses</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="fees/manage.php" class="text-decoration-none">
                                        <div class="p-3 bg-light text-center rounded">
                                            <i class="fas fa-money-bill fa-2x text-danger mb-2"></i>
                                            <div class="text-dark">Fees</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="reports/index.php" class="text-decoration-none">
                                        <div class="p-3 bg-light text-center rounded">
                                            <i class="fas fa-chart-bar fa-2x text-secondary mb-2"></i>
                                            <div class="text-dark">Reports</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Notifications</h5>
                        <a href="notifications/list.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted">No notifications available.</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item mb-3">
                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($notification['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
