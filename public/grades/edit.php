<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/utils.php';

$pdo = get_pdo_connection();

// Check if user is logged in and has admin/teacher role
if (!isset($_SESSION['user_id']) || (!has_role('admin') && !has_role('teacher'))) {
    header('Location: ../login.php');
    exit();
}

$result_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$result_id) {
    set_message('error', 'Invalid result ID.');
    header('Location: list.php');
    exit();
}

$result = null;
$errors = [];

// Fetch result details
try {
    $stmt = $pdo->prepare("SELECT er.id, er.exam_id, er.student_id, er.marks_obtained, er.remarks, e.title AS exam_title, e.total_marks, s.first_name, s.last_name FROM exam_results er JOIN exams e ON er.exam_id = e.id JOIN students s ON er.student_id = s.id WHERE er.id = ?");
    $stmt->execute([$result_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        set_message('error', 'Result not found.');
        header('Location: list.php');
        exit();
    }
} catch (PDOException $e) {
    set_message('error', 'Database error fetching result: ' . $e->getMessage());
    header('Location: list.php');
    exit();
}

// Populate form fields with existing data
$marks_obtained = $result['marks_obtained'];
$remarks = $result['remarks'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: edit.php?id=' . $result_id);
        exit();
    }

    $marks_obtained = filter_input(INPUT_POST, 'marks_obtained', FILTER_VALIDATE_FLOAT);
    $remarks = trim($_POST['remarks']);

    // Validation
    if ($marks_obtained === false || $marks_obtained < 0 || $marks_obtained > $result['total_marks']) {
        $errors[] = 'Marks obtained must be a number between 0 and ' . $result['total_marks'] . '.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE exam_results SET marks_obtained = ?, remarks = ?, marked_by = ? WHERE id = ?");
            $stmt->execute([$marks_obtained, $remarks, $_SESSION['user_id'], $result_id]);

            set_message('success', 'Result updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error updating result: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            set_message('error', $error);
        }
    }
}

$csrf_token = generate_csrf_token();
$message = get_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grade - SAMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h2 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Edit Grade
                </h2>
            </div>
            <div class="col-auto">
                <a href="list.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Grades
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <form action="edit.php?id=<?php echo htmlspecialchars($result_id); ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-circle bg-primary-subtle">
                                                <i class="fas fa-file-alt text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($result['exam_title']); ?></h5>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-user-graduate me-2"></i>
                                                <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?>
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0 ms-3">
                                            <span class="badge bg-primary fs-6">
                                                <?php echo htmlspecialchars($result['total_marks']); ?> Marks
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" 
                                               class="form-control form-control-lg" 
                                               id="marks_obtained" 
                                               name="marks_obtained" 
                                               value="<?php echo htmlspecialchars($marks_obtained); ?>" 
                                               min="0" 
                                               max="<?php echo htmlspecialchars($result['total_marks']); ?>" 
                                               step="0.01" 
                                               required>
                                        <label for="marks_obtained">Marks Obtained</label>
                                    </div>
                                    <div class="progress mt-2" style="height: 4px;">
                                        <div class="progress-bar" 
                                             role="progressbar" 
                                             style="width: <?php echo ($marks_obtained / $result['total_marks']) * 100; ?>%"
                                             aria-valuenow="<?php echo ($marks_obtained / $result['total_marks']) * 100; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" 
                                                  id="remarks" 
                                                  name="remarks" 
                                                  style="height: 100px;"><?php echo htmlspecialchars($remarks); ?></textarea>
                                        <label for="remarks">Remarks</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="list.php" class="btn btn-light">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Grade
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title d-flex align-items-center mb-4">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Grade Information
                        </h5>
                        <div class="text-center">
                            <div class="display-4 mb-3">
                                <?php 
                                $percentage = ($marks_obtained / $result['total_marks']) * 100;
                                $grade = '';
                                if ($percentage >= 80) $grade = 'A';
                                else if ($percentage >= 70) $grade = 'B';
                                else if ($percentage >= 60) $grade = 'C';
                                else if ($percentage >= 50) $grade = 'D';
                                else if ($percentage >= 40) $grade = 'E';
                                else $grade = 'F';
                                echo $grade;
                                ?>
                            </div>
                            <div class="h5 text-muted mb-0">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="text-center text-muted">
                            <small>Last updated by <?php echo htmlspecialchars($_SESSION['username']); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .avatar-circle {
            width: 48px;
            height: 48px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-circle i {
            font-size: 20px;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const marksInput = document.getElementById('marks_obtained');
            const progressBar = document.querySelector('.progress-bar');
            const gradeDisplay = document.querySelector('.display-4');
            const percentageDisplay = document.querySelector('.h5.text-muted');
            const totalMarks = <?php echo $result['total_marks']; ?>;

            function updateGradeInfo() {
                const marks = parseFloat(marksInput.value) || 0;
                const percentage = (marks / totalMarks) * 100;

                // Update progress bar
                progressBar.style.width = percentage + '%';
                progressBar.className = 'progress-bar';
                progressBar.classList.add(percentage >= 40 ? 'bg-success' : 'bg-danger');

                // Update percentage display
                percentageDisplay.textContent = percentage.toFixed(1) + '%';

                // Update grade display
                let grade = 'F';
                if (percentage >= 80) grade = 'A';
                else if (percentage >= 70) grade = 'B';
                else if (percentage >= 60) grade = 'C';
                else if (percentage >= 50) grade = 'D';
                else if (percentage >= 40) grade = 'E';
                gradeDisplay.textContent = grade;

                // Update grade display color
                gradeDisplay.className = 'display-4 mb-3';
                gradeDisplay.classList.add(percentage >= 40 ? 'text-success' : 'text-danger');
            }

            marksInput.addEventListener('input', updateGradeInfo);
            updateGradeInfo(); // Initial update
        });
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
