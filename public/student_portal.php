<?php
require_once '../config/config.php';
require_once '../config/utils.php';

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['student_portal_id']);
    header('Location: student_portal.php');
    exit;
}

$error = '';
$student = null;
$exam_results = [];
$pending_exams = [];
$payments = [];
$notifications = [];
$timetable = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_SESSION['student_portal_id'])) {
    $pdo = get_pdo_connection();
    
    $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : null;
    $stored_student_id = isset($_SESSION['student_portal_id']) ? $_SESSION['student_portal_id'] : null;
    
    try {
        if ($student_id) {
            // Debug: Log the student_id being searched
            error_log("Searching for student_id: " . $student_id);
            
            $stmt = $pdo->prepare("SELECT s.*, c.name as course_name, u.email 
                                 FROM students s 
                                 LEFT JOIN student_courses sc ON s.id = sc.student_id 
                                 LEFT JOIN courses c ON sc.course_id = c.id
                                 LEFT JOIN users u ON s.user_id = u.id
                                 WHERE s.student_id = ? AND s.status = 'active'");
            $stmt->execute([$student_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($stored_student_id) {
            // Debug: Log the stored_student_id being searched
            error_log("Searching for stored_student_id: " . $stored_student_id);
            
            $stmt = $pdo->prepare("SELECT s.*, c.name as course_name, u.email
                                 FROM students s 
                                 LEFT JOIN student_courses sc ON s.id = sc.student_id 
                                 LEFT JOIN courses c ON sc.course_id = c.id
                                 LEFT JOIN users u ON s.user_id = u.id
                                 WHERE s.id = ? AND s.status = 'active'");
            $stmt->execute([$stored_student_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Debug: Log the query result
        error_log("Query result: " . print_r($result, true));
        
        if ($result) {
            $student = $result;
            $_SESSION['student_portal_id'] = $student['id'];
            
            // Fetch exam results
            $stmt = $pdo->prepare("
                SELECT e.title as exam_name, eg.marks_obtained as obtained_marks, e.total_marks, e.exam_date,
                       s.name as subject_name
                FROM exam_grades eg
                JOIN exams e ON eg.exam_id = e.id
                JOIN subjects s ON e.subject_id = s.id
                WHERE eg.student_id = ?
                ORDER BY e.exam_date DESC
            ");
            $stmt->execute([$student['id']]);
            $exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch pending exams
            $stmt = $pdo->prepare("
                SELECT e.title, e.exam_date, e.total_marks, e.duration,
                       s.name as subject_name
                FROM exams e
                JOIN subjects s ON e.subject_id = s.id
                WHERE e.subject_id IN (
                    SELECT DISTINCT subject_id 
                    FROM student_subjects 
                    WHERE student_id = ?
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
            
            // Fetch payment details with fee structures
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
            
            // Fetch recent notifications
            $stmt = $pdo->prepare("
                SELECT DISTINCT n.*
                FROM notifications n
                LEFT JOIN student_notifications sn ON n.id = sn.notification_id
                WHERE (n.audience = 'all')
                   OR (n.audience = 'specific' AND sn.student_id = ?)
                ORDER BY n.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$student['id']]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } else {
            $error = "Invalid Student ID. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Database error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - SAMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --background-color: #f5f6fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-primary);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .portal-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
        }

        .student-form {
            max-width: 500px;
            margin: 100px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: box-shadow 0.3s ease;
        }

        .student-form:hover {
            box-shadow: var(--hover-shadow);
        }

        .dashboard-card {
            background-color: #fff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .dashboard-card:hover {
            box-shadow: var(--hover-shadow);
            transform: translateY(-2px);
        }

        .card-header {
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
            color: var(--primary-color);
        }

        .card-header i {
            color: var(--secondary-color);
            margin-right: 10px;
        }

        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }

        .notification-item:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending { 
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status-completed { 
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .exam-date { 
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .nav-pills .nav-link.active {
            background-color: var(--secondary-color);
        }

        .logout-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 8px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            margin-top: 8px;
        }

        .progress-bar {
            background-color: var(--secondary-color);
        }

        .list-group-item {
            border: none;
            padding: 12px 20px;
            margin-bottom: 5px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            background-color: rgba(52, 152, 219, 0.1);
            transform: translateX(5px);
        }

        .list-group-item i {
            color: var(--secondary-color);
            margin-right: 10px;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .student-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--card-shadow);
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: bold;
            color: var(--secondary-color);
            margin: 10px 0;
        }

        .stat-card .label {
            color: var(--text-secondary);
            font-size: 0.9em;
        }
    </style>
</head>
<body class="bg-light">
    <div class="portal-container">
        <?php if (defined('DEBUG') && DEBUG): ?>
            <div class="debug-info" style="margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                <h5>Debug Information:</h5>
                <pre><?php 
                    echo "POST data: " . print_r($_POST, true) . "\n";
                    echo "Session data: " . print_r($_SESSION, true) . "\n";
                    echo "Student data: " . print_r($student, true) . "\n";
                ?></pre>
            </div>
        <?php endif; ?>

        <?php if (!$student): ?>
            <div class="student-form">
                <h2 class="text-center mb-4">Student Portal Login</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group mb-3">
                        <label for="student_id" class="form-label">Enter your Student ID:</label>
                        <input type="text" id="student_id" name="student_id" required class="form-control form-control-lg">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Access Portal</button>
                </form>
                <p class="text-center mt-3"><a href="index.php">&larr; Back to Home</a></p>
            </div>
        <?php else: ?>
            <a href="student_portal.php?logout=1" class="btn btn-outline-primary logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <h2 class="mb-3"><i class="fas fa-user-graduate"></i> Welcome, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                <div class="d-flex justify-content-between align-items-center">
                    <p class="mb-0 fs-5">
                        <span class="badge bg-light text-dark me-2">ID: <?php echo htmlspecialchars($student['student_id']); ?></span>
                        <span class="badge bg-light text-dark">Class: <?php echo htmlspecialchars($student['class']); ?></span>
                    </p>
                    <p class="mb-0 fs-5">
                        <span class="badge bg-light text-dark">Year: <?php echo htmlspecialchars($student['year']); ?></span>
                    </p>
                </div>
            </div>

            <!-- Student Stats -->
            <div class="student-stats mb-4">
                <div class="stat-card">
                    <i class="fas fa-book fa-2x text-primary"></i>
                    <div class="number"><?php echo count($exam_results); ?></div>
                    <div class="label">Exams Completed</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check fa-2x text-success"></i>
                    <div class="number"><?php echo count($pending_exams); ?></div>
                    <div class="label">Upcoming Exams</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-bell fa-2x text-warning"></i>
                    <div class="number"><?php echo count($notifications); ?></div>
                    <div class="label">Notifications</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-money-bill-wave fa-2x text-info"></i>
                    <div class="number"><?php echo count($payments); ?></div>
                    <div class="label">Payments Made</div>
                </div>
            </div>

            <div class="row">
                <!-- Student Information -->
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h4><i class="fas fa-user"></i> Personal Information</h4>
                            <button class="btn btn-sm btn-outline-primary" title="Edit Profile">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="student-info">
                            <div class="info-item mb-3">
                                <label class="text-muted mb-1">Full Name</label>
                                <p class="mb-0 fs-5"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                            </div>
                            <div class="info-item mb-3">
                                <label class="text-muted mb-1">Student ID</label>
                                <p class="mb-0"><?php echo htmlspecialchars($student['student_id']); ?></p>
                            </div>
                            <div class="info-item mb-3">
                                <label class="text-muted mb-1">Class</label>
                                <p class="mb-0"><?php echo htmlspecialchars($student['class']); ?></p>
                            </div>
                            <div class="info-item mb-3">
                                <label class="text-muted mb-1">Contact</label>
                                <p class="mb-0"><?php echo htmlspecialchars($student['contact_no'] ?? 'Not provided'); ?></p>
                            </div>
                            <div class="info-item">
                                <label class="text-muted mb-1">Address</label>
                                <p class="mb-0"><?php echo htmlspecialchars($student['address'] ?? 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h4><i class="fas fa-bell"></i> Recent Notifications</h4>
                        </div>
                        <?php if (empty($notifications)): ?>
                            <p>No recent notifications</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item">
                                    <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($notification['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-end mt-3">
                                <a href="student_notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Exam Results and Pending Exams -->
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h4><i class="fas fa-chart-bar"></i> Recent Exam Results</h4>
                        </div>
                        <?php if (empty($exam_results)): ?>
                            <p>No exam results available</p>
                        <?php else: ?>
                            <?php 
                            $total_percentage = 0;
                            $exam_count = count($exam_results);
                            foreach ($exam_results as $result): 
                                $percentage = ($result['obtained_marks'] / $result['total_marks'] * 100);
                                $total_percentage += $percentage;
                            ?>
                                <div class="exam-result mb-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong class="d-block"><?php echo htmlspecialchars($result['exam_name']); ?></strong>
                                            <small class="text-muted"><?php echo htmlspecialchars($result['subject_name']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo ($percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger')); ?> p-2">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>Score: <?php echo $result['obtained_marks']; ?>/<?php echo $result['total_marks']; ?></div>
                                        <div class="exam-date"><?php echo date('M j, Y', strtotime($result['exam_date'])); ?></div>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo ($percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger')); ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%"
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="average-score text-center pt-3 border-top">
                                <h5>Average Performance</h5>
                                <div class="h2 mb-0 <?php echo (($total_percentage/$exam_count) >= 75 ? 'text-success' : (($total_percentage/$exam_count) >= 50 ? 'text-warning' : 'text-danger')); ?>">
                                    <?php echo number_format($total_percentage/$exam_count, 1); ?>%
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h4><i class="fas fa-calendar-alt"></i> Upcoming Exams</h4>
                        </div>
                        <?php if (empty($pending_exams)): ?>
                            <p>No upcoming exams</p>
                        <?php else: ?>
                            <?php foreach ($pending_exams as $exam): ?>
                                <div class="mb-3">
                                    <strong><?php echo htmlspecialchars($exam['title']); ?></strong>
                                    <div>Date: <?php echo date('M j, Y', strtotime($exam['exam_date'])); ?></div>
                                    <div>Total Marks: <?php echo $exam['total_marks']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payments -->
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h4><i class="fas fa-money-bill"></i> Payment History</h4>
                        </div>
                        <?php if (empty($payments)): ?>
                            <p>No payment records found</p>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($payment['fee_type']); ?></strong>
                                        <span class="status-badge <?php echo $payment['status'] == 'completed' ? 'status-completed' : 'status-pending'; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </div>
                                    <div>Amount: Rs. <?php echo number_format($payment['amount'], 2); ?></div>
                                    <small class="text-muted">Paid on: <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Links -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h4><i class="fas fa-link"></i> Quick Links</h4>
                        </div>
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar"></i> View Full Timetable
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-alt"></i> Course Materials
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-comments"></i> Student Forum
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
