<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$subject_id_param = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$subject = null;

// Fetch subject details for pre-filling the form
if ($subject_id_param > 0) {
    $stmt = $pdo->prepare("SELECT id, name, code, course_id, teacher_id FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id_param]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subject) {
        set_message('error', 'Subject not found.');
        header('Location: list.php');
        exit();
    }
} else {
    set_message('error', 'Invalid subject ID.');
    header('Location: list.php');
    exit();
}

// Pre-fill form variables with existing data
$name = $subject['name'];
$code = $subject['code'];
$course_id = $subject['course_id'];
$teacher_id = $subject['teacher_id'] ?? 0; // Default to 0 if NULL

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
        header('Location: edit.php?id=' . $subject_id_param);
        exit();
    }

    // Update variables with POST data for validation and display
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

    // Check for duplicate Subject Code, excluding current subject
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE code = ? AND id != ?");
        $stmt->execute([$code, $subject_id_param]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Subject Code already exists for another subject.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE subjects SET name = ?, code = ?, course_id = ?, teacher_id = ? WHERE id = ?");
            // Handle case where teacher_id is 0 (not assigned)
            $execute_params = [$name, $code, $course_id, ($teacher_id === 0 ? null : $teacher_id), $subject_id_param];
            $stmt->execute($execute_params);

            set_message('success', 'Subject updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error: ' . $e->getMessage());
            header('Location: edit.php?id=' . $subject_id_param);
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


    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Edit Subject: <?php echo htmlspecialchars($name); ?> (Code: <?php echo htmlspecialchars($code); ?>)</h2>
            <a href="list.php" class="btn btn-secondary">Back to Subject List</a>
        </div>

        <?php display_message($message); ?>

        <?php
        // Find the linked course's details
        $linked_course_name = 'N/A';
        $linked_course_code = 'N/A';
        foreach ($courses as $c) {
            if ($c['id'] == $course_id) {
                $linked_course_name = htmlspecialchars($c['name']);
                $linked_course_code = htmlspecialchars($c['code']);
                break;
            }
        }
        ?>

        <div class="card mb-4">
            <div class="card-header">
                Course Details for this Subject
            </div>
            <div class="card-body">
                <p><strong>Linked Course:</strong> <?php echo $linked_course_name; ?> (<?php echo $linked_course_code; ?>)</p>
            </div>
        </div>

        <form action="edit.php?id=<?php echo htmlspecialchars($subject_id_param); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="mb-3">
                <label for="name" class="form-label">Subject Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="mb-3">
                <label for="code" class="form-label">Subject Code</label>
                <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($code); ?>" required>
            </div>

            <div class="mb-3">
                <label for="course_id" class="form-label">Linked Course</label>
                <select class="form-select" id="course_id" name="course_id" required>
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['id']); ?>" <?php echo ($course_id == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']) . ' (' . htmlspecialchars($c['code']) . ')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="teacher_id" class="form-label">Assigned Teacher</label>
                <select class="form-select" id="teacher_id" name="teacher_id">
                    <option value="0">-- Not Assigned --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo htmlspecialchars($t['id']); ?>" <?php echo ($teacher_id == $t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Subject</button>
        </form>
    </div>

    