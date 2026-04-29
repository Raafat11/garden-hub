<?php
$pageTitle = 'My Profile - Garden Hub';
require_once '../core/Security.php';
require_once '../classes/User.php';

Security::requireLogin();

$user = new User($_SESSION['userId']);
$success = null;
$error = null;

// Handle profile update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $data = [
            'name' => Security::sanitize($_POST['name']),
            'email' => Security::sanitize($_POST['email'])
        ];
        
        if($user->updateProfile($data)) {
            $_SESSION['name'] = $data['name'];
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile.";
        }
    }
}

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="row">
        <?php include '../templates/sidebar.php'; ?>
        
        <div class="col-md-10">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-person-circle"></i> My Profile
            </h2>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= Security::escape($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?= Security::escape($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="card card-glass">
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= Security::escape($user->getName()) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= Security::escape($user->getEmail()) ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" 
                                       value="<?= ucfirst(str_replace('_', ' ', $user->getRole())) ?>" disabled>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Community Points</label>
                                <input type="text" class="form-control" 
                                       value="<?= $user->getCommunityPoints() ?>" disabled>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Karma Points</label>
                                <input type="text" class="form-control" 
                                       value="<?= $user->getKarmaPoints() ?>" disabled>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-garden">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>