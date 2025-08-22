<?php
// Ensure auth_check.php is included to have hasPermission() function available
require_once __DIR__ . '/auth_check.php';

// Get current page and directory
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel" data-bs-backdrop="true">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center">
            <i class="fas fa-graduation-cap fs-3 me-2 text-light"></i>
            <h5 class="offcanvas-title mb-0 text-light" id="sidebarLabel">SAMS</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="menu-category">Main Menu</div>
        <nav class="nav flex-column">
            <?php if (hasPermission('dashboard_view')): ?>
            <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('students_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'student') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/students/list.php">
                <i class="fas fa-user-graduate"></i> <span>Students</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('courses_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'course') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/courses/list.php">
                <i class="fas fa-book"></i> <span>Courses</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('subjects_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'subject') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/subjects/list.php">
                <i class="fas fa-chalkboard"></i> <span>Subjects</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('attendance_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'attendance') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/attendance/list.php">
                <i class="fas fa-clipboard-check"></i> <span>Attendance</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('exams_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'exam') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/exams/list.php">
                <i class="fas fa-file-alt"></i> <span>Exams</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('grades_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'grade') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/grades/list.php">
                <i class="fas fa-award"></i> <span>Grades</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('fees_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'fee') !== false || strpos($current_page, 'payment') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/fees/index.php">
                <i class="fas fa-money-bill-wave"></i> <span>Fee Management</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('notifications_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'notification') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/notifications/list.php">
                <i class="fas fa-bell"></i> <span>Notifications</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('users_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'user') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/users/list.php">
                <i class="fas fa-users"></i> <span>Users</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('roles_list')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'role') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/roles/list.php">
                <i class="fas fa-user-tag"></i> <span>Roles</span>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('settings_school_info') || hasPermission('settings_grading_scale')): ?>
            <a class="nav-link <?php echo (strpos($current_page, 'setting') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>public/settings/index.php">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
            <?php endif; ?>
            
            <div class="menu-category">Account</div>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>public/logout.php?csrf_token=<?php echo generate_csrf_token(); ?>">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </nav>
    </div>
</div>