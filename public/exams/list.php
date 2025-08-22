<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$exams = [];
$total_exams = 0;
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

$search_query = $_GET['search'] ?? '';
$filter_subject_id = $_GET['subject_id'] ?? '';

$subjects_list = [];
// Fetch subjects for filter dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name");
    $subjects_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching subjects: ' . $e->getMessage());
}

// Build query
$sql_base = "SELECT e.id, e.title, e.exam_date, e.duration, e.total_marks, e.weight, s.name AS subject_name, u.username AS created_by_username
             FROM exams e
             JOIN subjects s ON e.subject_id = s.id
             LEFT JOIN users u ON e.created_by = u.id
             WHERE 1=1";
$count_sql_base = "SELECT COUNT(*) FROM exams e JOIN subjects s ON e.subject_id = s.id WHERE 1=1";

$params = [];

if ($search_query) {
    $sql_base .= " AND (e.title LIKE ? OR s.name LIKE ?)";
    $count_sql_base .= " AND (e.title LIKE ? OR s.name LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

if ($filter_subject_id) {
    $sql_base .= " AND e.subject_id = ?";
    $count_sql_base .= " AND e.subject_id = ?";
    $params[] = $filter_subject_id;
}

// Get total count
try {
    $stmt_count = $pdo->prepare($count_sql_base);
    $stmt_count->execute($params);
    $total_exams = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    set_message('error', 'Database error counting exams: ' . $e->getMessage());
}

$total_pages = ceil($total_exams / $records_per_page);

// Fetch exams
$sql = $sql_base . " ORDER BY e.exam_date DESC, e.title ASC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching exams: ' . $e->getMessage());
}

$message = get_message();
?>



    <div class="container-fluid py-4">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h2 class="mb-0">
                    <i class="fas fa-file-alt me-2 text-primary"></i>Exams Management
                </h2>
            </div>
            <div class="col-auto">
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Exam
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-4">
                <form method="GET" action="list.php">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="form-floating">
                                <input type="text" 
                                       name="search" 
                                       class="form-control" 
                                       id="searchInput"
                                       placeholder="Search by title or subject" 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <label for="searchInput">
                                    <i class="fas fa-search text-muted me-2"></i>Search Exams
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" name="subject_id" id="subjectSelect">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects_list as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject['id']); ?>" <?php echo ($filter_subject_id == $subject['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="subjectSelect">
                                    <i class="fas fa-book text-muted me-2"></i>Filter by Subject
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-center">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <a href="list.php" class="btn btn-light">
                                <i class="fas fa-undo me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

                <?php if (empty($exams)): ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>No exams found matching your criteria.</div>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3">Title</th>
                                <th class="py-3">Subject</th>
                                <th class="py-3">Date</th>
                                <th class="py-3">Duration</th>
                                <th class="py-3">Total Marks</th>
                                <th class="py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($exams)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                            <h5 class="fw-light text-muted">No exams found</h5>
                                            <p class="text-muted small mb-0">Try adjusting your search or filters</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($exams as $exam): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="icon-square bg-primary bg-opacity-10 p-2 rounded me-3">
                                                    <i class="fas fa-file-alt text-primary"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i>
                                                <?php echo htmlspecialchars($exam['subject_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="far fa-calendar me-2 text-muted"></i>
                                                <?php echo date('F d, Y', strtotime($exam['exam_date'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="far fa-clock me-2 text-muted"></i>
                                                <?php echo $exam['duration']; ?> minutes
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-star me-2 text-warning"></i>
                                                <?php echo $exam['total_marks']; ?> marks
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="view.php?id=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-sm btn-light" 
                                                   data-bs-toggle="tooltip" 
                                                   title="View Details">
                                                    <i class="fas fa-eye text-primary"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-sm btn-light"
                                                   data-bs-toggle="tooltip" 
                                                   title="Edit Exam">
                                                    <i class="fas fa-edit text-info"></i>
                                                </a>
                                                <?php if (has_role('admin')): ?>
                                                    <a href="delete.php?id=<?php echo $exam['id']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" 
                                                       class="btn btn-sm btn-light"
                                                       onclick="return confirm('Are you sure you want to delete this exam? This action cannot be undone.');"
                                                       data-bs-toggle="tooltip" 
                                                       title="Delete Exam">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Initialize tooltips -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($filter_subject_id) ? '&subject_id=' . urlencode($filter_subject_id) : ''; ?>">
                                            <i class="fas fa-chevron-left small"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($filter_subject_id) ? '&subject_id=' . urlencode($filter_subject_id) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($filter_subject_id) ? '&subject_id=' . urlencode($filter_subject_id) : ''; ?>">
                                            <i class="fas fa-chevron-right small"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <div class="text-center text-muted small mt-2">
                            Showing page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
