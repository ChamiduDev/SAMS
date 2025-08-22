<?php
require_once '../includes/header.php';
$pdo = get_pdo_connection();

// Get all subjects for the form
$subjects = [];
try {
    $stmt = $pdo->query("SELECT id, name, code FROM subjects ORDER BY name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Error fetching subjects: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $courses = $_POST['courses'] ?? [];
    $subjects = $_POST['subjects'] ?? [];
    
    if (empty($courses)) {
        $errors[] = 'At least one course must be selected.';
    }
    
    if (empty($subjects)) {
        $errors[] = 'At least one subject must be selected for each course.';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert student record
            $stmt = $pdo->prepare("
                INSERT INTO students (
                    user_id, student_id, first_name, last_name, 
                    dob, gender, year, address, contact_no
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $final_user_id,
                $student_id,
                $first_name,
                $last_name,
                $dob,
                $gender,
                $year,
                $address,
                $contact_no
            ]);
            
            $new_student_id = $pdo->lastInsertId();
            
            // Insert course and subject assignments
            $stmt_course_subject = $pdo->prepare("
                INSERT INTO student_course_subjects 
                (student_id, course_id, subject_id) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($courses as $index => $course_id) {
                if (isset($subjects[$index]) && is_array($subjects[$index])) {
                    foreach ($subjects[$index] as $subject_id) {
                        $stmt_course_subject->execute([
                            $new_student_id,
                            $course_id,
                            $subject_id
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            set_message('success', 'Student added successfully with course and subject assignments.');
            header('Location: list.php');
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_message('error', 'Database error: ' . $e->getMessage());
        }
    }
}
?>
