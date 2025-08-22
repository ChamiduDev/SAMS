<?php
session_start();
require_once dirname(dirname(dirname(__DIR__))) . '/config/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/config/utils.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    header('Location: ' . BASE_URL . 'public/login.php');
    exit();
}

// Get student information
$pdo = get_pdo_connection();
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.student_id,
        s.first_name,
        s.last_name,
        CONCAT(s.first_name, ' ', s.last_name) as full_name,
        s.year,
        s.user_id,
        u.email,
        u.username
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE u.id = ? AND s.status = 'active'
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// If student record not found or inactive, redirect to login
if (!$student) {
    session_destroy();
    header('Location: ' . BASE_URL . 'public/login.php?error=invalid_student');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - SAMS</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Student Dashboard CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/assets/css/student.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        #sidebar-wrapper {
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: fixed;
            top: 0;
            left: -280px;
            z-index: 1050;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all .3s;
            overflow-y: auto;
        }

        #wrapper.toggled #sidebar-wrapper {
            left: 0;
        }

        /* Main Content Styles */
        #page-content-wrapper {
            width: 100%;
            min-height: 100vh;
            padding: 90px 25px 25px;
            transition: all .3s;
        }

        .container-fluid {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Card Spacing */
        .card {
            margin-bottom: 25px;
        }

        .row {
            margin-right: -12px;
            margin-left: -12px;
        }

        .col, [class*="col-"] {
            padding-right: 12px;
            padding-left: 12px;
        }

        /* Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1040;
            opacity: 0;
            transition: all .5s ease-in-out;
        }

        #wrapper.toggled .sidebar-overlay {
            display: block;
            opacity: 1;
        }
        
        .navbar {
            background: #fff !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.75rem 1.5rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 999;
        }
        
        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color) !important;
        }
        
        .sidebar-heading {
            padding: 1.5rem 1rem;
            font-size: 1.2rem;
            color: #fff;
        }
        
        .list-group-item {
            background-color: transparent;
            color: #ecf0f1;
            border: none;
            padding: 0.8rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateX(5px);
        }
        
        .list-group-item.active {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .student-profile {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Navbar and Layout Styles */
        .navbar {
            background: #fff !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 65px;
            padding: 0.5rem 1.5rem;
            z-index: 1030;
        }
        
        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color) !important;
            margin-right: 2rem;
        }

        #menuToggle {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            margin-right: 1rem;
            transition: all 0.3s ease;
        }

        #menuToggle:hover {
            background-color: rgba(0,0,0,0.05);
        }

        @media (max-width: 991.98px) {
            #page-content-wrapper {
                padding: 80px 15px 15px;
            }
        }

        /* Custom animation for notifications */
        @keyframes bell-shake {
            0% { transform: rotate(0); }
            15% { transform: rotate(5deg); }
            30% { transform: rotate(-5deg); }
            45% { transform: rotate(4deg); }
            60% { transform: rotate(-4deg); }
            75% { transform: rotate(2deg); }
            85% { transform: rotate(-2deg); }
            92% { transform: rotate(1deg); }
            100% { transform: rotate(0); }
        }

        .notification-bell:hover i {
            animation: bell-shake 0.7s ease;
        }

        /* Custom styles for stats cards */
        .stats-card {
            border-radius: 15px;
            overflow: hidden;
        }

        .stats-card i {
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .stats-card:hover i {
            opacity: 1;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
<div id="wrapper">
<!-- Sidebar Overlay -->
<div class="sidebar-overlay"></div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top">
    <div class="container-fluid px-4">
        <button class="btn btn-link text-dark" id="menuToggle" type="button">
            <i class="fas fa-bars"></i>
        </button>

        <div class="d-flex align-items-center">
            <!-- Notifications -->
            <div class="nav-item me-3">
                <a class="nav-link notification-bell position-relative" href="<?php echo BASE_URL; ?>public/student/notifications.php">
                    <i class="fas fa-bell"></i>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                    $stmt->execute([$_SESSION['user_id']]);
                    $unread_count = $stmt->fetchColumn();
                    if ($unread_count > 0):
                    ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.5rem;">
                        <?php echo $unread_count; ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
                    <?php echo htmlspecialchars($student['username']); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                   
                    <li>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>public/student/settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>public/logout.php?csrf_token=<?php echo generate_csrf_token(); ?>">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Bootstrap and other scripts at the end -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('wrapper');
    const overlay = document.querySelector('.sidebar-overlay');
    const menuToggle = document.getElementById('menuToggle');

    if (!wrapper || !menuToggle) return;
    
    function toggleSidebar() {
        wrapper.classList.toggle('toggled');
        document.body.style.overflow = wrapper.classList.contains('toggled') ? 'hidden' : '';
    }

    // Menu toggle button click
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
    });

    // Overlay click
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (wrapper.classList.contains('toggled')) {
                toggleSidebar();
            }
        });
    }

    // ESC key press
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && wrapper.classList.contains('toggled')) {
            toggleSidebar();
        }
    });

    // Handle initial state
    if (window.innerWidth >= 992) {
        wrapper.classList.remove('toggled');
    }
});
</script>
