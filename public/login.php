<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/utils.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Set cache control headers to prevent caching of login page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$pdo = get_pdo_connection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.id, u.password, u.role_id, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();

        // Set cache control headers for dashboard redirect
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Redirect based on role
        if ($user['role_name'] === 'student') {
            header('Location: student_dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();

    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAMS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #2d5a2d;
            --accent-green: #4a7c4a;
            --light-green: #e8f5e8;
            --dark-green: #1a3d1a;
            --professional-blue: #2c3e50;
            --light-gray: #f8f9fa;
            --medium-gray: #6c757d;
            --border-color: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a2332 0%, #2c3e50 25%, var(--dark-green) 75%, #1a3d1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(74, 124, 74, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(45, 90, 45, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(44, 62, 80, 0.2) 0%, transparent 50%);
            z-index: -1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        .login-left {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--accent-green) 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.03) 10px,
                rgba(255, 255, 255, 0.03) 20px
            );
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
        }

        .logo i {
            font-size: 32px;
            color: white;
        }

        .login-left h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .login-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }

        .login-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h3 {
            color: var(--primary-green);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--medium-gray);
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--professional-blue);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-gray);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-green);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 0 3px rgba(74, 124, 74, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--medium-gray);
            font-size: 1.1rem;
            z-index: 2;
        }

        .form-group.has-icon .form-control {
            padding-left: 50px;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--accent-green) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(45, 90, 45, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-alert {
            background: linear-gradient(135deg, #fee 0%, #fcc 100%);
            border: 1px solid #f5c6c6;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .error-alert i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: var(--medium-gray);
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
            z-index: 1;
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-green);
        }

        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            position: relative;
            z-index: 2;
        }

        .feature {
            text-align: center;
            opacity: 0.9;
        }

        .feature i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.8);
        }

        .feature h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .feature p {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 450px;
                min-height: auto;
            }

            .login-left {
                display: none; /* Hide the left branding section on smaller screens */
            }

            .login-right {
                padding: 50px 40px;
            }

            .login-header h3 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .login-right {
                padding: 40px 25px;
            }
            .form-control {
                padding: 14px 20px 14px 45px;
            }
            .btn-login {
                padding: 14px;
            }
        }

        /* Loading Animation */
        .btn-login.loading {
            pointer-events: none;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2>SAMS</h2>
            <p>Smart Academic Management System</p>
            
            
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <h3>Welcome Back!</h3>
                <p>Please enter your details to sign in.</p>
            </div>

            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" id="loginForm">
                <div class="form-group has-icon">
                    <label for="username">Username</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" class="form-control" id="username" name="username" required 
                           placeholder="Username" autocomplete="username">
                </div>

                <div class="form-group has-icon">
                    <label for="password">Password</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" required 
                           placeholder="Password" autocomplete="current-password">
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span id="loginText">Sign In</span>
                </button>
            </form>

            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add loading animation on form submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const text = document.getElementById('loginText');
            
            btn.classList.add('loading');
            text.textContent = 'Signing In...';
            
            // Prevent multiple submissions
            btn.disabled = true;
        });

        // Add focus effects to form inputs
        document.querySelectorAll('.form-group').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        
    </script>
</body>
</html>