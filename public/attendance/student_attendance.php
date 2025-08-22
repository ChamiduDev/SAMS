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

// Fetch attendance records grouped by subject
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        s.name as subject_name,
        s.code as subject_code,
        c.name as course_name,
        c.code as course_code
    FROM attendance a
    JOIN subjects s ON a.subject_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE a.student_id = ?
    ORDER BY a.date DESC
");
$stmt->execute([$student['id']]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group attendance by subject
$attendance_by_subject = [];
foreach ($attendance_records as $record) {
    $subject_key = $record['subject_name'];
    if (!isset($attendance_by_subject[$subject_key])) {
        $attendance_by_subject[$subject_key] = [
            'subject_name' => $record['subject_name'],
            'subject_code' => $record['subject_code'],
            'course_name' => $record['course_name'],
            'total_classes' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'records' => []
        ];
    }
    $attendance_by_subject[$subject_key]['records'][] = $record;
    $attendance_by_subject[$subject_key]['total_classes']++;
    
    switch ($record['status']) {
        case 'present':
            $attendance_by_subject[$subject_key]['present']++;
            break;
        case 'absent':
            $attendance_by_subject[$subject_key]['absent']++;
            break;
        case 'late':
            $attendance_by_subject[$subject_key]['late']++;
            break;
    }
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

    <!-- Attendance Overview -->
    <div class="row">
        <?php if (empty($attendance_records)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No attendance records available yet.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($attendance_by_subject as $subject_data): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <?php echo htmlspecialchars($subject_data['subject_name']); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($subject_data['subject_code']); ?>)</small>
                            </h5>
                            <small class="text-muted"><?php echo htmlspecialchars($subject_data['course_name']); ?></small>
                        </div>
                        <div class="card-body">
                            <!-- Attendance Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-3 text-center">
                                    <div class="h5 mb-0 text-success"><?php echo $subject_data['present']; ?></div>
                                    <div class="small text-muted">Present</div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="h5 mb-0 text-danger"><?php echo $subject_data['absent']; ?></div>
                                    <div class="small text-muted">Absent</div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="h5 mb-0 text-warning"><?php echo $subject_data['late']; ?></div>
                                    <div class="small text-muted">Late</div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <?php 
                                    $attendance_percentage = ($subject_data['present'] + ($subject_data['late'] * 0.5)) / $subject_data['total_classes'] * 100;
                                    ?>
                                    <div class="h5 mb-0 text-primary"><?php echo number_format($attendance_percentage, 1); ?>%</div>
                                    <div class="small text-muted">Attendance</div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress mb-4" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo ($subject_data['present'] / $subject_data['total_classes'] * 100); ?>%" 
                                     title="Present"></div>
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: <?php echo ($subject_data['late'] / $subject_data['total_classes'] * 100); ?>%" 
                                     title="Late"></div>
                                <div class="progress-bar bg-danger" role="progressbar" 
                                     style="width: <?php echo ($subject_data['absent'] / $subject_data['total_classes'] * 100); ?>%" 
                                     title="Absent"></div>
                            </div>

                            <!-- Recent Attendance Records -->
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $recent_records = array_slice($subject_data['records'], 0, 5);
                                        foreach ($recent_records as $record): 
                                        ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $record['status'] === 'present' ? 'success' : 
                                                            ($record['status'] === 'late' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo ($record['notes'] ?? '') ? htmlspecialchars($record['notes']) : '-'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
