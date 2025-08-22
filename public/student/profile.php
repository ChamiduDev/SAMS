<?php
require_once '../includes/student/header.php';
require_once '../includes/student/sidebar.php';

$success_message = '';
$error_message = '';

// Get detailed student information
try {
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            u.email,
            u.username,
            u.created_at as account_created
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE u.id = ? AND s.status = 'active'
    ");
    
    // First get the basic student info
    $stmt->execute([$_SESSION['user_id']]);
    $student_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student_details) {
        // Now get additional details separately to avoid query complexity
        try {
            // Get course count
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT course_id) as total_courses 
                FROM student_courses 
                WHERE student_id = ?
            ");
            $stmt->execute([$student_details['id']]);
            $course_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $student_details['total_courses'] = $course_result['total_courses'] ?? 0;

            // Get attendance details
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT date) as total_attendance_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as total_present_days
                FROM attendance 
                WHERE student_id = ?
            ");
            $stmt->execute([$student_details['id']]);
            $attendance_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $student_details['total_attendance_days'] = $attendance_result['total_attendance_days'] ?? 0;
            $student_details['total_present_days'] = $attendance_result['total_present_days'] ?? 0;

            // Get average grade
            $stmt = $pdo->prepare("
                SELECT ROUND(AVG(marks_obtained / total_marks * 100), 1) as average_grade
                FROM exam_grades
                WHERE student_id = ?
            ");
            $stmt->execute([$student_details['id']]);
            $grade_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $student_details['average_grade'] = $grade_result['average_grade'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error fetching additional student details: " . $e->getMessage());
            // Don't fail the whole page if additional details can't be loaded
        }
    }
    $stmt->execute([$_SESSION['user_id']]);
        $student_details = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($student_details) {
            $attendance_percentage = $student_details['total_attendance_days'] > 0
                ? round(($student_details['total_present_days'] / $student_details['total_attendance_days']) * 100, 1)
                : 0;
        } else {
            $student_details = [];
            $attendance_percentage = 0;
        }

} catch (PDOException $e) {
    error_log("Error fetching student details: " . $e->getMessage());
    $error_message = "Failed to load student details. Please try again later.";
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $upload_dir = '../uploads/profile_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $error_message = "Invalid file type. Please upload a JPG, PNG, or GIF image.";
    } elseif ($file['size'] > $max_size) {
        $error_message = "File is too large. Maximum size is 5MB.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Upload failed. Please try again.";
    } else {
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $student['id'] . '_' . time() . '.' . $file_extension;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            try {
                $stmt = $pdo->prepare("UPDATE students SET profile_image = ? WHERE id = ?");
                $stmt->execute([$new_filename, $student['id']]);
                $success_message = "Profile image updated successfully.";
                $student_details['profile_image'] = $new_filename;
            } catch (PDOException $e) {
                error_log("Error updating profile image: " . $e->getMessage());
                $error_message = "Failed to update profile image in database.";
                unlink($destination); // Remove uploaded file if database update fails
            }
        } else {
            $error_message = "Failed to save the uploaded file.";
        }
    }
}
?>

