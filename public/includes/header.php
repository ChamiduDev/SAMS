<?php
// public/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the authentication check to secure the page
if (file_exists(__DIR__ . '/auth_check.php')) {
    require_once __DIR__ . '/auth_check.php';
}

// We need config for any constants or DB functions if needed in the header itself
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/utils.php';

// Get the current script name to highlight the active menu item in the sidebar
$current_page = basename($_SERVER['PHP_SELF']);

// Output buffering to prevent headers already sent errors
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SAMS Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/assets/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/assets/css/notifications.css">
    <!-- Bootstrap JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Sidebar JS -->
    <script defer src="<?php echo BASE_URL; ?>public/assets/js/sidebar.js"></script>
    <!-- Notifications JS -->
    <script defer src="<?php echo BASE_URL; ?>public/assets/js/notifications.js"></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'list.php' && dirname($_SERVER['PHP_SELF']) === '/sams backup/SAMS/public/students'): ?>
    <script defer src="<?php echo BASE_URL; ?>public/students/scripts.js"></script>
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) === 'add.php' && dirname($_SERVER['PHP_SELF']) === '/sams backup/SAMS/public/students'): ?>
    <script defer src="<?php echo BASE_URL; ?>public/assets/js/subject-selection.js"></script>
    <?php endif; ?>
</head>
<body>

<?php require __DIR__ . '/sidebar.php'; ?>

<div class="d-flex" id="wrapper">
    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
            <div class="container-fluid">
                <!-- Sidebar Toggle -->
                <button class="btn btn-primary" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                        <!-- Notifications -->
                        <li class="nav-item me-3">
                            <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>public/notifications/list.php">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notifications-count" style="font-size: 0.5rem;">
                                    0
                                </span>
                            </a>
                        </li>
                        <!-- User Dropdown (Optional) -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>public/profile/">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>public/settings/">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>public/logout.php?csrf_token=<?php echo generate_csrf_token(); ?>">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container-fluid">
            <!-- Main content will be inserted here -->
