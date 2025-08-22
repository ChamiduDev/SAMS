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

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role_name'];

$exams_list = [];
$students_in_subject = [];
$exam_details = null;
$existing_results = [];

$selected_exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);

// Fetch exams for dropdown
try {
    $sql = "SELECT e.id, e.title, s.name AS subject_name FROM exams e JOIN subjects s ON e.subject_id = s.id ORDER BY e.exam_date DESC, e.title ASC";
    $stmt = $pdo->query($sql);
    $exams_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching exams: ' . $e->getMessage());
}

if ($selected_exam_id) {
    // Fetch exam details
    try {
        $stmt = $pdo->prepare("SELECT e.id, e.title, e.subject_id, e.total_marks, s.name AS subject_name FROM exams e JOIN subjects s ON e.subject_id = s.id WHERE e.id = ?");
        $stmt->execute([$selected_exam_id]);
        $exam_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exam_details) {
            set_message('error', 'Exam not found.');
            $selected_exam_id = null;
        }
    } catch (PDOException $e) {
        set_message('error', 'Database error fetching exam details: ' . $e->getMessage());
        $selected_exam_id = null;
    }

    if ($exam_details) {
        // Fetch students enrolled in the subject's course
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT st.id, st.first_name, st.last_name
                FROM students st
                JOIN student_courses sc ON st.id = sc.student_id
                JOIN subjects sub ON sc.course_id = sub.course_id
                WHERE sub.id = ?
                ORDER BY st.last_name, st.first_name
            ");
            $stmt->execute([$exam_details['subject_id']]);
            $students_in_subject = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            set_message('error', 'Database error fetching students for subject: ' . $e->getMessage());
        }

        // Fetch existing results for this exam
        try {
            $stmt = $pdo->prepare("SELECT student_id, marks_obtained, remarks FROM exam_results WHERE exam_id = ?");
            $stmt->execute([$selected_exam_id]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_results[$row['student_id']] = [
                    'marks_obtained' => $row['marks_obtained'],
                    'remarks' => $row['remarks']
                ];
            }
        } catch (PDOException $e) {
            set_message('error', 'Database error fetching existing results: ' . $e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_grades'])) {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: mark.php?exam_id=' . $selected_exam_id);
        exit();
    }

    $exam_id_post = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
    $marks_data = $_POST['marks'] ?? [];
    $remarks_data = $_POST['remarks'] ?? [];

    if (!$exam_id_post || $exam_id_post !== $selected_exam_id) {
        set_message('error', 'Invalid exam ID for submission.');
    } else if (empty($marks_data)) {
        set_message('error', 'No marks data submitted.');
    } else {
        try {
            $pdo->beginTransaction();
            $marked_count = 0;

            // Delete existing results for this exam to allow re-marking
            $stmt_delete = $pdo->prepare("DELETE FROM exam_results WHERE exam_id = ?");
            $stmt_delete->execute([$exam_id_post]);

            $stmt_insert = $pdo->prepare("INSERT INTO exam_results (exam_id, student_id, marks_obtained, remarks, marked_by) VALUES (?, ?, ?, ?, ?)");

            foreach ($marks_data as $student_id => $marks_obtained) {
                $student_id = filter_var($student_id, FILTER_VALIDATE_INT);
                $marks_obtained = filter_var($marks_obtained, FILTER_VALIDATE_FLOAT);
                $remarks = filter_var($remarks_data[$student_id] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

                if ($student_id && $marks_obtained !== false && $marks_obtained >= 0 && $marks_obtained <= $exam_details['total_marks']) {
                    $stmt_insert->execute([$exam_id_post, $student_id, $marks_obtained, $remarks, $user_id]);
                    $marked_count++;
                } else {
                    error_log("Invalid marks data for student ID: $student_id, Marks: $marks_obtained");
                }
            }

            $pdo->commit();
            set_message('success', "Grades marked successfully for $marked_count students.");
            // Refresh existing results after successful marking
            header('Location: mark.php?exam_id=' . $selected_exam_id);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_message('error', 'Database error marking grades: ' . $e->getMessage());
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
    <title>Mark Grades - SAMS</title>
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
                    <i class="fas fa-pen-alt me-2"></i>Mark Grades
                </h2>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="mark.php">
                    <div class="row align-items-end g-3">
                        <div class="col-md-8">
                            <div class="form-floating">
                                <select class="form-select" id="exam_id" name="exam_id" required onchange="this.form.submit()">
                                    <option value="">-- Select an Exam --</option>
                                    <?php foreach ($exams_list as $exam): ?>
                                        <option value="<?php echo htmlspecialchars($exam['id']); ?>" <?php echo ($selected_exam_id == $exam['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($exam['title']) . ' (' . htmlspecialchars($exam['subject_name']) . ')'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="exam_id">Select Exam</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <a href="../exams/add.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-plus me-2"></i>Create New Exam
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($exam_details): ?>
            <div class="row g-4">
                <div class="col-md-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="card-title mb-0">
                                    <i class="fas fa-file-alt text-primary me-2"></i>
                                    <?php echo htmlspecialchars($exam_details['title']); ?>
                                </h4>
                                <span class="badge bg-primary fs-6">
                                    <i class="fas fa-star me-1"></i>
                                    <?php echo htmlspecialchars($exam_details['total_marks']); ?> Marks
                                </span>
                            </div>
                            <p class="text-muted mb-0">
                                <i class="fas fa-book me-2"></i>
                                <strong>Subject:</strong> <?php echo htmlspecialchars($exam_details['subject_name']); ?>
                            </p>
                        </div>
                    </div>

                    <?php if (empty($students_in_subject)): ?>
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-4 text-center">
                                <i class="fas fa-users-slash text-muted fa-3x mb-3"></i>
                                <h5 class="text-muted">No Students Found</h5>
                                <p class="mb-0">Ensure students are enrolled in courses linked to this subject.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="mark.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($selected_exam_id); ?>">

                            <div class="card shadow-sm border-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0 py-3">Student Name</th>
                                                <th class="border-0 py-3 text-center">Marks Obtained</th>
                                                <th class="border-0 py-3">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students_in_subject as $student): ?>
                                                <?php
                                                $current_marks = $existing_results[$student['id']]['marks_obtained'] ?? '';
                                                $current_remarks = $existing_results[$student['id']]['remarks'] ?? '';
                                                $percentage = $current_marks ? ($current_marks / $exam_details['total_marks']) * 100 : 0;
                                                ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-user-graduate text-muted me-2"></i>
                                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                        </div>
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <div class="input-group">
                                                            <input type="number" 
                                                                   class="form-control text-center" 
                                                                   name="marks[<?php echo htmlspecialchars($student['id']); ?>]" 
                                                                   value="<?php echo htmlspecialchars($current_marks); ?>" 
                                                                   min="0" 
                                                                   max="<?php echo htmlspecialchars($exam_details['total_marks']); ?>" 
                                                                   step="0.01" 
                                                                   required>
                                                            <span class="input-group-text bg-light">
                                                                / <?php echo htmlspecialchars($exam_details['total_marks']); ?>
                                                            </span>
                                                        </div>
                                                        <?php if ($current_marks): ?>
                                                        <div class="progress mt-2" style="height: 4px;">
                                                            <div class="progress-bar bg-<?php echo $percentage >= 40 ? 'success' : 'danger'; ?>" 
                                                                 role="progressbar" 
                                                                 style="width: <?php echo $percentage; ?>%" 
                                                                 aria-valuenow="<?php echo $percentage; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="fas fa-comment-alt"></i>
                                                            </span>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   name="remarks[<?php echo htmlspecialchars($student['id']); ?>]" 
                                                                   value="<?php echo htmlspecialchars($current_remarks); ?>" 
                                                                   placeholder="Enter remarks...">
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer bg-white border-0 p-4">
                                    <div class="d-flex justify-content-end">
                                        <a href="list.php" class="btn btn-light me-2">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary" name="mark_grades">
                                            <i class="fas fa-save me-2"></i>Save Grades
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate percentage and update progress bar when marks change
        document.addEventListener('DOMContentLoaded', function() {
            const totalMarks = <?php echo isset($exam_details['total_marks']) ? $exam_details['total_marks'] : 0; ?>;
            
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', function() {
                    const marksObtained = parseFloat(this.value) || 0;
                    const percentage = (marksObtained / totalMarks) * 100;
                    const progressBar = this.closest('td').querySelector('.progress-bar');
                    
                    if (progressBar) {
                        progressBar.style.width = percentage + '%';
                        progressBar.classList.remove('bg-success', 'bg-danger');
                        progressBar.classList.add(percentage >= 40 ? 'bg-success' : 'bg-danger');
                    }
                });
            });
        });
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