<div id="page-content-wrapper">
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h4 class="mb-3">My Profile</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../student_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Overview -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="position-relative mb-4 mx-auto" style="width: 150px;">
                            <?php if (!empty($student_details['profile_image'])): ?>
                                <img src="<?php echo BASE_URL; ?>public/uploads/profile_images/<?php echo htmlspecialchars($student_details['profile_image']); ?>"
                                     class="profile-image rounded-circle mb-3"
                                     alt="Profile Image">
                            <?php else: ?>
                                <div class="profile-image-placeholder rounded-circle mb-3 mx-auto">
                                    <i class="fas fa-user-graduate fa-4x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                            <button type="button" 
                                    class="btn btn-sm btn-light rounded-circle position-absolute bottom-0 end-0"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#uploadPhotoModal">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        
                        <h5 class="mb-1"><?php echo htmlspecialchars(($student_details['first_name'] ?? '') . ' ' . ($student_details['last_name'] ?? '')); ?></h5>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($student_details['student_id'] ?? ''); ?></p>
                        
                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span class="badge bg-primary px-3 py-2">
                                <i class="fas fa-graduation-cap me-1"></i>
                                Year <?php echo htmlspecialchars($student_details['year'] ?? ''); ?>
                            </span>
                            <span class="badge bg-info px-3 py-2">
                                <i class="fas fa-book me-1"></i>
                                <?php echo $student_details['total_courses'] ?? 0; ?> Courses
                            </span>
                        </div>
                        
                        <hr>
                        
                        <div class="text-start">
                            <p class="mb-2">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <?php echo htmlspecialchars($student_details['email'] ?? 'Not provided'); ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <?php echo htmlspecialchars($student_details['phone'] ?? 'Not provided'); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <?php echo htmlspecialchars($student_details['address'] ?? 'Not provided'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="col-md-8">
                <!-- Performance Overview -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>Academic Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center">
                                    <div class="display-6 mb-2"><?php echo isset($attendance_percentage) ? $attendance_percentage : 0; ?>%</div>
                                    <p class="text-muted mb-0">Attendance Rate</p>
                                    <small class="text-muted">
                                        <?php echo $student_details['total_present_days'] ?? 0; ?>/<?php echo $student_details['total_attendance_days'] ?? 0; ?> Days
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center">
                                    <div class="display-6 mb-2">
                                        <?php echo $student_details['average_grade'] ?? 'N/A'; ?>
                                    </div>
                                    <p class="text-muted mb-0">Average Grade</p>
                                    <small class="text-muted">Across all subjects</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center">
                                    <div class="display-6 mb-2">
                                        <?php echo $student_details['total_courses'] ?? 0; ?>
                                    </div>
                                    <p class="text-muted mb-0">Total Courses</p>
                                    <small class="text-muted">Currently enrolled</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>Account Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong>Username:</strong><br>
                                    <?php echo htmlspecialchars($student_details['username'] ?? ''); ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Student ID:</strong><br>
                                    <?php echo htmlspecialchars($student_details['student_id'] ?? ''); ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Account Created:</strong><br>
                                    <?php echo !empty($student_details['account_created']) ? date('F j, Y', strtotime($student_details['account_created'])) : 'N/A'; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong>Department:</strong><br>
                                    <?php echo htmlspecialchars($student_details['department'] ?? 'Not assigned'); ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Admission Date:</strong><br>
                                    <?php echo !empty($student_details['admission_date']) ? date('F j, Y', strtotime($student_details['admission_date'])) : 'N/A'; ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Status:</strong><br>
                                    <span class="badge bg-<?php echo ($student_details['status'] ?? '') === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo !empty($student_details['status']) ? ucfirst($student_details['status']) : 'N/A'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Photo Modal -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Profile Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="uploadPhotoForm">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Choose Photo</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                        <div class="form-text">
                            Maximum file size: 5MB<br>
                            Allowed formats: JPG, PNG, GIF
                        </div>
                    </div>
                    <div class="mb-3">
                        <div id="imagePreview" class="text-center" style="display: none;">
                            <img src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Photo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.profile-image {
    width: 150px;
    height: 150px;
    object-fit: cover;
}

.profile-image-placeholder {
    width: 150px;
    height: 150px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card {
    border: none;
    border-radius: 10px;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.display-6 {
    font-size: 2rem;
    font-weight: 600;
}

.badge {
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview
    const input = document.getElementById('profile_image');
    const preview = document.getElementById('imagePreview');
    const previewImg = preview.querySelector('img');

    input.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });

    // Form validation
    const form = document.getElementById('uploadPhotoForm');
    form.addEventListener('submit', function(e) {
        const file = input.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!file) {
            e.preventDefault();
            alert('Please select a file.');
            return;
        }

        if (file.size > maxSize) {
            e.preventDefault();
            alert('File is too large. Maximum size is 5MB.');
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            e.preventDefault();
            alert('Invalid file type. Please upload a JPG, PNG, or GIF image.');
            return;
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
