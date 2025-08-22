<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';

$pdo = get_pdo_connection();
$student_id = $student['id']; // Already available from student header

// Get attendance summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance 
    WHERE student_id = ?
");
$stmt->execute([$student_id]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize summary with defaults if no data
if (!$summary) {
    $summary = [
        'total_days' => 0,
        'present_days' => 0,
        'late_days' => 0,
        'absent_days' => 0
    ];
}

// Calculate percentages
$attendance_percentage = $summary['total_days'] > 0 
    ? round((($summary['present_days'] + ($summary['late_days'] * 0.5)) / $summary['total_days']) * 100, 1)
    : 0;

// Get monthly attendance
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance 
    WHERE student_id = ?
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute([$student_id]);
$monthly_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    LIMIT 10
");
$stmt->execute([$student_id]);
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div id="page-content-wrapper">
    <div class="container mt-5">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">
                <i class="fas fa-calendar-check text-primary me-2"></i>Attendance Record
            </h2>
        </div>

        <!-- Attendance Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Overall Attendance</h6>
                        <div class="display-4 mb-2"><?php echo $attendance_percentage; ?>%</div>
                        <small class="d-block">
                            <?php echo $summary['total_days']; ?> Total Days
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Present Days</h6>
                        <div class="display-4 mb-2"><?php echo $summary['present_days']; ?></div>
                        <small class="d-block">
                            <?php echo $summary['total_days'] > 0 ? round(($summary['present_days'] / $summary['total_days']) * 100, 1) : 0; ?>% of Total
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Late Days</h6>
                        <div class="display-4 mb-2"><?php echo $summary['late_days']; ?></div>
                        <small class="d-block">
                            <?php echo $summary['total_days'] > 0 ? round(($summary['late_days'] / $summary['total_days']) * 100, 1) : 0; ?>% of Total
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Absent Days</h6>
                        <div class="display-4 mb-2"><?php echo $summary['absent_days']; ?></div>
                        <small class="d-block">
                            <?php echo $summary['total_days'] > 0 ? round(($summary['absent_days'] / $summary['total_days']) * 100, 1) : 0; ?>% of Total
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Attendance & Recent Records -->
        <div class="row">
            <!-- Monthly Attendance -->
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Monthly Attendance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Present</th>
                                        <th>Late</th>
                                        <th>Absent</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_attendance as $month): ?>
                                        <tr>
                                            <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo $month['present_days']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    <?php echo $month['late_days']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?php echo $month['absent_days']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $monthly_percentage = $month['total_days'] > 0 ? round(
                                                    (($month['present_days'] + ($month['late_days'] * 0.5)) / $month['total_days']) * 100, 
                                                    1
                                                ) : 0;
                                                $badge_class = $monthly_percentage >= 75 ? 'bg-success' : 
                                                    ($monthly_percentage >= 60 ? 'bg-warning' : 'bg-danger');
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo $monthly_percentage; ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Attendance</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_attendance as $record): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <?php
                                            $status_class = $record['status'] === 'present' ? 'bg-success' : 
                                                ($record['status'] === 'late' ? 'bg-warning' : 'bg-danger');
                                            ?>
                                            <span class="badge <?php echo $status_class; ?> rounded-circle p-2">
                                                <i class="fas fa-<?php 
                                                    echo $record['status'] === 'present' ? 'check' : 
                                                        ($record['status'] === 'late' ? 'clock' : 'times'); 
                                                ?>"></i>
                                            </span>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($record['subject_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('l, F j, Y', strtotime($record['date'])); ?>
                                            </small>
                                        </div>
                                        <div class="ms-auto">
                                            <span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($record['subject_code']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
}

.display-4 {
    font-size: 2.5rem;
    font-weight: 300;
    line-height: 1.2;
}

.badge {
    font-weight: 500;
}

.list-group-item:hover {
    background-color: rgba(0, 0, 0, 0.01);
}
</style>


