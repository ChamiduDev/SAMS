<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$exam_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$exam_id) {
    set_message('error', 'Invalid exam ID.');
    header('Location: list.php');
    exit();
}

$exam = null;
$errors = [];
$subjects = [];

// Fetch exam details
try {
    $stmt = $pdo->prepare("SELECT id, title, subject_id, exam_date, duration, total_marks, weight FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        set_message('error', 'Exam not found.');
        header('Location: list.php');
        exit();
    }
} catch (PDOException $e) {
    set_message('error', 'Database error fetching exam: ' . $e->getMessage());
    header('Location: list.php');
    exit();
}

// Fetch subjects for dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching subjects: ' . $e->getMessage());
}

// Populate form fields with existing data
$title = $exam['title'];
$subject_id = $exam['subject_id'];
$exam_date = $exam['exam_date'];
$duration = $exam['duration'];
$total_marks = $exam['total_marks'];
$weight = $exam['weight'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: edit.php?id=' . $exam_id);
        exit();
    }

    $title = trim($_POST['title']);
    $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $exam_date = trim($_POST['exam_date']);
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
    $total_marks = filter_input(INPUT_POST, 'total_marks', FILTER_VALIDATE_INT);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);

    // Validation
    if (empty($title)) { $errors[] = 'Exam Title is required.'; }
    if (!$subject_id) { $errors[] = 'Subject is required.'; }
    if (empty($exam_date)) { $errors[] = 'Exam Date is required.'; } elseif (!isValidDate($exam_date)) { $errors[] = 'Invalid Exam Date format.'; }
    if (!$duration || $duration <= 0) { $errors[] = 'Duration must be a positive number.'; }
    if (!$total_marks || $total_marks <= 0) { $errors[] = 'Total Marks must be a positive number.'; }
    if ($weight === false || $weight < 0 || $weight > 100) { $errors[] = 'Weight must be a number between 0 and 100.'; }

    // Check if subject_id exists
    if ($subject_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Selected Subject does not exist.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE exams SET title = ?, subject_id = ?, exam_date = ?, duration = ?, total_marks = ?, weight = ? WHERE id = ?");
            $stmt->execute([$title, $subject_id, $exam_date, $duration, $total_marks, $weight, $exam_id]);

            set_message('success', 'Exam updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error updating exam: ' . $e->getMessage());
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



    <div class="container-fluid py-4">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h2 class="mb-0">
                    <i class="fas fa-edit me-2 text-primary"></i>Edit Exam
                </h2>
            </div>
            <div class="col-auto">
                <a href="list.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form action="edit.php?id=<?php echo htmlspecialchars($exam_id); ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="row g-4">
                                <!-- Basic Information -->
                                <div class="col-12">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="icon-square bg-primary bg-opacity-10 p-2 rounded me-3">
                                            <i class="fas fa-info-circle text-primary"></i>
                                        </div>
                                        <h5 class="mb-0">Basic Information</h5>
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="title" 
                                                       name="title" 
                                                       placeholder="Enter exam title"
                                                       value="<?php echo htmlspecialchars($title); ?>" 
                                                       required>
                                                <label for="title">
                                                    <i class="fas fa-file-signature me-2 text-muted"></i>
                                                    Exam Title
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="subject_id" name="subject_id" required>
                                                    <option value="">Select Subject</option>
                                                    <?php foreach ($subjects as $subject): ?>
                                                        <option value="<?php echo htmlspecialchars($subject['id']); ?>" <?php echo ($subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($subject['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="subject_id">
                                                    <i class="fas fa-book me-2 text-muted"></i>
                                                    Subject
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="date" 
                                                       class="form-control" 
                                                       id="exam_date" 
                                                       name="exam_date"
                                                       value="<?php echo htmlspecialchars($exam_date); ?>" 
                                                       required>
                                                <label for="exam_date">
                                                    <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                                    Exam Date
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="duration" 
                                                       name="duration"
                                                       value="<?php echo htmlspecialchars($duration); ?>" 
                                                       placeholder="Enter exam duration"
                                                       min="1" 
                                                       required>
                                                <label for="duration">
                                                    <i class="fas fa-clock me-2 text-muted"></i>
                                                    Duration (minutes)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-2">
                                </div>

                                <!-- Exam Settings -->
                                <div class="col-12">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="icon-square bg-primary bg-opacity-10 p-2 rounded me-3">
                                            <i class="fas fa-cog text-primary"></i>
                                        </div>
                                        <h5 class="mb-0">Exam Settings</h5>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="total_marks" 
                                                       name="total_marks"
                                                       value="<?php echo htmlspecialchars($total_marks); ?>" 
                                                       placeholder="Enter total marks"
                                                       min="1" 
                                                       required>
                                                <label for="total_marks">
                                                    <i class="fas fa-star me-2 text-muted"></i>
                                                    Total Marks
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="weight" 
                                                       name="weight"
                                                       value="<?php echo htmlspecialchars($weight); ?>" 
                                                       placeholder="Enter weight percentage"
                                                       step="0.01" 
                                                       min="0" 
                                                       max="100">
                                                <label for="weight">
                                                    <i class="fas fa-percentage me-2 text-muted"></i>
                                                    Weight in Percentage
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="alert alert-light border-primary border-opacity-25">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0">
                                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                                    </div>
                                                    <div class="flex-grow-1 ms-2">
                                                        <h6 class="alert-heading mb-1">About Exam Settings</h6>
                                                        <p class="mb-0 small text-muted">
                                                            Total marks represent the maximum achievable score in this exam. 
                                                            Weight percentage determines how much this exam contributes to the final grade calculation.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="list.php" class="btn btn-light">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Exam
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
