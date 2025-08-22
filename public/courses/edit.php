<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$course_id_param = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$course = null;

// Fetch course details for pre-filling the form
if ($course_id_param > 0) {
    $stmt = $pdo->prepare("SELECT id, name, code, description FROM courses WHERE id = ?");
    $stmt->execute([$course_id_param]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        set_message('error', 'Course not found.');
        header('Location: list.php');
        exit();
    }
} else {
    set_message('error', 'Invalid course ID.');
    header('Location: list.php');
    exit();
}

// Pre-fill form variables with existing data
$name = $course['name'];
$code = $course['code'];
$description = $course['description'];

// Fetch subjects associated with this course
$associated_subjects = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, code, teacher_id FROM subjects WHERE course_id = ? ORDER BY name");
    $stmt->execute([$course_id_param]);
    $associated_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching associated subjects: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: edit.php?id=' . $course_id_param);
        exit();
    }

    // Update variables with POST data for validation and display
    $name = trim($_POST['name']);
    $code = trim(strtoupper($_POST['code'])); // Convert code to uppercase
    $description = trim($_POST['description']);

    // Input Validation
    if (empty($name)) {
        $errors[] = 'Course Name is required.';
    }
    if (empty($code)) {
        $errors[] = 'Course Code is required.';
    }

    // Check for duplicate Course Code, excluding current course
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE code = ? AND id != ?");
        $stmt->execute([$code, $course_id_param]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Course Code already exists for another course.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE courses SET name = ?, code = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $code, $description, $course_id_param]);

            set_message('success', 'Course updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error: ' . $e->getMessage());
            header('Location: edit.php?id=' . $course_id_param);
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
            <h2>Edit Course: <?php echo htmlspecialchars($name); ?> (Code: <?php echo htmlspecialchars($code); ?>)</h2>
            <a href="list.php" class="btn btn-secondary">Back to Course List</a>
        </div>

        <?php display_message($message); ?>

        <form action="edit.php?id=<?php echo htmlspecialchars($course_id_param); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="mb-3">
                <label for="name" class="form-label">Course Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="mb-3">
                <label for="code" class="form-label">Course Code</label>
                <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($code); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Update Course</button>
        </form>

        <div class="mt-5">
            <h3>Subjects in this Course</h3>
            <?php if (empty($associated_subjects)): ?>
                <div class="alert alert-info" role="alert">
                    No subjects are currently associated with this course.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Assigned Teacher</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($associated_subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['id']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                    <td>
                                        <?php
                                        // Fetch teacher username if teacher_id is set
                                        $teacher_name = 'N/A';
                                        if ($subject['teacher_id']) {
                                            try {
                                                $stmt_teacher = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                                                $stmt_teacher->execute([$subject['teacher_id']]);
                                                $teacher_data = $stmt_teacher->fetch(PDO::FETCH_ASSOC);
                                                if ($teacher_data) {
                                                    $teacher_name = htmlspecialchars($teacher_data['username']);
                                                }
                                            } catch (PDOException $e) {
                                                error_log("Error fetching teacher name: " . $e->getMessage());
                                            }
                                        }
                                        echo $teacher_name;
                                        ?>
                                    </td>
                                    <td>
                                        <a href="../subjects/edit.php?id=<?php echo htmlspecialchars($subject['id']); ?>" class="btn btn-sm btn-info">View/Edit Subject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    