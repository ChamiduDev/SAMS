<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$exam_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$exam_id) {
    set_message('error', 'Invalid exam ID.');
    header('Location: list.php');
    exit();
}

try {
    // Fetch exam details with subject name
    $stmt = $pdo->prepare("
        SELECT e.*, s.name AS subject_name 
        FROM exams e
        JOIN subjects s ON e.subject_id = s.id
        WHERE e.id = ?
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        set_message('error', 'Exam not found.');
        header('Location: list.php');
        exit();
    }

    // Fetch exam results summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_students,
            COUNT(CASE WHEN obtained_marks >= ? * 0.4 THEN 1 END) as passed_students,
            AVG(obtained_marks) as average_marks,
            MAX(obtained_marks) as highest_marks,
            MIN(obtained_marks) as lowest_marks
        FROM exam_results
        WHERE exam_id = ?
    ");
    $stmt->execute([$exam['total_marks'], $exam_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch top 5 students
    $stmt = $pdo->prepare("
        SELECT er.*, CONCAT(s.first_name, ' ', s.last_name) as student_name
        FROM exam_results er
        JOIN students s ON er.student_id = s.id
        WHERE er.exam_id = ?
        ORDER BY er.obtained_marks DESC
        LIMIT 5
    ");
    $stmt->execute([$exam_id]);
    $top_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
    header('Location: list.php');
    exit();
}

$message = get_message();
?>



    <div class="container-fluid py-4">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h2 class="mb-0">
                    <i class="fas fa-file-alt me-2 text-primary"></i>Exam Details
                </h2>
            </div>
            <div class="col-auto">
                <a href="list.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="row g-4">
            <!-- Basic Information Card -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="icon-square bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="fas fa-info-circle text-primary"></i>
                            </div>
                            <h5 class="mb-0">Exam Information</h5>
                        </div>

                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-4 text-muted">Title</div>
                                    <div class="col-8 fw-medium"><?php echo htmlspecialchars($exam['title']); ?></div>
                                </div>
                            </div>
                            <div class="list-group-item px-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-4 text-muted">Subject</div>
                                    <div class="col-8">
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-book me-1"></i>
                                            <?php echo htmlspecialchars($exam['subject_name']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item px-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-4 text-muted">Date</div>
                                    <div class="col-8">
                                        <i class="far fa-calendar me-2 text-muted"></i>
                                        <?php echo date('F d, Y', strtotime($exam['exam_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item px-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-4 text-muted">Total Marks</div>
                                    <div class="col-8">
                                        <i class="fas fa-star text-warning me-2"></i>
                                        <?php echo $exam['total_marks']; ?> marks
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item px-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-4 text-muted">Weight</div>
                                    <div class="col-8">
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-primary" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $exam['weight']; ?>%"></div>
                                            </div>
                                            <span class="ms-2 text-muted small">
                                                <?php echo $exam['weight']; ?>%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Statistics Card -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="icon-square bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="fas fa-chart-bar text-primary"></i>
                            </div>
                            <h5 class="mb-0">Performance Overview</h5>
                        </div>

                        <div class="row g-4">
                            <!-- Total Students Card -->
                            <div class="col-sm-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h3 class="mb-2"><?php echo $summary['total_students']; ?></h3>
                                        <p class="card-text text-muted small mb-0">
                                            <i class="fas fa-users me-2"></i>Total Students
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Pass Rate Card -->
                            <div class="col-sm-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h3 class="mb-2">
                                            <?php 
                                            $pass_rate = $summary['total_students'] > 0 
                                                ? round(($summary['passed_students'] / $summary['total_students']) * 100) 
                                                : 0;
                                            echo $pass_rate . '%';
                                            ?>
                                        </h3>
                                        <p class="card-text text-muted small mb-0">
                                            <i class="fas fa-check-circle me-2"></i>Pass Rate
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Average Score -->
                            <div class="col-12">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item px-0 py-3">
                                        <div class="row align-items-center">
                                            <div class="col-4 text-muted">Average Score</div>
                                            <div class="col-8">
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                    $avg_percentage = $exam['total_marks'] > 0 
                                                        ? round(($summary['average_marks'] / $exam['total_marks']) * 100) 
                                                        : 0;
                                                    ?>
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-success" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $avg_percentage; ?>%"></div>
                                                    </div>
                                                    <span class="ms-2">
                                                        <?php echo round($summary['average_marks'], 1); ?>
                                                        <span class="text-muted small">/ <?php echo $exam['total_marks']; ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item px-0 py-3">
                                        <div class="row align-items-center">
                                            <div class="col-4 text-muted">Highest Score</div>
                                            <div class="col-8">
                                                <i class="fas fa-trophy text-warning me-2"></i>
                                                <?php echo $summary['highest_marks']; ?>
                                                <span class="text-muted small">/ <?php echo $exam['total_marks']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item px-0 py-3">
                                        <div class="row align-items-center">
                                            <div class="col-4 text-muted">Lowest Score</div>
                                            <div class="col-8">
                                                <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                                <?php echo $summary['lowest_marks']; ?>
                                                <span class="text-muted small">/ <?php echo $exam['total_marks']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performers Card -->
            <?php if (!empty($top_students)): ?>
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="icon-square bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="fas fa-medal text-primary"></i>
                            </div>
                            <h5 class="mb-0">Top Performers</h5>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3">Rank</th>
                                        <th class="py-3">Student</th>
                                        <th class="py-3">Score</th>
                                        <th class="py-3">Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_students as $index => $student): ?>
                                        <tr>
                                            <td>
                                                <?php if ($index < 3): ?>
                                                    <div class="icon-square bg-warning bg-opacity-10 p-2 rounded text-center" style="width: 40px">
                                                        <?php 
                                                        $medal_class = ['text-warning', 'text-secondary', 'text-orange'];
                                                        echo "<i class='fas fa-medal {$medal_class[$index]}'></i>";
                                                        ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="icon-square bg-light p-2 rounded text-center" style="width: 40px">
                                                        <?php echo $index + 1; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-square bg-primary bg-opacity-10 p-2 rounded me-3">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($student['student_name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo $student['obtained_marks']; ?>
                                                <span class="text-muted small">/ <?php echo $exam['total_marks']; ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $score_percentage = ($student['obtained_marks'] / $exam['total_marks']) * 100;
                                                $bg_class = $score_percentage >= 80 ? 'bg-success' : ($score_percentage >= 60 ? 'bg-info' : 'bg-warning');
                                                ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1" style="height: 6px; width: 100px">
                                                        <div class="progress-bar <?php echo $bg_class; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $score_percentage; ?>%"></div>
                                                    </div>
                                                    <span class="ms-2 text-muted small">
                                                        <?php echo round($score_percentage); ?>%
                                                    </span>
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
            <?php endif; ?>
        </div>
    </div>

    
