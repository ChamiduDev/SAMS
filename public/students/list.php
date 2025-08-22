<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

// Pagination settings
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search_query = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$search_sql = '';

    if (!empty($_GET['search'])) {
        $search_term = '%' . $_GET['search'] . '%';
        $search_sql = " WHERE (
            s.student_id LIKE :search OR 
            s.first_name LIKE :search OR 
            s.last_name LIKE :search OR 
            s.year LIKE :search OR
            s.gender LIKE :search OR
            s.contact_no LIKE :search OR
            c.name LIKE :search OR
            sub.name LIKE :search
        ) AND s.status = 'active'";
    }

// Get total number of students for pagination
$stmt_count = $pdo->prepare("
    SELECT COUNT(DISTINCT s.id) 
    FROM students s
    LEFT JOIN student_course_subjects scs ON s.id = scs.student_id
    LEFT JOIN courses c ON scs.course_id = c.id
    LEFT JOIN subjects sub ON scs.subject_id = sub.id" . 
    ($search_sql ? $search_sql : " WHERE s.status = 'active'"));
if (!empty($search_query)) {
    $stmt_count->bindValue(':search', $search_term, PDO::PARAM_STR);
}
$stmt_count->execute();
$total_students = $stmt_count->fetchColumn();
$total_pages = ceil($total_students / $limit);

// Fetch students with pagination and search
$sql = "SELECT s.id, s.student_id, s.first_name, s.last_name, s.year, s.gender, s.contact_no,
        GROUP_CONCAT(DISTINCT c.name) as courses,
        GROUP_CONCAT(DISTINCT sub.name) as subjects
        FROM students s
        LEFT JOIN student_course_subjects scs ON s.id = scs.student_id
        LEFT JOIN courses c ON scs.course_id = c.id
        LEFT JOIN subjects sub ON scs.subject_id = sub.id" . 
        ($search_sql ? str_replace("WHERE (", "WHERE (", $search_sql) : " WHERE s.status = 'active'") . 
        " GROUP BY s.id ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

if (!empty($search_query)) {
    $stmt->bindValue(':search', $search_term, PDO::PARAM_STR);
}

// Bind limit and offset parameters as integers
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = get_message();
?>


    <div class="container-fluid px-4">
        

        <!-- Combined Stats and Search Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Student Management</h5>
                    </div>
                    <div class="col-auto me-3">
                        <div class="d-flex gap-2">
                            <a href="export_pdf.php<?php echo !empty($_GET['search']) ? '?search=' . htmlspecialchars($_GET['search']) : ''; ?>" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i>Export PDF
                            </a>
                            <a href="export_excel.php<?php echo !empty($_GET['search']) ? '?search=' . htmlspecialchars($_GET['search']) : ''; ?>" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-file-excel me-1"></i>Export Excel
                            </a>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary-dark me-3">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <div class="stat-label text-muted">Total Students</div>
                                <div class="stat-number h4 mb-0"><?php echo $total_students; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <form action="list.php" method="GET" class="d-flex gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                       placeholder="Search by ID, Name, Course, Subject, or Year" 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <a href="add.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Add New Student
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php display_message($message); ?>

        <?php if (empty($students)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Students Found</h5>
                    <p class="mb-0">Try adjusting your search criteria or add new students.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0">Student List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3">Student ID</th>
                                    <th class="py-3">Full Name</th>
                                    <th class="py-3">Year</th>
                                    <th class="py-3">Gender</th>
                                    <th class="py-3">Contact No</th>
                                    <th class="py-3">Courses</th>
                                    <th class="py-3">Subjects</th>
                                    <th class="py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="align-middle"><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initial rounded-circle bg-light text-primary me-2">
                                                    <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                                </div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                            </div>
                                        </td>
                                        <td class="align-middle"><?php echo htmlspecialchars($student['year']); ?></td>
                                        <td class="align-middle">
                                            <span class="badge bg-<?php echo $student['gender'] === 'Male' ? 'primary-subtle text-primary' : 'info-subtle text-info'; ?>">
                                                <?php echo htmlspecialchars($student['gender']); ?>
                                            </span>
                                        </td>
                                        <td class="align-middle"><?php echo htmlspecialchars($student['contact_no']); ?></td>
                                        <td class="align-middle">
                                            <div class="small text-muted">
                                                <?php echo htmlspecialchars($student['courses'] ?? 'None'); ?>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="small text-muted">
                                                <?php echo htmlspecialchars($student['subjects'] ?? 'None'); ?>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex gap-2">
                                                <a href="view.php?id=<?php echo htmlspecialchars($student['id']); ?>" class="btn btn-sm btn-primary-subtle text-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo htmlspecialchars($student['id']); ?>" class="btn btn-sm btn-warning-subtle text-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger-subtle text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-student-id="<?php echo htmlspecialchars($student['id']); ?>" data-student-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-initial rounded-circle bg-danger-subtle text-danger mx-auto mb-3" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-times fa-2x"></i>
                            </div>
                            <h5>Delete Student Record</h5>
                            <p class="mb-0">Are you sure you want to delete student <strong id="modalStudentName"></strong>?</p>
                            <p class="text-muted small mb-0">Student ID: <span id="modalStudentId"></span></p>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            This action will mark the student as deleted and cannot be undone.
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteForm" action="delete.php" method="POST" style="display: inline;">
                            <input type="hidden" name="student_id" id="deleteStudentId">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete Student
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    