<?php
$pageTitle = 'Join Us - Garden Hub';
require_once '../core/Security.php';
require_once '../classes/User.php';

$error = null;

// If already logged in, redirect to dashboard
if(Security::isLoggedIn()) {
    header("Location: /garden-hub/public/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $name = Security::sanitize($_POST['name']);
        $email = Security::sanitize($_POST['email']);
        $password = $_POST['password'];
        $role = Security::sanitize($_POST['role']);
        
        // Basic validation
        if(strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $user = new User();
            if($user->register($name, $email, $password, $role)) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "Email already exists or registration failed.";
            }
        }
    }
}

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-glass">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4" style="color:#2D6A4F;">
                        <i class="bi bi-person-plus"></i> Join Garden Hub
                    </h3>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle"></i> <?= Security::escape($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= Security::escape($_POST['name'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= Security::escape($_POST['email'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Your Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Choose...</option>
                                <option value="user">Community Member</option>
                                <option value="plot_owner">Plot Owner</option>
                                <option value="volunteer">Volunteer</option>
                                <option value="warden">Warden</option>
                                <option value="garden_manager">Garden Manager</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-garden w-100 mb-3">
                            <i class="bi bi-person-plus"></i> Create Account
                        </button>
                        
                        <p class="text-center text-muted mb-0">
                            Already have an account? <a href="login.php" style="color:#2D6A4F;">Log In</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>