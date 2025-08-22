<?php
require_once '../includes/header.php';

// Check if user has permission to mark attendance (only admin and teachers)
if (!has_role('admin') && !has_role('teacher')) {
    header("Location: " . BASE_URL . "public/unauthorized.php");
    exit;
}

$pdo = get_pdo_connection();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Add JavaScript for dynamic subject loading
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('course_id');
    const subjectSelect = document.getElementById('subject_id');
    
    function loadSubjects(courseId) {
        if (!courseId) {
            subjectSelect.innerHTML = '<option value="">Select Course First</option>';
            subjectSelect.disabled = true;
            return;
        }

        // Show loading state
        subjectSelect.disabled = true;
        subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';

        // Fetch subjects using POST request
        fetch('get_subjects.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'course_id=' + encodeURIComponent(courseId) + '&csrf_token=' + encodeURIComponent('<?php echo $csrf_token; ?>')
        })
        .then(response => response.json())
        .then(data => {
            subjectSelect.disabled = false;
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }

            data.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject.id;
                option.textContent = subject.name;
                subjectSelect.appendChild(option);
            });

            // If there was a previously selected subject, try to reselect it
            const previouslySelected = subjectSelect.getAttribute('data-selected');
            if (previouslySelected) {
                subjectSelect.value = previouslySelected;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
            subjectSelect.disabled = true;
        });
    }

    // Add change event listener to course select
    if (courseSelect) {
        courseSelect.addEventListener('change', function() {
            loadSubjects(this.value);
        });

        // Load subjects if course is already selected
        if (courseSelect.value) {
            loadSubjects(courseSelect.value);
        }
    }
});
</script>
<?php

// Initialize variables
$courses = [];
$subjects = [];
$students = [];
$selected_course_id = $_POST['course_id'] ?? '';
$selected_subject_id = $_POST['subject_id'] ?? '';
$selected_date = $_POST['attendance_date'] ?? date('Y-m-d');
$attendance_marked = false;
$error_message = '';
$success_message = '';

// Validate selected date
if ($selected_date) {
    $today = new DateTime();
    $selected = new DateTime($selected_date);
    if ($selected > $today) {
        $error_message = "Cannot mark attendance for future dates.";
        $selected_date = date('Y-m-d');
    }
}

