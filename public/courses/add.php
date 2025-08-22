<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$name = '';
$code = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: add.php');
        exit();
    }

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

    // Check for duplicate Course Code
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Course Code already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (name, code, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $code, $description]);

            set_message('success', 'Course added successfully.');
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



        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Add New Course
                        </h5>
                        <a href="list.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Course List
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="add.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="mb-4">
                                <label for="name" class="form-label">Course Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-graduation-cap"></i>
                                    </span>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($name); ?>" 
                                           placeholder="Enter course name" required>
                                </div>
                                <div class="form-text">Enter a descriptive name for the course</div>
                            </div>

                            <div class="mb-4">
                                <label for="code" class="form-label">Course Code</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-code"></i>
                                    </span>
                                    <input type="text" class="form-control" id="code" name="code" 
                                           value="<?php echo htmlspecialchars($code); ?>" 
                                           placeholder="Enter course code"
                                           style="text-transform: uppercase;" required>
                                </div>
                                <div class="form-text">Course code must be unique (e.g., CS101)</div>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-align-left"></i>
                                    </span>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="4" placeholder="Enter course description"><?php echo htmlspecialchars($description); ?></textarea>
                                </div>
                                <div class="form-text">Provide a detailed description of the course</div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="list.php" class="btn btn-light">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Course
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    