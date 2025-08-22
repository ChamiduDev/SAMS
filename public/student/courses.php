<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';
$pdo = get_pdo_connection();

// Get student details and enrolled courses
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        c.id as course_id,
        c.name as course_name,
        c.code as course_code,
        c.description as course_description,
        GROUP_CONCAT(DISTINCT sub.name) as subject_names,
        GROUP_CONCAT(DISTINCT sub.code) as subject_codes,
        COUNT(DISTINCT sub.id) as subject_count
    FROM students s
    JOIN student_courses sc ON s.id = sc.student_id
    JOIN courses c ON sc.course_id = c.id
    LEFT JOIN subjects sub ON c.id = sub.course_id
    WHERE s.user_id = ? AND s.status = 'active'
    GROUP BY c.id
");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Student's sidebar is already included above
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h4 class="mb-3">My Courses</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../student_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Courses</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (empty($courses)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                You are not enrolled in any courses yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0">
                                    <?php echo htmlspecialchars($course['course_name'] ?? ''); ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($course['course_code'] ?? ''); ?>)</small>
                                </h5>
                                <span class="badge bg-primary">
                                    <?php echo (int)$course['subject_count']; ?> Subjects
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Course Description</h6>
                                    <p><?php echo htmlspecialchars($course['course_description'] ?? 'No description available.'); ?></p>
                                </div>
                                
                                <div class="mb-0">
                                    <h6 class="text-muted mb-2">Subjects</h6>
                                    <div class="row g-2">
                                        <?php 
                                        if (!empty($course['subject_names'])) {
                                            $subject_names = explode(',', $course['subject_names']);
                                            $subject_codes = explode(',', $course['subject_codes']);
                                            foreach ($subject_names as $index => $subject): 
                                                if ($subject): // Only show non-empty subjects
                                            ?>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-2">
                                                        <small class="d-block text-muted">
                                                            <?php echo htmlspecialchars($subject_codes[$index] ?? 'No Code'); ?>
                                                        </small>
                                                        <?php echo htmlspecialchars($subject); ?>
                                                    </div>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach;
                                        } else { 
                                        ?>
                                            <div class="col-12">
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    No subjects have been added to this course yet.
                                                </div>
                                            </div>
                                        <?php 
                                        } 
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
}

.badge {
    font-weight: 500;
}
</style>

<?php require_once '../includes/footer.php'; ?>