// Fetch courses based on user role
try {
    if (has_role('admin')) {
        $stmt = $pdo->query("SELECT id, name FROM courses ORDER BY name");
    } else {
        // For teachers, only show assigned courses
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.id, c.name 
            FROM courses c 
            JOIN subjects s ON c.id = s.course_id 
            WHERE s.teacher_id = ? 
            ORDER BY c.name
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $error_message = "Error loading courses.";
}

// Handle form submission for marking attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    // Check if CSRF token is present
    if (!isset($_POST['csrf_token'])) {
        $error_message = "Security token missing. Please refresh the page and try again.";
    }
    // Verify CSRF token
    else if (!verify_csrf_token($_POST['csrf_token'])) {
        // Generate a new token for the form
        $csrf_token = generate_csrf_token();
        $error_message = "Security validation failed. A new form has been generated. Please try again.";
    } else {
        // Validate input data
        $selected_course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $selected_subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
        $selected_date = filter_input(INPUT_POST, 'attendance_date', FILTER_SANITIZE_SPECIAL_CHARS);
        $attendance_data = $_POST['attendance'] ?? [];

        // Validate date is not in future
        $today = new DateTime();
        $selected = new DateTime($selected_date);
        if ($selected > $today) {
            $error_message = "Cannot mark attendance for future dates.";
        }
        // Validate course and subject
        else if (!$selected_course_id || !$selected_subject_id || !isValidDate($selected_date)) {
            $error_message = "Invalid course, subject, or date provided.";
        }
        // Validate attendance data
        else if (empty($attendance_data)) {
            $error_message = "No attendance data submitted.";
        }
        // Validate teacher's permission for the subject
        else if (!has_role('admin')) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$selected_subject_id, $_SESSION['user_id']]);
            if ($stmt->fetchColumn() == 0) {
                $error_message = "You don't have permission to mark attendance for this subject.";
            }
        }
        
        if (empty($error_message)) {
            try {
                $pdo->beginTransaction();
                $marked_count = 0;
                $duplicate_count = 0;

                // Get existing attendance records
                $stmt_check = $pdo->prepare("
                    SELECT student_id, status 
                    FROM attendance 
                    WHERE subject_id = ? AND date = ?
                ");
                $stmt_check->execute([$selected_subject_id, $selected_date]);
                $existing_attendance = $stmt_check->fetchAll(PDO::FETCH_KEY_PAIR);

                // Prepare statements
                $stmt_insert = $pdo->prepare("
                    INSERT INTO attendance (student_id, subject_id, date, status, marked_by) 
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = ?, marked_by = ?
                ");

                $stmt_enrollment = $pdo->prepare("
                    SELECT s.id 
                    FROM students s
                    JOIN student_courses sc ON s.id = sc.student_id
                    JOIN subjects sub ON sc.course_id = sub.course_id
                    WHERE s.id = ? AND sub.id = ? AND s.status = 'active'
                ");

                // Process each attendance record
                foreach ($attendance_data as $student_id => $status) {
                    $student_id = filter_var($student_id, FILTER_VALIDATE_INT);
                    $status = filter_var($status, FILTER_SANITIZE_SPECIAL_CHARS);

                    if (!$student_id || !in_array($status, ['present', 'absent', 'late'])) {
                        error_log("Invalid student ID or status: Student ID: $student_id, Status: $status");
                        continue;
                    }

                    // Validate student enrollment
                    $stmt_enrollment->execute([$student_id, $selected_subject_id]);
                    if (!$stmt_enrollment->fetch()) {
                        error_log("Student $student_id not enrolled in subject $selected_subject_id");
                        continue;
                    }

                    try {
                        // Insert or update attendance
                        $stmt_insert->execute([
                            $student_id,
                            $selected_subject_id,
                            $selected_date,
                            $status,
                            $_SESSION['user_id'],
                            $status,
                            $_SESSION['user_id']
                        ]);

                        if (isset($existing_attendance[$student_id])) {
                            if ($existing_attendance[$student_id] !== $status) {
                                $duplicate_count++;
                            }
                        } else {
                            $marked_count++;
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() == '23000') { // Duplicate entry error
                            error_log("Duplicate attendance entry prevented: Student $student_id, Date $selected_date");
                            $duplicate_count++;
                        } else {
                            throw $e;
                        }
                    }
                }

                $pdo->commit();
                
                // Prepare success message
                $message_parts = [];
                if ($marked_count > 0) {
                    $message_parts[] = "Marked attendance for $marked_count new students";
                }
                if ($duplicate_count > 0) {
                    $message_parts[] = "Updated attendance for $duplicate_count existing students";
                }
                
                if (!empty($message_parts)) {
                    $success_message = implode(" and ", $message_parts) . ".";
                    $attendance_marked = true;
                } else {
                    $error_message = "No changes were made to attendance records.";
                }
                
                // Log the attendance marking activity
                $log_message = "Attendance marked for subject ID: $selected_subject_id, Date: $selected_date, " . 
                             "New entries: $marked_count, Updated: $duplicate_count, By user: " . $_SESSION['user_id'];
                error_log($log_message);
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error marking attendance: " . $e->getMessage());
                $error_message = "An error occurred while marking attendance. Please try again or contact support if the problem persists.";
            }
        }
    }
}

// Fetch students if course, subject, and date are selected (for initial load or after submission)
if ($selected_course_id && $selected_subject_id && isValidDate($selected_date)) {
    try {
        // Fetch students enrolled in the selected course
        $stmt_students = $pdo->prepare("
            SELECT s.id, s.first_name, s.last_name
            FROM students s
            JOIN student_courses sc ON s.id = sc.student_id
            WHERE sc.course_id = ?
            ORDER BY s.last_name, s.first_name
        ");
        $stmt_students->execute([$selected_course_id]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        // Fetch existing attendance for pre-filling the form if available
        $existing_attendance = [];
        $stmt_existing = $pdo->prepare("SELECT student_id, status FROM attendance WHERE subject_id = ? AND date = ?");
        $stmt_existing->execute([$selected_subject_id, $selected_date]);
        while ($row = $stmt_existing->fetch(PDO::FETCH_ASSOC)) {
            $existing_attendance[$row['student_id']] = $row['status'];
        }

        // If attendance was just marked, use the submitted data to pre-fill
        if ($attendance_marked && isset($_POST['attendance'])) {
            foreach ($_POST['attendance'] as $student_id => $status) {
                $existing_attendance[$student_id] = $status;
            }
        }

    } catch (PDOException $e) {
        error_log("Error fetching students or existing attendance: " . $e->getMessage());
        $error_message = "Error loading students or existing attendance.";
    }
}

// Fetch subjects based on selected course (for initial load or if course was selected)
if ($selected_course_id) {
    try {
        if (has_role('teacher')) {
            // Teachers only see subjects they are assigned to
            $stmt_subjects = $pdo->prepare("SELECT id, name FROM subjects WHERE course_id = ? AND teacher_id = ? ORDER BY name");
            $stmt_subjects->execute([$selected_course_id, $_SESSION['user_id']]);
        } else {
            // Admins see all subjects for the course
            $stmt_subjects = $pdo->prepare("SELECT id, name FROM subjects WHERE course_id = ? ORDER BY name");
            $stmt_subjects->execute([$selected_course_id]);
        }
        $subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching subjects: " . $e->getMessage());
        $error_message = "Error loading subjects.";
    }
}

?>


    <div class="container-fluid px-4">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0">
                    <i class="fas fa-clipboard-check me-2"></i>Mark Attendance
                </h5>
                <a href="list.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Records
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="mark.php" id="attendanceForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-primary">Select Class Details</h6>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="course_id" class="form-label">Course</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-graduation-cap"></i>
                                        </span>
                                        <select class="form-select" id="course_id" name="course_id" required>
                                            <option value="">Select Course</option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?php echo htmlspecialchars($course['id']); ?>" <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($course['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="subject_id" class="form-label">Subject</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-book"></i>
                                        </span>
                                        <select class="form-select" id="subject_id" name="subject_id" required>
                                            <option value="">Select Subject</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo htmlspecialchars($subject['id']); ?>" <?php echo ($selected_subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($subject['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="attendance_date" class="form-label">Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" value="<?php echo htmlspecialchars($selected_date); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary" name="load_students">
                                    <i class="fas fa-users me-2"></i>Load Students
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($students)): ?>
                    <form method="POST" action="mark.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($selected_course_id); ?>">
                        <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($selected_subject_id); ?>">
                        <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selected_date); ?>">

                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-subtitle text-primary mb-0">
                                        <i class="fas fa-users me-2"></i>Student Attendance
                                    </h6>
                                    <div class="btn-group" role="group" aria-label="Mark all as">
                                        <button type="button" class="btn btn-sm btn-success mark-all" data-status="present">
                                            <i class="fas fa-check me-1"></i>All Present
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger mark-all" data-status="absent">
                                            <i class="fas fa-times me-1"></i>All Absent
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-3">Student Name</th>
                                                <th class="py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <?php $current_status = $existing_attendance[$student['id']] ?? 'present'; ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-user-graduate text-primary me-2"></i>
                                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <input type="radio" class="btn-check" name="attendance[<?php echo htmlspecialchars($student['id']); ?>]" 
                                                                   id="status_present_<?php echo htmlspecialchars($student['id']); ?>" 
                                                                   value="present" <?php echo ($current_status === 'present') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-success btn-sm" for="status_present_<?php echo htmlspecialchars($student['id']); ?>">
                                                                <i class="fas fa-check"></i> Present
                                                            </label>

                                                            <input type="radio" class="btn-check" name="attendance[<?php echo htmlspecialchars($student['id']); ?>]" 
                                                                   id="status_absent_<?php echo htmlspecialchars($student['id']); ?>" 
                                                                   value="absent" <?php echo ($current_status === 'absent') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-danger btn-sm" for="status_absent_<?php echo htmlspecialchars($student['id']); ?>">
                                                                <i class="fas fa-times"></i> Absent
                                                            </label>

                                                            <input type="radio" class="btn-check" name="attendance[<?php echo htmlspecialchars($student['id']); ?>]" 
                                                                   id="status_late_<?php echo htmlspecialchars($student['id']); ?>" 
                                                                   value="late" <?php echo ($current_status === 'late') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-warning btn-sm" for="status_late_<?php echo htmlspecialchars($student['id']); ?>">
                                                                <i class="fas fa-clock"></i> Late
                                                            </label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="list.php" class="btn btn-light">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" name="mark_attendance">
                                <i class="fas fa-save me-2"></i>Save Attendance
                            </button>
                        </div>
                    </form>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.querySelectorAll('.mark-all').forEach(button => {
                                button.addEventListener('click', function() {
                                    const status = this.dataset.status;
                                    document.querySelectorAll(`input[value="${status}"]`).forEach(radio => {
                                        radio.checked = true;
                                    });
                                });
                            });
                        });
                    </script>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_students']) && !$error_message): ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>No students found for the selected course and subject.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
