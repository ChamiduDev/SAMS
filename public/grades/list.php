<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/utils.php';

$pdo = get_pdo_connection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role_name'];

$exam_results = [];
$total_results = 0;
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get student ID if user is a student
$student_id = null;
if ($user_role === 'student') {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student_id = $stmt->fetchColumn();
}

$exams_list = [];
$students_list = [];
$subjects_list = [];

// Fetch filter options
try {
    $stmt = $pdo->query("SELECT id, title FROM exams ORDER BY title");
    $exams_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name");
    $students_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name");
    $subjects_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching filter options: ' . $e->getMessage());
}

// Filter parameters
$filter_exam_id = $_GET['exam_id'] ?? '';
$filter_student_id = $_GET['student_id'] ?? '';
$filter_subject_id = $_GET['subject_id'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query
$sql_base = "SELECT er.id, er.marks_obtained, er.remarks, er.created_at,
                    e.title AS exam_title, e.total_marks,
                    s.first_name, s.last_name,
                    sub.name AS subject_name,
                    u.username AS marked_by_username
             FROM exam_results er
             JOIN exams e ON er.exam_id = e.id
             JOIN students s ON er.student_id = s.id
             JOIN subjects sub ON e.subject_id = sub.id
             LEFT JOIN users u ON er.marked_by = u.id
             WHERE 1=1";
$count_sql_base = "SELECT COUNT(*) FROM exam_results er
                   JOIN exams e ON er.exam_id = e.id
                   JOIN students s ON er.student_id = s.id
                   JOIN subjects sub ON e.subject_id = sub.id
                   WHERE 1=1";

$params = [];

// Apply filters
if ($filter_exam_id) {
    $sql_base .= " AND er.exam_id = ?";
    $count_sql_base .= " AND er.exam_id = ?";
    $params[] = $filter_exam_id;
}
if ($filter_student_id) {
    $sql_base .= " AND er.student_id = ?";
    $count_sql_base .= " AND er.student_id = ?";
    $params[] = $filter_student_id;
}
if ($filter_subject_id) {
    $sql_base .= " AND e.subject_id = ?";
    $count_sql_base .= " AND e.subject_id = ?";
    $params[] = $filter_subject_id;
}

// Role-based access
if ($user_role === 'student') {
    if ($student_id) {
        $sql_base .= " AND er.student_id = ?";
        $count_sql_base .= " AND er.student_id = ?";
        $params[] = $student_id;
        
        // Hide filters for students since they should only see their own grades
        $exams_list = [];
        $students_list = [];
        $filter_exam_id = '';
        $filter_student_id = '';
        $filter_subject_id = '';
    } else {
        $sql_base .= " AND 0=1"; // No student record found for this user
        $count_sql_base .= " AND 0=1";
    }
} elseif ($user_role === 'parent') {
    $stmt_children_ids = $pdo->prepare("SELECT id FROM students WHERE parent_user_id = ?"); // Assuming parent_user_id
    $stmt_children_ids->execute([$user_id]);
    $children_ids = $stmt_children_ids->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($children_ids)) {
        $placeholders = implode(',', array_fill(0, count($children_ids), '?'));
        $sql_base .= " AND er.student_id IN ($placeholders)";
        $count_sql_base .= " AND er.student_id IN ($placeholders)";
        $params = array_merge($params, $children_ids);
    } else {
        $sql_base .= " AND 0=1";
        $count_sql_base .= " AND 0=1";
    }
}

// Search query
if ($search_query) {
    $search_term = '%' . $search_query . '%';
    $sql_base .= " AND (e.title LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR sub.name LIKE ?)";
    $count_sql_base .= " AND (e.title LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR sub.name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Get total count
try {
    $stmt_count = $pdo->prepare($count_sql_base);
    $stmt_count->execute($params);
    $total_results = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    set_message('error', 'Database error counting exam results: ' . $e->getMessage());
}

$total_pages = ceil($total_results / $records_per_page);

// Fetch results
$sql = $sql_base . " ORDER BY er.created_at DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching exam results: ' . $e->getMessage());
}

$message = get_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Grades - SAMS</title>
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
                    <i class="fas fa-graduation-cap me-2"></i>Exam Grades
                </h2>
            </div>
            <?php if (has_role('admin') || has_role('teacher')): ?>
            <div class="col-auto">
                <a href="mark.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Grade
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php display_message($message); ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="list.php" class="mb-0">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select" id="exam_id" name="exam_id">
                                    <option value="">All Exams</option>
                                    <?php foreach ($exams_list as $exam): ?>
                                        <option value="<?php echo htmlspecialchars($exam['id']); ?>" <?php echo ($filter_exam_id == $exam['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($exam['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="exam_id">Exam</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select" id="student_id" name="student_id">
                                    <option value="">All Students</option>
                                    <?php foreach ($students_list as $student): ?>
                                        <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo ($filter_student_id == $student['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="student_id">Student</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <select class="form-select" id="subject_id" name="subject_id">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects_list as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject['id']); ?>" <?php echo ($filter_subject_id == $subject['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="subject_id">Subject</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search...">
                                <label for="search">Search</label>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <a href="list.php" class="btn btn-secondary">
                                <i class="fas fa-undo me-2"></i>Clear
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($exam_results)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No exam results found matching your criteria.</h5>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Exam Title</th>
                                <th>Subject</th>
                                <th>Student Name</th>
                                <th>Marks</th>
                                <th>Remarks</th>
                                <th>Marked By</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exam_results as $result): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?php echo htmlspecialchars($result['exam_title']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                    <td>
                                        <span class="badge text-bg-<?php echo ($result['marks_obtained'] >= ($result['total_marks'] * 0.4)) ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($result['marks_obtained']); ?> / <?php echo htmlspecialchars($result['total_marks']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($result['remarks'])): ?>
                                            <span class="text-muted"><?php echo htmlspecialchars($result['remarks']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle text-muted me-2"></i>
                                            <?php echo htmlspecialchars($result['marked_by_username'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($user_role === 'admin' || $user_role === 'teacher'): ?>
                                            <a href="edit.php?id=<?php echo htmlspecialchars($result['id']); ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="if(confirm('Are you sure you want to delete this grade?')) window.location.href='delete.php?id=<?php echo htmlspecialchars($result['id']); ?>';"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-transparent pt-0">
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($filter_exam_id) ? '&exam_id=' . urlencode($filter_exam_id) : ''; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_subject_id) ? '&subject_id=' . urlencode($filter_subject_id) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($filter_exam_id) ? '&exam_id=' . urlencode($filter_exam_id) : ''; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_subject_id) ? '&subject_id=' . urlencode($filter_subject_id) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($filter_exam_id) ? '&exam_id=' . urlencode($filter_exam_id) : ''; ?><?php echo !empty($filter_student_id) ? '&student_id=' . urlencode($filter_student_id) : ''; ?><?php echo !empty($filter_subject_id) ? '&subject_id=' . urlencode($filter_subject_id) : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
