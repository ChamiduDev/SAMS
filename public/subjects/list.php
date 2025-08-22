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
    $join_sql = ' JOIN courses c ON s.course_id = c.id'; // Always join for course info

if (!empty($search_query)) {
    $search_sql = " WHERE s.name LIKE :search_term OR s.code LIKE :search_term OR c.name LIKE :search_term OR c.code LIKE :search_term";
}

// Get total number of subjects for pagination
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM subjects s" . $join_sql . $search_sql);
if (!empty($search_query)) {
    $stmt_count->bindValue(':search_term', "%" . $search_query . "%", PDO::PARAM_STR);
}
$stmt_count->execute();
$total_subjects = $stmt_count->fetchColumn();
$total_pages = ceil($total_subjects / $limit);

// Fetch subjects with pagination and search
$sql = "SELECT s.id, s.name, s.code, 
        c.id as course_id, c.name as course_name, c.code as course_code,
        u.username as teacher_name,
        (SELECT COUNT(*) FROM student_course_subjects scs WHERE scs.subject_id = s.id) as student_count
        FROM subjects s" . 
        $join_sql . 
        " LEFT JOIN users u ON s.teacher_id = u.id" .
        $search_sql . 
        " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

if (!empty($search_query)) {
    $stmt->bindValue(':search_term', "%" . $search_query . "%", PDO::PARAM_STR);
}

// Bind limit and offset parameters as integers
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = get_message();
?>


    <div class="container-fluid px-4">
        <!-- Search and Stats Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Subject Management</h5>
                    </div>
                    <div class="col-auto">
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Subject
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <form action="list.php" method="GET">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                       placeholder="Search by subject name, code or course" 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="btn btn-primary px-4">Search</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex align-items-center bg-light rounded p-3">
                            <div class="rounded-circle bg-primary-subtle p-2">
                                <i class="fas fa-book text-primary"></i>
                            </div>
                            <div class="ms-3">
                                <div class="text-muted small">Total Subjects</div>
                                <div class="h5 mb-0"><?php echo $total_subjects; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php display_message($message); ?>

        <?php if (empty($subjects)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Subjects Found</h5>
                    <p class="mb-0">Try adjusting your search criteria or add new subjects.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0">Subject List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Subject</th>
                                    <th>Code</th>
                                    <th>Course</th>
                                    <th>Teacher</th>
                                    <th>Students</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initial rounded-circle bg-info-subtle text-info me-3">
                                                    <?php echo strtoupper(substr($subject['name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($subject['name']); ?></div>
                                                    <div class="text-muted small">ID: <?php echo htmlspecialchars($subject['id']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <?php echo htmlspecialchars($subject['code']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="../courses/edit.php?id=<?php echo htmlspecialchars($subject['course_id']); ?>" 
                                                   class="text-decoration-none">
                                                    <div class="avatar-initial rounded bg-success-subtle text-success me-2" style="padding: 0.5rem;">
                                                        <?php echo strtoupper(substr($subject['course_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium"><?php echo htmlspecialchars($subject['course_name']); ?></div>
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            <?php echo htmlspecialchars($subject['course_code']); ?>
                                                        </span>
                                                    </div>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($subject['teacher_name']): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-initial rounded-circle bg-warning-subtle text-warning me-2">
                                                        <i class="fas fa-chalkboard-teacher"></i>
                                                    </div>
                                                    <div class="small"><?php echo htmlspecialchars($subject['teacher_name']); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    <i class="fas fa-user-slash me-1"></i>Not Assigned
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initial rounded-circle bg-primary-subtle text-primary me-2">
                                                    <i class="fas fa-users"></i>
                                                </div>
                                                <div class="small">
                                                    <?php echo (int)$subject['student_count']; ?> Student(s)
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="edit.php?id=<?php echo htmlspecialchars($subject['id']); ?>" 
                                                   class="btn btn-sm btn-warning-subtle text-warning" 
                                                   title="Edit Subject">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo htmlspecialchars(BASE_URL . 'public/subjects/view.php?id=' . $subject['id']); ?>" 
                                                   class="btn btn-sm btn-info-subtle text-info" 
                                                   title="View Subject Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger-subtle text-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal" 
                                                        data-subject-id="<?php echo htmlspecialchars($subject['id']); ?>" 
                                                        data-subject-name="<?php echo htmlspecialchars($subject['name']); ?>"
                                                        title="Delete Subject">
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
                                <i class="fas fa-book-dead fa-2x"></i>
                            </div>
                            <h5>Delete Subject</h5>
                            <p class="mb-0">Are you sure you want to delete subject <strong id="modalSubjectName"></strong>?</p>
                            <p class="text-muted small mb-0">Subject ID: <span id="modalSubjectId"></span></p>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            This action cannot be undone. Please be certain.
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteForm" action="delete.php" method="POST" style="display: inline;">
                            <input type="hidden" name="subject_id" id="deleteSubjectId">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete Subject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Handle delete modal
        document.addEventListener('DOMContentLoaded', function() {
            var deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    // Button that triggered the modal
                    var button = event.relatedTarget;
                    
                    // Extract info from data-* attributes
                    var subjectId = button.getAttribute('data-subject-id');
                    var subjectName = button.getAttribute('data-subject-name');
                    
                    // Update the modal's content
                    document.getElementById('modalSubjectName').textContent = subjectName;
                    document.getElementById('modalSubjectId').textContent = subjectId;
                    document.getElementById('deleteSubjectId').value = subjectId;
                });
            }
        });
    </script>
    