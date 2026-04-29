<?php
$pageTitle = 'RBAC Management - Admin';
require_once '../../core/Security.php';
require_once '../../classes/Admin.php';

Security::requireRole(['admin']);

$admin = new Admin($_SESSION['userId']);
$success = null;
$error = null;

// Handle role update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $userId = (int)$_POST['user_id'];
        $newRole = Security::sanitize($_POST['role']);
        
        if($admin->assignRole($userId, $newRole)) {
            $success = "Role updated successfully!";
        } else {
            $error = "Failed to update role.";
        }
    }
}

// Get all users
$db = Database::getInstance()->getConnection();
$users = $db->query("SELECT userId, name, email, role, communityPoints FROM users ORDER BY userId DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-shield-lock"></i> RBAC - Role Management
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
                <div class="card-body">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Current Role</th>
                                <th>Points</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr>
                                <td><?= $user['userId'] ?></td>
                                <td><?= Security::escape($user['name']) ?></td>
                                <td><?= Security::escape($user['email']) ?></td>
                                <td>
                                    <span class="badge bg-primary"><?= ucfirst(str_replace('_', ' ', $user['role'])) ?></span>
                                </td>
                                <td><?= $user['communityPoints'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                            data-bs-target="#roleModal<?= $user['userId'] ?>">
                                        <i class="bi bi-pencil"></i> Change Role
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Role Change Modal -->
                            <div class="modal fade" id="roleModal<?= $user['userId'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Change Role: <?= Security::escape($user['name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                <input type="hidden" name="user_id" value="<?= $user['userId'] ?>">
                                                
                                                <label class="form-label">Select New Role</label>
                                                <select name="role" class="form-select" required>
                                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                    <option value="plot_owner" <?= $user['role'] === 'plot_owner' ? 'selected' : '' ?>>Plot Owner</option>
                                                    <option value="volunteer" <?= $user['role'] === 'volunteer' ? 'selected' : '' ?>>Volunteer</option>
                                                    <option value="warden" <?= $user['role'] === 'warden' ? 'selected' : '' ?>>Warden</option>
                                                    <option value="garden_manager" <?= $user['role'] === 'garden_manager' ? 'selected' : '' ?>>Garden Manager</option>
                                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-garden">Update Role</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>