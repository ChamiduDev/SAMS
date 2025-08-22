<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug information
error_log('Session user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log('GET id: ' . (isset($_GET['id']) ? $_GET['id'] : 'not set'));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/utils.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to view subject details.";
    header('Location: ' . BASE_URL . 'public/login.php');
    exit();
}

// Validate subject ID
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] <= 0) {
    $_SESSION['error'] = "Invalid subject ID provided.";
    header('Location: list.php');
    exit();
}

$subject_id = intval($_GET['id']);
error_log('Processing subject_id: ' . $subject_id);

$pdo = get_pdo_connection();

try {
    // Get subject details with course and teacher information
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.name as course_name,
            c.code as course_code,
            u.username as teacher_name,
            (
                SELECT COUNT(DISTINCT scs.student_id) 
                FROM student_course_subjects scs 
                WHERE scs.subject_id = s.id
            ) as student_count
        FROM subjects s
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN users u ON s.teacher_id = u.id
        WHERE s.id = ?
    ");
    
    error_log('Executing query for subject_id: ' . $subject_id);
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log('Query result: ' . ($subject ? 'Subject found' : 'Subject not found'));

        if (!$subject) {
        $_SESSION['error'] = "Subject not found.";
        header('Location: list.php');
        exit();
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $_SESSION['error'] = "Database error occurred.";
    header('Location: list.php');
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('Location: list.php');
    exit;
}
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="row page-titles mx-0">
        <div class="col-sm-6 p-md-0">
            <div class="welcome-text">
                <h4>Subject Details</h4>
            </div>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="list.php">Subjects</a></li>
                <li class="breadcrumb-item active">View Subject</li>
            </ol>
        </div>
    </div>

    <?php display_message(get_message()); ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Subject Information</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th width="200">Subject ID</th>
                                    <td><?php echo htmlspecialchars($subject['id']); ?></td>
                                </tr>
                                <tr>
                                    <th>Subject Name</th>
                                    <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Subject Code</th>
                                    <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                </tr>
                                <tr>
                                    <th>Course</th>
                                    <td>
                                        <?php echo htmlspecialchars($subject['course_name']); ?>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($subject['course_code']); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Teacher</th>
                                    <td><?php echo htmlspecialchars($subject['teacher_name'] ?? 'Not Assigned'); ?></td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td><?php echo nl2br(htmlspecialchars($subject['description'] ?? '')); ?></td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td><?php echo htmlspecialchars($subject['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <th>Number of Students</th>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($subject['student_count']); ?> students enrolled
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-3">
        <div class="col-12">
            <a href="list.php" class="btn btn-secondary">Back to Subject List</a>
            <a href="edit.php?id=<?php echo htmlspecialchars($subject['id']); ?>" class="btn btn-primary">Edit Subject</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
