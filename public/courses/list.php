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

if (!empty($search_query)) {
    $search_sql = " WHERE name LIKE :search_term OR code LIKE :search_term OR description LIKE :search_term";
}

// Get total number of courses for pagination
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM courses" . $search_sql);
if (!empty($search_query)) {
    $stmt_count->bindValue(':search_term', "%" . $search_query . "%", PDO::PARAM_STR);
}
$stmt_count->execute();
$total_courses = $stmt_count->fetchColumn();
$total_pages = ceil($total_courses / $limit);

// Fetch courses with pagination and search
$sql = "SELECT c.id, c.name, c.code, c.description,
        (SELECT COUNT(*) FROM subjects s WHERE s.course_id = c.id) as subject_count,
        GROUP_CONCAT(s.name SEPARATOR ', ') as subject_list 
        FROM courses c 
        LEFT JOIN subjects s ON c.id = s.course_id " . 
        ($search_sql ? str_replace("WHERE", "WHERE (", $search_sql) . ")" : "") . 
        " GROUP BY c.id 
        ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

if (!empty($search_query)) {
    $stmt->bindValue(':search_term', "%" . $search_query . "%", PDO::PARAM_STR);
}

// Bind limit and offset parameters as integers
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = get_message();
?>


    <div class="container-fluid px-4">
        <!-- Search and Stats Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Course Management</h5>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex gap-2">
                            <a href="../subjects/list.php" class="btn btn-info-subtle text-info">
                                <i class="fas fa-book me-2"></i>Subjects
                            </a>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Course
                            </a>
                        </div>
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
                                       placeholder="Search by name, code, or description" 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="btn btn-primary px-4">Search</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex align-items-center bg-light rounded p-3">
                            <div class="rounded-circle bg-primary-subtle p-2">
                                <i class="fas fa-graduation-cap text-primary"></i>
                            </div>
                            <div class="ms-3">
                                <div class="text-muted small">Total Courses</div>
                                <div class="h5 mb-0"><?php echo $total_courses; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($courses)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Courses Found</h5>
                    <p class="mb-0">Try adjusting your search criteria or add new courses.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0">Course List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Course</th>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Subjects</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initial rounded-circle bg-primary-subtle text-primary me-3">
                                                    <?php echo strtoupper(substr($course['name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($course['name']); ?></div>
                                                    <div class="text-muted small">ID: <?php echo htmlspecialchars($course['id']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                <?php echo htmlspecialchars($course['code'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo htmlspecialchars($course['description'] ?? ''); ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <div class="fw-semibold small"><?php echo (int)($course['subject_count'] ?? 0); ?> Subject(s)</div>
                                                <div class="text-muted small text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($course['subject_list'] ?? 'No subjects'); ?>">
                                                    <?php echo htmlspecialchars($course['subject_list'] ?? 'No subjects'); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="edit.php?id=<?php echo htmlspecialchars($course['id']); ?>" 
                                                   class="btn btn-sm btn-warning-subtle text-warning" 
                                                   title="Edit Course">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger-subtle text-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal" 
                                                        data-course-id="<?php echo htmlspecialchars($course['id']); ?>" 
                                                        data-course-name="<?php echo htmlspecialchars($course['name']); ?>"
                                                        title="Delete Course">
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
                                <i class="fas fa-trash fa-2x"></i>
                            </div>
                            <h5>Delete Course</h5>
                            <p class="mb-0">Are you sure you want to delete course <strong id="modalCourseName"></strong>?</p>
                            <p class="text-muted small mb-0">Course ID: <span id="modalCourseId"></span></p>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            This action cannot be undone. If this course has subjects linked to it, you will not be able to delete it.
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteForm" action="delete.php" method="POST" style="display: inline;">
                            <input type="hidden" name="course_id" id="deleteCourseId">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete Course
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
                    var courseId = button.getAttribute('data-course-id');
                    var courseName = button.getAttribute('data-course-name');
                    
                    // Update the modal's content
                    document.getElementById('modalCourseName').textContent = courseName;
                    document.getElementById('modalCourseId').textContent = courseId;
                    document.getElementById('deleteCourseId').value = courseId;
                });
            }
        });
    </script>
    