<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';

$pdo = get_pdo_connection();
$current_date = date('Y-m-d');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<div class='container mt-5'>";
echo "<div class='alert alert-info'>Debug Information:</div>";

// Debug output for session and student info
echo "<div class='alert alert-info'>";
echo "Session User ID: " . $_SESSION['user_id'] . "<br>";
echo "Student ID: " . ($student['id'] ?? 'Not set') . "<br>";
echo "Student Name: " . ($student['full_name'] ?? 'Not set') . "<br>";
echo "</div>";

try {
    // First query - get student's courses and subjects
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            c.id as course_id,
            c.name as course_name,
            c.code as course_code,
            s.id as subject_id,
            s.name as subject_name,
            s.code as subject_code
        FROM students st
        JOIN student_courses sc ON st.id = sc.student_id
        JOIN courses c ON sc.course_id = c.id
        JOIN subjects s ON s.course_id = c.id
        WHERE st.id = ?
    ");
    
    echo "<div class='alert alert-info'>Executing query with student ID: " . $student['id'] . "</div>";
    
    $stmt->execute([$student['id']]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>Found " . count($subjects) . " subjects</div>";
    
    if (!empty($subjects)) {
        echo "<div class='alert alert-info'>Subjects found:<br>";
        foreach ($subjects as $subject) {
            echo htmlspecialchars($subject['subject_name']) . " (" . htmlspecialchars($subject['subject_code']) . ")<br>";
        }
        echo "</div>";
        
        // Get subject IDs for the student
        $subject_ids = array_unique(array_column($subjects, 'subject_id'));
        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        
        // Second query - get exams
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.title,
                e.exam_date,
                e.total_marks,
                e.weight,
                s.name as subject_name,
                s.code as subject_code,
                c.name as course_name,
                CASE 
                    WHEN e.exam_date < CURDATE() THEN 'past'
                    WHEN e.exam_date = CURDATE() THEN 'today'
                    ELSE 'upcoming'
                END as exam_status,
                er.marks_obtained,
                er.id as result_id
            FROM exams e
            JOIN subjects s ON e.subject_id = s.id
            JOIN courses c ON s.course_id = c.id
            LEFT JOIN exam_results er ON e.id = er.exam_id AND er.student_id = ?
            WHERE e.subject_id IN ($placeholders)
            ORDER BY e.exam_date ASC, e.id ASC
        ");
        
        // Combine student_id and subject_ids for the query
        $params = array_merge([$student['id']], $subject_ids);
        
        echo "<div class='alert alert-info'>Executing exams query with parameters: " . implode(', ', $params) . "</div>";
        
        $stmt->execute($params);
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='alert alert-info'>Found " . count($exams) . " exams</div>";
        
        if (!empty($exams)) {
            echo "<div class='alert alert-info'>Exams found:<br>";
            foreach ($exams as $exam) {
                echo htmlspecialchars($exam['title']) . " - " . 
                     htmlspecialchars($exam['subject_name']) . " - " . 
                     $exam['exam_date'] . "<br>";
            }
            echo "</div>";
        }
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    error_log("Exams fetch failed: " . $e->getMessage());
}

echo "</div>";
?>
