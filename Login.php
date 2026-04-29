<?php
$pageTitle = 'Log In - Garden Hub';
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
        $email = Security::sanitize($_POST['email']);
        $password = $_POST['password'];
        $redirect = $_GET['redirect'] ?? null;
        
        $user = new User();
        if($user->login($email, $password)) {
            // Redirect to dashboard or previous page
            $target = $redirect ?: "/garden-hub/public/{$_SESSION['role']}/dashboard.php";
            header("Location: " . $target);
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-glass">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4" style="color:#2D6A4F;">
                        <i class="bi bi-box-arrow-in-right"></i> Log In
                    </h3>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle"></i> <?= Security::escape($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['registered'])): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Registration successful! Please log in.
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?= Security::escape($_POST['email'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-garden w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Log In
                        </button>
                        
                        <p class="text-center text-muted mb-0">
                            Don't have an account? <a href="register.php" style="color:#2D6A4F;">Join Us</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>