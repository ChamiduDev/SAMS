<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$name = '';
$code = '';
$course_id = '';
$teacher_id = '';

// Fetch all courses for the dropdown
$courses = [];
try {
    $stmt = $pdo->query("SELECT id, name, code FROM courses ORDER BY name");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching courses: ' . $e->getMessage());
}

// Fetch all teachers for the dropdown
$teachers = [];
try {
    $stmt = $pdo->query("SELECT u.id, u.username FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'teacher' ORDER BY username");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching teachers: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: add.php');
        exit();
    }

    $name = trim($_POST['name']);
    $code = trim(strtoupper($_POST['code'])); // Convert code to uppercase
    $course_id = (int)$_POST['course_id'];
    $teacher_id = (int)$_POST['teacher_id'];

    // Input Validation
    if (empty($name)) {
        $errors[] = 'Subject Name is required.';
    }
    if (empty($code)) {
        $errors[] = 'Subject Code is required.';
    }
    if ($course_id <= 0) {
        $errors[] = 'Linked Course is required.';
    }
    // Teacher ID can be 0 if not assigned, but validate if provided
    if ($teacher_id < 0) {
        $errors[] = 'Invalid Teacher selection.';
    }

    // Check for duplicate Subject Code
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Subject Code already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (name, code, course_id, teacher_id) VALUES (?, ?, ?, ?)");
            // Handle case where teacher_id is 0 (not assigned)
            $execute_params = [$name, $code, $course_id, ($teacher_id === 0 ? null : $teacher_id)];
            $stmt->execute($execute_params);

            set_message('success', 'Subject added successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error: ' . $e->getMessage());
            header('Location: add.php');
            exit();
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


    <div class="container-fluid px-4">
        <?php display_message($message); ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-book me-2"></i>Add New Subject
                        </h5>
                        <a href="list.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Subject List
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="add.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <!-- Basic Information -->
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3 text-primary">Basic Information</h6>
                                    
                                    <div class="mb-4">
                                        <label for="name" class="form-label">Subject Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-book-open"></i>
                                            </span>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($name); ?>" 
                                                   placeholder="Enter subject name" required>
                                        </div>
                                        <div class="form-text">Enter a descriptive name for the subject</div>
                                    </div>

                                    <div class="mb-0">
                                        <label for="code" class="form-label">Subject Code</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-code"></i>
                                            </span>
                                            <input type="text" class="form-control" id="code" name="code" 
                                                   value="<?php echo htmlspecialchars($code); ?>" 
                                                   placeholder="Enter subject code"
                                                   style="text-transform: uppercase;" required>
                                        </div>
                                        <div class="form-text">Subject code must be unique (e.g., MATH101)</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Course and Teacher Assignment -->
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3 text-primary">Course and Teacher Assignment</h6>
                                    
                                    <div class="mb-4">
                                        <label for="course_id" class="form-label">Linked Course</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-graduation-cap"></i>
                                            </span>
                                            <select class="form-select" id="course_id" name="course_id" required>
                                                <option value="">Select Course</option>
                                                <?php foreach ($courses as $c): ?>
                                                    <option value="<?php echo htmlspecialchars($c['id']); ?>" <?php echo ($course_id == $c['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($c['name']) . ' (' . htmlspecialchars($c['code']) . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-text">Select the course this subject belongs to</div>
                                    </div>

                                    <div class="mb-0">
                                        <label for="teacher_id" class="form-label">Assigned Teacher</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                            </span>
                                            <select class="form-select" id="teacher_id" name="teacher_id">
                                                <option value="0">-- Not Assigned --</option>
                                                <?php foreach ($teachers as $t): ?>
                                                    <option value="<?php echo htmlspecialchars($t['id']); ?>" <?php echo ($teacher_id == $t['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($t['username']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-text">Optional: Assign a teacher to this subject</div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="list.php" class="btn btn-light">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Subject
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    