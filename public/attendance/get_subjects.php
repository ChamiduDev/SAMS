<?php
// Start session and include required files
session_start();
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Set headers
header('Content-Type: application/json');

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || (!has_role('admin') && !has_role('teacher'))) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$pdo = get_pdo_connection();
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$subjects = [];

if ($course_id) {
    try {
        if (has_role('teacher')) {
            // Teachers only see subjects they are assigned to
            $stmt = $pdo->prepare("
                SELECT s.id, s.name, s.course_id 
                FROM subjects s 
                WHERE s.course_id = ? 
                AND s.teacher_id = ? 
                ORDER BY s.name
            ");
            $stmt->execute([$course_id, $_SESSION['user_id']]);
        } else {
            // Admins see all subjects for the course
            $stmt = $pdo->prepare("
                SELECT s.id, s.name, s.course_id 
                FROM subjects s 
                WHERE s.course_id = ? 
                ORDER BY s.name
            ");
            $stmt->execute([$course_id]);
        }
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching subjects: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
        exit;
    }
}

echo json_encode($subjects);
exit;
?>
