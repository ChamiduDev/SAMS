<?php
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Include header
include_once '../includes/header.php';

$pdo = get_pdo_connection();

$school_info = [];
$errors = [];

// Fetch current school information
try {
    $stmt = $pdo->query("SELECT * FROM school_settings LIMIT 1");
    $school_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching school information: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: school_info.php');
        exit();
    }

    $school_name = trim($_POST['school_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $principal_name = trim($_POST['principal_name']);
    $school_code = trim($_POST['school_code']);
    $academic_year = trim($_POST['academic_year']);

    // Validation
    if (empty($school_name)) { $errors[] = 'School name is required.'; }
    if (empty($address)) { $errors[] = 'Address is required.'; }
    if (empty($phone)) { $errors[] = 'Phone number is required.'; }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email format.'; }
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) { $errors[] = 'Invalid website URL.'; }

    if (empty($errors)) {
        try {
            if ($school_info) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE school_settings SET 
                    school_name = ?, address = ?, phone = ?, email = ?, website = ?,
                    principal_name = ?, school_code = ?, academic_year = ?");
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO school_settings 
                    (school_name, address, phone, email, website, principal_name, school_code, academic_year)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            }
            
            $stmt->execute([
                $school_name, $address, $phone, $email, $website,
                $principal_name, $school_code, $academic_year
            ]);

            set_message('success', 'School information updated successfully.');
            header('Location: school_info.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            set_message('error', $error);
        }
    }
}

$csrf_token = generate_csrf_token();
$message = get_message();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-school text-primary me-2"></i>School Settings
        </h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'danger'; ?> d-flex align-items-center">
            <i class="fas fa-<?php echo strpos($message, 'success') !== false ? 'check' : 'exclamation'; ?>-circle me-2"></i>
            <?php display_message($message); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-cog text-primary me-2"></i>School Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="school_info.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="row g-4">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="school_name" class="form-label text-muted fw-bold">
                                        <i class="fas fa-building me-2"></i>School Name
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="school_name" 
                                           name="school_name" value="<?php echo htmlspecialchars($school_info['school_name'] ?? ''); ?>" 
                                           required>
                                    <div class="form-text">Official name of the institution</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="school_code" class="form-label text-muted fw-bold">
                                        <i class="fas fa-hashtag me-2"></i>School Code
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="school_code" 
                                           name="school_code" value="<?php echo htmlspecialchars($school_info['school_code'] ?? ''); ?>">
                                    <div class="form-text">Unique identifier for the school</div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address" class="form-label text-muted fw-bold">
                                        <i class="fas fa-map-marker-alt me-2"></i>Address
                                    </label>
                                    <textarea class="form-control" id="address" name="address" 
                                              rows="3" required><?php echo htmlspecialchars($school_info['address'] ?? ''); ?></textarea>
                                    <div class="form-text">Complete postal address</div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label text-muted fw-bold">
                                        <i class="fas fa-phone me-2"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control form-control-lg" id="phone" 
                                           name="phone" value="<?php echo htmlspecialchars($school_info['phone'] ?? ''); ?>" 
                                           required>
                                    <div class="form-text">Primary contact number</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label text-muted fw-bold">
                                        <i class="fas fa-envelope me-2"></i>Email Address
                                    </label>
                                    <input type="email" class="form-control form-control-lg" id="email" 
                                           name="email" value="<?php echo htmlspecialchars($school_info['email'] ?? ''); ?>">
                                    <div class="form-text">Official email address</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="website" class="form-label text-muted fw-bold">
                                        <i class="fas fa-globe me-2"></i>Website
                                    </label>
                                    <input type="url" class="form-control form-control-lg" id="website" 
                                           name="website" value="<?php echo htmlspecialchars($school_info['website'] ?? ''); ?>">
                                    <div class="form-text">School website URL</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="principal_name" class="form-label text-muted fw-bold">
                                        <i class="fas fa-user-tie me-2"></i>Principal's Name
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="principal_name" 
                                           name="principal_name" value="<?php echo htmlspecialchars($school_info['principal_name'] ?? ''); ?>">
                                    <div class="form-text">Name of the school principal</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year" class="form-label text-muted fw-bold">
                                        <i class="fas fa-calendar-alt me-2"></i>Current Academic Year
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="academic_year" 
                                           name="academic_year" value="<?php echo htmlspecialchars($school_info['academic_year'] ?? ''); ?>" 
                                           placeholder="e.g., 2025-2026">
                                    <div class="form-text">Current academic year period</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
