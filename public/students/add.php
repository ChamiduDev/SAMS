<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

function generateStudentID($pdo) {
    $year = date('Y');
    
    // Get the last student number for this year
    
    // Get the last student number for this year
    $stmt = $pdo->prepare("
        SELECT student_id 
        FROM students 
        WHERE student_id LIKE ?
        ORDER BY student_id DESC 
        LIMIT 1
    ");
    $yearPrefix = $year . '%';
    $stmt->execute([$yearPrefix]);
    $lastId = $stmt->fetchColumn();
    
    if ($lastId) {
        // Extract the number part and increment
        $lastNumber = intval(substr($lastId, -4));
        $newNumber = $lastNumber + 1;
    } else {
        // Start with 1 if no existing IDs for this year
        $newNumber = 1;
    }
    
    // Format: YYYY0001
    return $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

$errors = [];
$student_id = generateStudentID($pdo); // Auto-generate student ID
$first_name = '';
$last_name = '';
$dob = '';
$gender = '';
$year = date('Y');
$address = '';
$contact_no = '';
$user_link_option = 'create_new'; // Default to creating new user
$linked_user_id = '';
$selected_courses = []; // Array to store selected courses and their subjects
$selected_subjects = []; // Array to store selected subjects

// Fetch all courses with their subjects for the dropdown
$courses = [];
try {
    $stmt = $pdo->query("
        SELECT c.id as course_id, c.name as course_name, c.code as course_code, 
               s.id as subject_id, s.name as subject_name, s.code as subject_code
        FROM courses c
        LEFT JOIN subjects s ON s.course_id = c.id
        ORDER BY c.name, s.name
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize the results by course
    foreach ($results as $row) {
        if (!isset($courses[$row['course_id']])) {
            $courses[$row['course_id']] = [
                'name' => $row['course_name'],
                'code' => $row['course_code'],
                'subjects' => []
            ];
        }
        if ($row['subject_id']) {
            $courses[$row['course_id']]['subjects'][] = [
                'id' => $row['subject_id'],
                'name' => $row['subject_name'],
                'code' => $row['subject_code']
            ];
        }
    }
} catch (PDOException $e) {
    set_message('error', 'Database error fetching courses and subjects: ' . $e->getMessage());
}

// Fetch existing 'student' role users who are not yet linked to a student record
$unlinked_students = [];
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        JOIN roles r ON u.role_id = r.id
        WHERE r.name = 'student' AND s.user_id IS NULL
        ORDER BY u.username
    ");
    $stmt->execute();
    $unlinked_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching unlinked users: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: add.php');
        exit();
    }

    $student_id = trim($_POST['student_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = trim($_POST['dob']);
    $gender = $_POST['gender'];
    $year = trim($_POST['year']);
    $address = trim($_POST['address']);
    $contact_no = trim($_POST['contact_no']);
    $user_link_option = $_POST['user_link_option'];
    $linked_user_id = ($user_link_option == 'link_existing') ? (int)$_POST['linked_user_id'] : null;
    
    // Process course and subject selections
    $selected_subjects = [];
    if (isset($_POST['subjects']) && is_array($_POST['subjects'])) {
        foreach ($_POST['subjects'] as $subject_id) {
            $subject_id = (int)$subject_id;
            if ($subject_id > 0) {
                $selected_subjects[] = $subject_id;
            }
        }
    }

    // Input Validation
    if (empty($student_id)) { $errors[] = 'Student ID is required.'; }
    if (empty($first_name)) { $errors[] = 'First Name is required.'; }
    if (empty($last_name)) { $errors[] = 'Last Name is required.'; }
    if (empty($dob)) { $errors[] = 'Date of Birth is required.'; } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dob)) { $errors[] = 'Invalid Date of Birth format (YYYY-MM-DD).'; }
    if (empty($gender)) { $errors[] = 'Gender is required.'; } elseif (!in_array($gender, ['M', 'F', 'O'])) { $errors[] = 'Invalid Gender selected.'; }
    if (empty($selected_subjects)) { $errors[] = 'Please select at least one subject.'; }
    if (empty($year)) { $errors[] = 'Year is required.'; } elseif (!is_numeric($year) || $year < 1900 || $year > 2100) { $errors[] = 'Invalid Year.'; }
    // Address and Contact No can be empty

    // Check for duplicate Student ID
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Student ID already exists.';
        }
    }

    // Handle user account linking/creation
    $final_user_id = null;
    if (empty($errors)) {
        if ($user_link_option == 'link_existing') {
            if ($linked_user_id > 0) {
                // Verify the linked_user_id is valid and unlinked
                $stmt = $pdo->prepare("SELECT u.id FROM users u LEFT JOIN students s ON u.id = s.user_id WHERE u.id = ? AND u.role = 'student' AND s.user_id IS NULL");
                $stmt->execute([$linked_user_id]);
                if ($stmt->fetch()) {
                    $final_user_id = $linked_user_id;
                } else {
                    $errors[] = 'Selected user account is invalid or already linked.';
                }
            } else {
                $errors[] = 'Please select an existing user account.';
            }
        } else { // create_new
            // Auto-create a new user account
            $new_username = strtolower(str_replace(' ', '', $first_name) . '.' . str_replace(' ', '', $last_name));
            $new_email = strtolower(str_replace(' ', '', $first_name) . '.' . str_replace(' ', '', $last_name) . '@sams.edu'); // Default email
            $default_password = 'password123'; // TEMPORARY DEFAULT PASSWORD - USER MUST CHANGE THIS!
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

            // Ensure username/email uniqueness for the new user
            $u_suffix = 1;
            $original_username = $new_username;
            while (true) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$new_username, $new_email]);
                if ($stmt->fetchColumn() == 0) {
                    break; // Username and email are unique
                }
                $new_username = $original_username . $u_suffix++;
                $new_email = strtolower(str_replace(' ', '', $first_name) . '.' . str_replace(' ', '', $last_name) . $u_suffix . '@sams.edu');
            }

            try {
                $pdo->beginTransaction();
                // Get the role_id for 'student'
                $stmt_role = $pdo->prepare("SELECT id FROM roles WHERE name = 'student'");
                $stmt_role->execute();
                $student_role_id = $stmt_role->fetchColumn();

                if (!$student_role_id) {
                    throw new PDOException("Student role not found in roles table.");
                }

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$new_username, $new_email, $hashed_password, $student_role_id]);
                $final_user_id = $pdo->lastInsertId();
                $pdo->commit();
                set_message('info', 'New user account created: Username: ' . htmlspecialchars($new_username) . ', Default Password: ' . htmlspecialchars($default_password) . '. Please inform the student to change this password immediately.');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Database error creating user account: ' . $e->getMessage();
            }
        }
    }

    // If no errors so far, insert student record and link to subjects
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            // Insert student record
            $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, first_name, last_name, dob, gender, year, address, contact_no, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$final_user_id, $student_id, $first_name, $last_name, $dob, $gender, $year, $address, $contact_no]);
            $new_student_id = $pdo->lastInsertId();

            // Insert student-subject relationships and collect course_ids
            $stmt_subject_link = $pdo->prepare("
                INSERT INTO student_course_subjects (student_id, course_id, subject_id) 
                SELECT ?, s.course_id, s.id 
                FROM subjects s 
                WHERE s.id = ?
            ");
            $course_ids = [];
            foreach ($selected_subjects as $subject_id) {
                $stmt_subject_link->execute([$new_student_id, $subject_id]);
                $stmt_course = $pdo->prepare("SELECT course_id FROM subjects WHERE id = ?");
                $stmt_course->execute([$subject_id]);
                $course_id = $stmt_course->fetchColumn();
                if ($course_id && !in_array($course_id, $course_ids)) {
                    $course_ids[] = $course_id;
                }
            }
            // Insert student-course relationships for each unique course
            $stmt_course_link = $pdo->prepare("INSERT IGNORE INTO student_courses (student_id, course_id) VALUES (?, ?)");
            foreach ($course_ids as $course_id) {
                $stmt_course_link->execute([$new_student_id, $course_id]);
            }

            $pdo->commit();
            set_message('success', 'Student added successfully and linked to course.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_message('error', 'Database error adding student or linking to course: ' . $e->getMessage());
            // If user account was created, consider rolling back or marking for cleanup
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



        <form action="add.php" method="POST">
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
                                <label for="student_id" class="form-label">Student ID</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>" readonly required>
                                </div>
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
                            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Course and Subject Selection</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="year" class="form-label">Academic Year</label>
                                <input type="number" class="form-control" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" required min="1900" max="2100">
                            </div>

                            <?php foreach ($courses as $course_id => $course): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-light py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initial rounded-circle bg-primary-subtle text-primary me-2" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                                <?php echo strtoupper(substr($course['name'], 0, 1)); ?>
                                            </div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($course['name']); ?></h6>
                                            <span class="badge bg-primary-subtle text-primary ms-2">
                                                <?php echo htmlspecialchars($course['code']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if (!empty($course['subjects'])): ?>
                                        <div class="card-body py-2">
                                            <?php foreach ($course['subjects'] as $subject): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input subject-checkbox" 
                                                           type="checkbox" 
                                                           name="subjects[]" 
                                                           value="<?php echo $subject['id']; ?>" 
                                                           id="subject_<?php echo $subject['id']; ?>"
                                                           data-course-id="<?php echo $course_id; ?>"
                                                           <?php echo in_array($subject['id'], $selected_subjects) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label d-flex justify-content-between align-items-center" for="subject_<?php echo $subject['id']; ?>">
                                                        <span><?php echo htmlspecialchars($subject['name']); ?></span>
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            <?php echo htmlspecialchars($subject['code']); ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="card-body py-2">
                                            <p class="text-muted mb-0"><small>No subjects available for this course</small></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
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

                <!-- User Account Information -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-user-lock me-2"></i>User Account</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="user_link_option" class="form-label">Account Setup</label>
                                <select class="form-select" id="user_link_option" name="user_link_option">
                                    <option value="create_new" <?php echo ($user_link_option == 'create_new') ? 'selected' : ''; ?>>Create New User Account (Role: Student)</option>
                                    <?php if (!empty($unlinked_students)): ?>
                                        <option value="link_existing" <?php echo ($user_link_option == 'link_existing') ? 'selected' : ''; ?>>Link to Existing Unlinked Student User</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div id="linked_user_select_div" style="display: <?php echo ($user_link_option == 'link_existing') ? 'block' : 'none'; ?>;">
                                <div class="mb-3">
                                    <label for="linked_user_id" class="form-label">Select User</label>
                                    <select class="form-select" id="linked_user_id" name="linked_user_id">
                                        <option value="">-- Select an unlinked student user --</option>
                                        <?php foreach ($unlinked_students as $user): ?>
                                            <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo ($linked_user_id == $user['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['email']) . ')'; ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
                            <i class="fas fa-save me-2"></i>Add Student
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

