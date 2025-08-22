<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar-wrapper bg-white shadow-sm">
    <div class="sidebar-header text-center py-4 bg-primary">
        <a href="<?php echo BASE_URL; ?>public/student_dashboard.php" class="text-decoration-none">
            <i class="fas fa-graduation-cap fa-2x text-white mb-2"></i>
            <h5 class="text-white mb-0">Student Portal</h5>
        </a>
    </div>

    <div class="sidebar-menu py-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>public/student_dashboard.php" 
                   class="nav-link <?php echo $current_page === 'student_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i>
                    Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>public/student/courses.php" 
                   class="nav-link <?php echo $current_page === 'courses.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book me-2"></i>
                    My Courses
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>public/student/attendance.php" 
                   class="nav-link <?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check me-2"></i>
                    Attendance Record
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>public/student/exams.php" 
                   class="nav-link <?php echo $current_page === 'exams.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt me-2"></i>
                    Exams & Results
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>public/student/fees.php" 
                   class="nav-link <?php echo $current_page === 'fees.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill me-2"></i>
                    Fee Details
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>public/student/notifications.php" 
                   class="nav-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell me-2"></i>
                    Notifications
                    <?php 
                    // Get unread notifications count
                    $unread_count = get_unread_count($pdo, $_SESSION['user_id'], $_SESSION['role_name']);
                    if ($unread_count > 0): 
                    ?>
                        <span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>public/student/profile.php" 
                   class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user me-2"></i>
                    My Profile
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer py-3 px-4 border-top">
        <div class="d-flex align-items-center">
            <?php
            // Get student basic info
            $stmt = $pdo->prepare("SELECT first_name, last_name, profile_image FROM students WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $student = $stmt->fetch();
            ?>
            <div class="flex-shrink-0">
                <?php if (!empty($student['profile_image'])): ?>
                    <img src="<?php echo BASE_URL . 'uploads/profiles/' . $student['profile_image']; ?>" 
                         alt="Profile" class="rounded-circle" width="40" height="40">
                <?php else: ?>
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                         style="width: 40px; height: 40px;">
                        <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-grow-1 ms-3">
                <p class="mb-0 text-dark fw-semibold">
                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                </p>
                <small class="text-muted">Student</small>
            </div>
            <div class="ms-auto">
                <a href="<?php echo BASE_URL; ?>public/logout.php" class="btn btn-link text-danger p-0" 
                   title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar-wrapper {
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    transition: all 0.3s ease;
}

.sidebar-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-menu .nav-link {
    color: #6c757d;
    padding: 0.8rem 1.5rem;
    transition: all 0.3s ease;
}

.sidebar-menu .nav-link:hover,
.sidebar-menu .nav-link.active {
    color: var(--bs-primary);
    background-color: rgba(13, 110, 253, 0.1);
}

.sidebar-menu .nav-link i {
    width: 20px;
    text-align: center;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    background: white;
}

/* Adjust main content to accommodate sidebar */
#page-content-wrapper {
    margin-left: 250px;
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .sidebar-wrapper {
        margin-left: -250px;
    }
    
    .sidebar-wrapper.active {
        margin-left: 0;
    }
    
    #page-content-wrapper {
        margin-left: 0;
    }
}
</style>
