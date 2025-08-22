<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div id="sidebar-wrapper">
    <!-- Student Profile -->
    <div class="student-profile text-center text-white">
        <div class="mb-3">
            <div class="profile-image-placeholder">
                <i class="fas fa-user-graduate fa-3x"></i>
            </div>
        </div>
        <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></h5>
        <span class="badge bg-light text-dark mb-2 px-3 py-2">
            <i class="fas fa-id-card me-1"></i>
            <?php echo htmlspecialchars($student['student_id']); ?>
        </span>
        <span class="badge bg-info px-3 py-2">
            <i class="fas fa-graduation-cap me-1"></i>
            Year <?php echo htmlspecialchars($student['year']); ?>
        </span>
    </div>
    
    <!-- Navigation -->
    <div class="px-3 mt-4">
        <h6 class="sidebar-heading text-uppercase text-white-50 px-3 mb-3">
            <i class="fas fa-compass me-2"></i>Navigation
        </h6>
    </div>
    
    <div class="list-group list-group-flush px-3">
        <a href="<?php echo BASE_URL; ?>public/student_dashboard.php" 
           class="list-group-item list-group-item-action rounded mb-2 <?php echo $current_page === 'student_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard
        </a>
        
        <a href="<?php echo BASE_URL; ?>public/student/courses.php" 
           class="list-group-item list-group-item-action rounded mb-2 <?php echo $current_page === 'courses.php' ? 'active' : ''; ?>">
            <i class="fas fa-book fa-fw me-2"></i> My Courses
        </a>
        
        <a href="<?php echo BASE_URL; ?>public/student/attendance.php" 
           class="list-group-item list-group-item-action rounded mb-2 <?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check fa-fw me-2"></i> Attendance
        </a>
        
        <a href="<?php echo BASE_URL; ?>public/student/exams.php" 
           class="list-group-item list-group-item-action rounded mb-2 <?php echo $current_page === 'exams.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt fa-fw me-2"></i> Exams
        </a>
        
        <a href="<?php echo BASE_URL; ?>public/student/results.php" 
           class="list-group-item list-group-item-action rounded mb-2 <?php echo $current_page === 'results.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar fa-fw me-2"></i> Results
        </a>
        
        <a href="<?php echo BASE_URL; ?>public/student/fees.php" 
           class="list-group-item list-group-item-action rounded mb-2 <?php echo $current_page === 'fees.php' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-alt fa-fw me-2"></i> Fees & Payments
        </a>
        
        <a href="<?php echo BASE_URL; ?>public/student/notifications.php" 
           class="list-group-item list-group-item-action rounded mb-2 <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
            <i class="fas fa-bell fa-fw me-2"></i> Notifications
            <?php
            if ($unread_count > 0):
            ?>
            <span class="badge bg-danger float-end"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="px-3 mt-4">
        <h6 class="sidebar-heading text-uppercase text-white-50 px-3 mb-3">
            <i class="fas fa-user-cog me-2"></i>Settings
        </h6>
    </div>

    <div class="list-group list-group-flush px-3">
        <a href="<?php echo BASE_URL; ?>public/student/settings.php" 
           class="list-group-item list-group-item-action rounded mb-2 <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog fa-fw me-2"></i> Account Settings
        </a>
        
        <a href="<?php echo BASE_URL; ?>public/logout.php?csrf_token=<?php echo generate_csrf_token(); ?>" 
           class="list-group-item list-group-item-action rounded mb-2 text-danger">
            <i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout
        </a>
    </div>

    <div class="p-3 mt-4">
        <div class="d-flex align-items-center justify-content-between text-white-50 small">
            <span>© <?php echo date('Y'); ?> SAMS</span>
            <span>v1.0.0</span>
        </div>
    </div>
</div>

<style>
.profile-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.profile-image-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 3px solid rgba(255, 255, 255, 0.2);
}

.sidebar-heading {
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.list-group-item {
    border-radius: 8px !important;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
}

.list-group-item.active {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
}

.list-group-item i {
    width: 20px;
    text-align: center;
}

.badge {
    transition: all 0.3s ease;
}

.badge:hover {
    transform: scale(1.05);
}
</style>
