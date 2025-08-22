<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$student_id_param = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$student = null;

// Fetch student details for pre-filling the form
if ($student_id_param > 0) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND status = 'active'");
    $stmt->execute([$student_id_param]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        set_message('error', 'Student not found or is deleted.');
        header('Location: list.php');
        exit();
    }
} else {
    set_message('error', 'Invalid student ID.');
    header('Location: list.php');
    exit();
}

// Pre-fill form variables with existing data
$student_id = $student['student_id']; // Cannot be changed
$first_name = $student['first_name'];
$last_name = $student['last_name'];
$dob = $student['dob'] ?? '';
$gender = $student['gender'] ?? '';
$year = $student['year'] ?? '';
$address = $student['address'] ?? '';
$contact_no = $student['contact_no'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: edit.php?id=' . $student_id_param);
        exit();
    }

    // Update variables with POST data for validation and display
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = trim($_POST['dob']);
    $gender = $_POST['gender'];
    $year = trim($_POST['year']);
    $address = trim($_POST['address']);
    $contact_no = trim($_POST['contact_no']);

    // Input Validation
    if (empty($first_name)) { $errors[] = 'First Name is required.'; }
    if (empty($last_name)) { $errors[] = 'Last Name is required.'; }
    if (empty($dob)) { $errors[] = 'Date of Birth is required.'; } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dob)) { $errors[] = 'Invalid Date of Birth format (YYYY-MM-DD).'; }
    if (empty($gender)) { $errors[] = 'Gender is required.'; } elseif (!in_array($gender, ['M', 'F', 'O'])) { $errors[] = 'Invalid Gender selected.'; }
    if (empty($year)) { $errors[] = 'Year is required.'; } elseif (!is_numeric($year) || $year < 1900 || $year > 2100) { $errors[] = 'Invalid Year.'; }
    // Address and Contact No can be empty

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, dob = ?, gender = ?, year = ?, address = ?, contact_no = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $dob, $gender, $year, $address, $contact_no, $student_id_param]);

            set_message('success', 'Student updated successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error: ' . $e->getMessage());
            header('Location: edit.php?id=' . $student_id_param);
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



        <form action="edit.php?id=<?php echo htmlspecialchars($student_id_param); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="row">
                <!-- Basic Information -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="student_id_display" class="form-label">Student ID</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="student_id_display" value="<?php echo htmlspecialchars($student_id); ?>" disabled>
                                </div>
                                <small class="form-text text-muted">Student ID cannot be changed.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($dob); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="M" <?php echo ($gender == 'M') ? 'selected' : ''; ?>>Male</option>
                                        <option value="F" <?php echo ($gender == 'F') ? 'selected' : ''; ?>>Female</option>
                                        <option value="O" <?php echo ($gender == 'O') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Information -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="year" class="form-label">Year</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="number" class="form-control" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="contact_no" class="form-label">Contact No</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" id="contact_no" name="contact_no" value="<?php echo htmlspecialchars($contact_no); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="list.php" class="btn btn-light">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Student
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    