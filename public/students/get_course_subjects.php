<?php
require_once '../../config/config.php';
require_once '../../config/utils.php';

$pdo = get_pdo_connection();

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$subjects = [];

if ($course_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.id, s.name, s.code
            FROM subjects s
            JOIN course_subjects cs ON s.id = cs.subject_id
            WHERE cs.course_id = ?
            ORDER BY s.name
        ");
        $stmt->execute([$course_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($subjects);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error fetching subjects']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid course ID']);
}
