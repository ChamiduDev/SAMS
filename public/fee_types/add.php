<?php
require_once '../includes/header.php';

$pdo = get_pdo_connection();

$errors = [];
$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid CSRF token.');
        header('Location: add.php');
        exit();
    }

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    // Validation
    if (empty($name)) { $errors[] = 'Fee Type Name is required.'; }

    // Check for duplicate name
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_types WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Fee Type Name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO fee_types (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);

            set_message('success', 'Fee Type added successfully.');
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            set_message('error', 'Database error adding fee type: ' . $e->getMessage());
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



    <div class="container-fluid page-container">
        <div class="page-header">
            <div>
                <h2>Add New Fee Type</h2>
                <p class="text-muted">Create a new category for fees</p>
            </div>
            <div>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <?php display_message($message); ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus-circle me-2"></i>
                        Fee Type Details
                    </div>
                    <div class="card-body">
                        <form action="add.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-4">
                                        <label for="name" class="form-label">Fee Type Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($name); ?>" 
                                               placeholder="Enter fee type name"
                                               required>
                                        <div class="form-text">
                                            Choose a unique and descriptive name for this fee type
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter a fee type name
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-4">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="4" placeholder="Enter a detailed description"><?php echo htmlspecialchars($description); ?></textarea>
                                        <div class="form-text">
                                            Provide additional details about this fee type to help users understand its purpose
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Fee Type
                                </button>
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>
                        Guidelines
                    </div>
                    <div class="card-body">
                        <h6 class="card-subtitle mb-3">Tips for creating fee types:</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Use clear and specific names
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Include relevant details in the description
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Avoid duplicate fee types
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Consider the fee structure hierarchy
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
