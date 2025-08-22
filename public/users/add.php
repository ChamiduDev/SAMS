<?php
// Start output buffering
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
error_log("Starting add.php script");

// Include necessary files
require_once '../../config/config.php';
require_once '../../config/utils.php';

try {
    $pdo = get_pdo_connection();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables
$errors = [];
$username = '';
$email = '';
$roleName = '';

// Debug function
function debug_to_file($message) {
    $debug_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debug_file, "[$timestamp] $message\n", FILE_APPEND);
}

debug_to_file("Page loaded");

function logToFile($message, $data = null) {
    $logFile = __DIR__ . '/user_creation.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= "\n" . print_r($data, true);
    }
    $logMessage .= "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    logToFile("=== New User Creation Started ===");
    logToFile("POST data received:", $_POST);
    logToFile("Session state:", $_SESSION);

    // Verify database connection first
    try {
        $pdo->query('SELECT 1');
        logToFile("Database connection verified");
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        set_message('error', 'Database connection failed. Please check your configuration.');
        header('Location: add.php');
        exit();
    }

    // CSRF Protection
    if (!isset($_POST['csrf_token'])) {
        error_log("No CSRF token found in POST data");
        set_message('error', 'CSRF token is missing.');
        header('Location: add.php');
        exit();
    }

    if (!verify_csrf_token($_POST['csrf_token'])) {
        error_log("CSRF token verification failed");
        set_message('error', 'Invalid CSRF token.');
        header('Location: add.php');
        exit();
    }
    error_log("CSRF token verified successfully");

    debug_to_file("Processing form data");
    
    // Dump raw POST data for debugging
    debug_to_file("RAW POST data: " . print_r($_POST, true));
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $roleName = isset($_POST['role']) ? $_POST['role'] : '';

    debug_to_file("Processed form data:");
    debug_to_file("Username: $username");
    debug_to_file("Email: $email");
    debug_to_file("Role Name: $roleName");
    debug_to_file("Password length: " . strlen($password));
    debug_to_file("Confirm password length: " . strlen($confirm_password));

    // Input Validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($roleName)) {
        $errors[] = 'Role is required.';
    }

    $roleId = null;
    
    // Validate role and get role ID
    if (empty($errors)) {
        debug_to_file("Looking up role ID for role: $roleName");
        
        // First check if roles table exists
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
            if (!$stmt->fetch()) {
                // Create roles table if it doesn't exist
                debug_to_file("Creating roles table...");
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS roles (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(50) NOT NULL UNIQUE
                    )
                ");
                
                // Insert default roles
                $pdo->exec("INSERT INTO roles (id, name) VALUES 
                    (1, 'admin'),
                    (2, 'teacher'),
                    (3, 'student')
                ");
                debug_to_file("Roles table created and populated");
            }

            // Now look up the role ID
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
            $stmt->execute([$roleName]);
            debug_to_file("Role query executed");
            $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($roleData) {
                $roleId = $roleData['id'];
                debug_to_file("Found role ID: $roleId");
            } else {
                debug_to_file("Role '$roleName' not found in database");
                $errors[] = "Invalid role selected: $roleName";
            }
        } catch (PDOException $e) {
            debug_to_file("Database error checking roles: " . $e->getMessage());
            $errors[] = 'Database error while checking role.';
        }
    }

    // Handle validation errors if any exist
    if (!empty($errors)) {
        debug_to_file("Validation errors found: " . implode(", ", $errors));
        foreach ($errors as $error) {
            set_message('error', $error);
        }
        // Return to the form with errors
        header('Location: add.php');
        exit();
    }
    
    // Start the user creation process
    debug_to_file("\n=== Starting User Creation ===");
    debug_to_file("No validation errors found");
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        debug_to_file("Transaction started");

        // Final duplicate check
        $checkStmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        $existing = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($existing)) {
            debug_to_file("Duplicate check found matches: " . print_r($existing, true));
            $pdo->rollBack();
            foreach ($existing as $existingUser) {
                if ($existingUser['username'] === $username) {
                    $errors[] = 'Username "' . htmlspecialchars($username) . '" is already taken.';
                }
                if ($existingUser['email'] === $email) {
                    $errors[] = 'Email address "' . htmlspecialchars($email) . '" is already registered.';
                }
            }
            foreach ($errors as $error) {
                set_message('error', $error);
            }
            header('Location: add.php');
            exit();
        }
        
        debug_to_file("No duplicates found, creating user");
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        debug_to_file("Password hashed");
        
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if (!$stmt->fetch()) {
            debug_to_file("Creating users table...");
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    role_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (role_id) REFERENCES roles(id)
                )
            ");
            debug_to_file("Users table created");
        }

        $sql = "INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)";
        debug_to_file("Preparing SQL: " . $sql);
        
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            $error = $pdo->errorInfo();
            debug_to_file("Prepare failed: " . print_r($error, true));
            throw new PDOException("Failed to prepare statement: " . implode(", ", $error));
        }

        $params = [
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashed_password,
            ':role_id' => $roleId
        ];
        debug_to_file("Parameters prepared: " . print_r($params, true));
        
        $result = $stmt->execute($params);
        debug_to_file("Execute result: " . ($result ? "SUCCESS" : "FAILED"));
        
        if (!$result) {
            $error = $stmt->errorInfo();
            debug_to_file("Execute error: " . print_r($error, true));
            throw new PDOException("Failed to insert user: " . implode(", ", $error));
        }

        $newUserId = $pdo->lastInsertId();
        debug_to_file("New user ID: " . $newUserId);
        
        // Verify the new user exists
        $verifyStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $verifyStmt->execute([$newUserId]);
        $newUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$newUser) {
            debug_to_file("ERROR: Could not verify new user in database");
            throw new PDOException("Failed to verify new user");
        }
        
        debug_to_file("User verified in database: " . print_r($newUser, true));
        
        // Success! Commit and redirect
        debug_to_file("Committing transaction");
        $pdo->commit();
        debug_to_file("Transaction committed successfully");
        
        debug_to_file("Setting success message");
        set_message('success', 'User "' . htmlspecialchars($username) . '" was created successfully.');
        
        debug_to_file("=== User Creation Complete ===");
        
        // End and clean all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Ensure fresh page load
        session_write_close(); // Ensure session data is saved
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Location: list.php?refresh=" . time());
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database error in add user: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Set a more user-friendly error message
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'Duplicate entry') !== false) {
            if (strpos($errorMsg, 'username') !== false) {
                $errorMsg = 'Username already exists.';
            } elseif (strpos($errorMsg, 'email') !== false) {
                $errorMsg = 'Email address already exists.';
            } else {
                $errorMsg = 'A user with those details already exists.';
            }
        }
        
        set_message('error', $errorMsg);
        error_log("Error message set: " . $errorMsg);
        
        // Clear any output buffers before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Location: add.php');
        exit();
    }
}

