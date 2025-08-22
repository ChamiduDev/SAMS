<?php
require_once '../includes/header.php';
require_once '../../config/config.php';
require_once '../../config/utils.php';
require_once '../../config/school_settings.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !has_role('admin')) {
    set_message('error', 'You do not have permission to access settings.');
    header('Location: ../dashboard.php');
    exit();
}

$pdo = get_pdo_connection();

try {
    // Fetch current settings
    $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Failed to load settings: ' . $e->getMessage());
    $settings = null;
}

$message = get_message();
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0">System Settings</h4>
                </div>
                <div class="card-body">
                    <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist">
                        <button class="nav-link text-start active mb-2" data-bs-toggle="pill" data-bs-target="#general-settings" type="button">
                            <i class="fas fa-cog me-2"></i>General Settings
                        </button>
                        <button class="nav-link text-start mb-2" data-bs-toggle="pill" data-bs-target="#academic-settings" type="button">
                            <i class="fas fa-graduation-cap me-2"></i>Academic Settings
                        </button>
                        <!--<button class="nav-link text-start mb-2" data-bs-toggle="pill" data-bs-target="#notification-settings" type="button">
                            <i class="fas fa-bell me-2"></i>Notification Settings
                        </button>-->
                        <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#school-info" type="button">
                            <i class="fas fa-school me-2"></i>School Information
                        </button>
                    </div>

                    <?php 
                    if (!empty($message)) {
                        display_message($message);
                    }
                    ?>

                    <div class="tab-content mt-3">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general-settings">
                            <form action="save_settings.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="settings_type" value="general">
                                
                                <div class="mb-3">
                                    <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                           value="<?php echo htmlspecialchars($settings['session_timeout']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="default_timezone" class="form-label">Default Timezone</label>
                                    <select class="form-select" id="default_timezone" name="default_timezone" required>
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers();
                                        foreach ($timezones as $timezone) {
                                            $selected = ($timezone === $settings['default_timezone']) ? 'selected' : '';
                                            echo "<option value=\"" . htmlspecialchars($timezone) . "\" $selected>" . 
                                                 htmlspecialchars($timezone) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="date_format" class="form-label">Date Format</label>
                                    <select class="form-select" id="date_format" name="date_format" required>
                                        <option value="Y-m-d" <?php echo $settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="m/d/Y" <?php echo $settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                        <option value="d/m/Y" <?php echo $settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                        <option value="d.m.Y" <?php echo $settings['date_format'] === 'd.m.Y' ? 'selected' : ''; ?>>DD.MM.YYYY</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save General Settings
                                </button>
                            </form>
                        </div>

                        <!-- Academic Settings -->
                        <div class="tab-pane fade" id="academic-settings">
                            <form action="save_settings.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="settings_type" value="academic">
                                
                                <div class="mb-3">
                                    <label for="attendance_threshold" class="form-label">Attendance Threshold (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                           id="attendance_threshold" name="attendance_threshold" 
                                           value="<?php echo htmlspecialchars($settings['attendance_threshold']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="passing_grade" class="form-label">Passing Grade (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                           id="passing_grade" name="passing_grade" 
                                           value="<?php echo htmlspecialchars($settings['passing_grade']); ?>" required>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Academic Settings
                                </button>
                            </form>
                        </div>

                        <!-- Notification Settings -->
                        <div class="tab-pane fade" id="notification-settings">
                            <form action="save_settings.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="settings_type" value="notifications">
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_notifications" 
                                               name="enable_notifications" value="1" 
                                               <?php echo $settings['enable_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_notifications">
                                            Enable System Notifications
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_parent_portal" 
                                               name="enable_parent_portal" value="1" 
                                               <?php echo $settings['enable_parent_portal'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_parent_portal">
                                            Enable Parent Portal
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Notification Settings
                                </button>
                            </form>
                        </div>

                        <!-- School Information -->
                        <div class="tab-pane fade" id="school-info">
                            <form action="save_settings.php" method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="settings_type" value="school">

                                <?php
                                // Fetch school info
                                try {
                                    $stmt = $pdo->query("SELECT * FROM school_info WHERE id = 1");
                                    $school_info = $stmt->fetch(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $school_info = null;
                                }
                                ?>

                                <div class="text-center mb-4">
                                    <div class="position-relative d-inline-block">
                                        <?php if (!empty($school_info['school_logo'])): ?>
                                            <img src="<?php echo htmlspecialchars($school_info['school_logo']); ?>" 
                                                 alt="School Logo" class="img-fluid mb-3" style="max-height: 150px;">
                                        <?php else: ?>
                                            <img src="../assets/images/school-logo-default.png" 
                                                 alt="Default School Logo" class="img-fluid mb-3" style="max-height: 150px;">
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <label for="school_logo" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-upload me-2"></i>Change Logo
                                            </label>
                                            <input type="file" id="school_logo" name="school_logo" 
                                                   class="form-control d-none" accept="image/*">
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="school_name" class="form-label">School Name</label>
                                            <input type="text" class="form-control" id="school_name" name="school_name"
                                                   value="<?php echo htmlspecialchars($school_info['school_name'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">Please enter the school name.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="school_email" class="form-label">Contact Email</label>
                                            <input type="email" class="form-control" id="school_email" name="school_email"
                                                   value="<?php echo htmlspecialchars($school_info['school_email'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">Please enter a valid email address.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="school_phone" class="form-label">Contact Phone</label>
                                            <input type="tel" class="form-control" id="school_phone" name="school_phone"
                                                   value="<?php echo htmlspecialchars($school_info['school_phone'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">Please enter a contact phone number.</div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="school_address" class="form-label">School Address</label>
                                            <textarea class="form-control" id="school_address" name="school_address" 
                                                      rows="3" required><?php echo htmlspecialchars($school_info['school_address'] ?? ''); ?></textarea>
                                            <div class="invalid-feedback">Please enter the school address.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save School Information
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php require_once '../includes/footer.php'; ?>
