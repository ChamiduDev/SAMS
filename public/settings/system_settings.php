<?php
require_once '../../config/config.php';
require_once '../../config/utils.php';

// Include header
include_once '../includes/header.php';

$pdo = get_pdo_connection();

$system_settings = [];
$errors = [];

// Fetch current system settings
try {
    $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $system_settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error fetching system settings: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: system_settings.php');
        exit();
    }

    $attendance_threshold = filter_input(INPUT_POST, 'attendance_threshold', FILTER_VALIDATE_FLOAT);
    $passing_grade = filter_input(INPUT_POST, 'passing_grade', FILTER_VALIDATE_FLOAT);
    $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
    $enable_parent_portal = isset($_POST['enable_parent_portal']) ? 1 : 0;
    $session_timeout = filter_input(INPUT_POST, 'session_timeout', FILTER_VALIDATE_INT);
    $default_timezone = $_POST['default_timezone'];
    $date_format = $_POST['date_format'];

    // Validation
    if ($attendance_threshold === false || $attendance_threshold < 0 || $attendance_threshold > 100) {
        $errors[] = 'Attendance threshold must be between 0 and 100.';
    }
    if ($passing_grade === false || $passing_grade < 0 || $passing_grade > 100) {
        $errors[] = 'Passing grade must be between 0 and 100.';
    }
    if ($session_timeout === false || $session_timeout < 5 || $session_timeout > 1440) {
        $errors[] = 'Session timeout must be between 5 and 1440 minutes.';
    }
    if (!in_array($default_timezone, DateTimeZone::listIdentifiers())) {
        $errors[] = 'Invalid timezone selected.';
    }

    if (empty($errors)) {
        try {
            if ($system_settings) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE system_settings SET 
                    attendance_threshold = ?, passing_grade = ?, enable_notifications = ?,
                    enable_parent_portal = ?, session_timeout = ?, default_timezone = ?,
                    date_format = ?");
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO system_settings 
                    (attendance_threshold, passing_grade, enable_notifications,
                    enable_parent_portal, session_timeout, default_timezone, date_format)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            }
            
            $stmt->execute([
                $attendance_threshold, $passing_grade, $enable_notifications,
                $enable_parent_portal, $session_timeout, $default_timezone, $date_format
            ]);

            set_message('success', 'System settings updated successfully.');
            header('Location: system_settings.php');
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

// Get list of timezones
$timezones = DateTimeZone::listIdentifiers();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-cogs text-primary me-2"></i>System Settings
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
                        <i class="fas fa-sliders-h text-primary me-2"></i>System Configuration
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="system_settings.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="row g-4">
                            <!-- Academic Settings -->
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Academic Settings</h6>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="attendance_threshold" class="form-label text-muted fw-bold">
                                                <i class="fas fa-clock me-2"></i>Attendance Threshold (%)
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control form-control-lg" id="attendance_threshold" 
                                                       name="attendance_threshold" value="<?php echo htmlspecialchars($system_settings['attendance_threshold'] ?? '75'); ?>" 
                                                       step="0.01" min="0" max="100">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Minimum attendance required</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="passing_grade" class="form-label text-muted fw-bold">
                                                <i class="fas fa-check-circle me-2"></i>Passing Grade (%)
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control form-control-lg" id="passing_grade" 
                                                       name="passing_grade" value="<?php echo htmlspecialchars($system_settings['passing_grade'] ?? '50'); ?>" 
                                                       step="0.01" min="0" max="100">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Minimum grade required to pass</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- System Features -->
                            <div class="col-12">
                                <h6 class="text-primary mb-3">System Features</h6>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_notifications" 
                                                   name="enable_notifications" <?php echo ($system_settings['enable_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_notifications">
                                                <i class="fas fa-bell me-2"></i>Enable Notifications
                                            </label>
                                            <div class="form-text">Allow system to send notifications</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_parent_portal" 
                                                   name="enable_parent_portal" <?php echo ($system_settings['enable_parent_portal'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_parent_portal">
                                                <i class="fas fa-users me-2"></i>Enable Parent Portal
                                            </label>
                                            <div class="form-text">Allow parent access to the system</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- System Settings -->
                            <div class="col-12">
                                <h6 class="text-primary mb-3">System Settings</h6>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="session_timeout" class="form-label text-muted fw-bold">
                                                <i class="fas fa-hourglass-half me-2"></i>Session Timeout
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control form-control-lg" id="session_timeout" 
                                                       name="session_timeout" value="<?php echo htmlspecialchars($system_settings['session_timeout'] ?? '30'); ?>" 
                                                       min="5" max="1440">
                                                <span class="input-group-text">minutes</span>
                                            </div>
                                            <div class="form-text">Auto logout after inactivity (5-1440 minutes)</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="default_timezone" class="form-label text-muted fw-bold">
                                                <i class="fas fa-globe me-2"></i>Default Timezone
                                            </label>
                                            <select class="form-select form-select-lg" id="default_timezone" name="default_timezone">
                                                <?php foreach ($timezones as $timezone): ?>
                                                    <option value="<?php echo htmlspecialchars($timezone); ?>" 
                                                            <?php echo ($system_settings['default_timezone'] ?? 'UTC') === $timezone ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($timezone); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">System default timezone</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date_format" class="form-label text-muted fw-bold">
                                                <i class="fas fa-calendar me-2"></i>Date Format
                                            </label>
                                            <select class="form-select form-select-lg" id="date_format" name="date_format">
                                                <option value="Y-m-d" <?php echo ($system_settings['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : ''; ?>>
                                                    YYYY-MM-DD (<?php echo date('Y-m-d'); ?>)
                                                </option>
                                                <option value="d/m/Y" <?php echo ($system_settings['date_format'] ?? 'Y-m-d') === 'd/m/Y' ? 'selected' : ''; ?>>
                                                    DD/MM/YYYY (<?php echo date('d/m/Y'); ?>)
                                                </option>
                                                <option value="m/d/Y" <?php echo ($system_settings['date_format'] ?? 'Y-m-d') === 'm/d/Y' ? 'selected' : ''; ?>>
                                                    MM/DD/YYYY (<?php echo date('m/d/Y'); ?>)
                                                </option>
                                                <option value="d-M-Y" <?php echo ($system_settings['date_format'] ?? 'Y-m-d') === 'd-M-Y' ? 'selected' : ''; ?>>
                                                    DD-Mon-YYYY (<?php echo date('d-M-Y'); ?>)
                                                </option>
                                            </select>
                                            <div class="form-text">System-wide date display format</div>
                                        </div>
                                    </div>
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