$csrf_token = generate_csrf_token();
$message = get_message();

// Fetch available roles for the dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM roles ORDER BY name");
    $available_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Error fetching roles: ' . $e->getMessage());
    $available_roles = [];
}

// Include the header
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-user-plus text-primary me-2"></i>Add New User
        </h2>
        <a href="list.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <?php 
    $messages = get_message();
    if (!empty($messages)): 
        foreach ($messages as $msg):
            $type = $msg['type'];
            $content = $msg['content'];
    ?>
        <div class="alert alert-<?php echo $type === 'success' ? 'success' : 'danger'; ?> d-flex align-items-center">
            <i class="fas fa-<?php echo $type === 'success' ? 'check' : 'exclamation'; ?>-circle me-2"></i>
            <?php echo htmlspecialchars($content); ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php 
        endforeach;
    endif; 
    ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <?php 
            debug_to_file("Rendering form");
            
            // Debug information
            echo "<!-- Debug Info:
            POST submitted: " . ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'Yes' : 'No') . "
            Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "
            Session ID: " . session_id() . "
            -->";
            ?>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="addUserForm" onsubmit="console.log('Form submitted')">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <?php debug_to_file("CSRF Token: " . ($csrf_token ?? 'not set')); ?>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username" class="form-label text-muted fw-bold">
                                <i class="fas fa-user me-2"></i>Username
                            </label>
                            <input type="text" class="form-control form-control-lg" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username); ?>" required
                                   placeholder="Enter username">
                            <div class="form-text">Choose a unique username for the account</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email" class="form-label text-muted fw-bold">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required
                                   placeholder="Enter email address">
                            <div class="form-text">Active email address for account recovery</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password" class="form-label text-muted fw-bold">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required
                                   placeholder="Enter password">
                            <div class="form-text">Minimum 6 characters long</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="confirm_password" class="form-label text-muted fw-bold">
                                <i class="fas fa-lock me-2"></i>Confirm Password
                            </label>
                            <input type="password" class="form-control form-control-lg" id="confirm_password" 
                                   name="confirm_password" required placeholder="Confirm password">
                            <div class="form-text">Re-enter the password to confirm</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label for="role" class="form-label text-muted fw-bold">
                                <i class="fas fa-user-tag me-2"></i>User Role
                            </label>
                            <select class="form-select form-select-lg" id="role" name="role" required>
                                <option value="">Select a role</option>
                                <?php foreach ($available_roles as $available_role): ?>
                                    <option value="<?php echo htmlspecialchars($available_role['name']); ?>" 
                                            <?php echo $roleName === $available_role['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($available_role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select the user's role to determine their permissions</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" onclick="console.log('Submit button clicked')">
                            <i class="fas fa-user-plus me-2"></i>Create User
                        </button>
                        <a href="list.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    console.log('Form submission started');
    
    // Get form values
    var username = document.getElementById('username').value;
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    var role = document.getElementById('role').value;
    
    console.log('Form data:', {
        username: username,
        email: email,
        passwordLength: password.length,
        confirmPasswordLength: confirmPassword.length,
        role: role
    });

    // Basic validation
    var errors = [];
    if (!username) errors.push('Username is required');
    if (!email) errors.push('Email is required');
    if (!password) errors.push('Password is required');
    if (password !== confirmPassword) errors.push('Passwords do not match');
    if (!role) errors.push('Role is required');

    if (errors.length > 0) {
        e.preventDefault();
        console.log('Validation errors:', errors);
        alert('Please fix the following errors:\n' + errors.join('\n'));
        return false;
    }

    console.log('Form validation passed, submitting...');
    // Let the form submit
    return true;
});
</script>
