<?php
$pageTitle = 'Tool Management - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';
require_once '../../classes/Tool.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$success = null;
$error = null;

// Handle tool update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $toolId = (int)$_POST['tool_id'];
        $action = $_POST['action'];
        
        if($action === 'update_status') {
            $newStatus = Security::sanitize($_POST['status']);
            if($manager->updateToolStatus($toolId, $newStatus)) {
                $success = "Tool status updated successfully!";
            } else {
                $error = "Failed to update tool status.";
            }
        }
    }
}

// Get all tools
$tool = new Tool();
$tools = $tool->getAllTools();

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-tools"></i> Tool Management
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
                                <th>Tool Name</th>
                                <th>Status</th>
                                <th>Usage Hours</th>
                                <th>Last Maintenance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tools as $t): ?>
                            <tr>
                                <td><?= $t['toolId'] ?></td>
                                <td><?= Security::escape($t['name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $t['status'] === 'available' ? 'success' : ($t['status'] === 'reserved' ? 'warning' : 'danger') ?>">
                                        <?= ucfirst($t['status']) ?>
                                    </span>
                                </td>
                                <td><?= $t['usageHours'] ?> hrs</td>
                                <td><?= $t['lastMaintenance'] ? date('M d, Y', strtotime($t['lastMaintenance'])) : 'N/A' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                            data-bs-target="#toolModal<?= $t['toolId'] ?>">
                                        <i class="bi bi-pencil"></i> Update
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Tool Update Modal -->
                            <div class="modal fade" id="toolModal<?= $t['toolId'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Tool: <?= Security::escape($t['name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                <input type="hidden" name="tool_id" value="<?= $t['toolId'] ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                
                                                <label class="form-label">Update Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="available" <?= $t['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                                    <option value="maintenance" <?= $t['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                                    <option value="out_of_service" <?= $t['status'] === 'out_of_service' ? 'selected' : '' ?>>Out of Service</option>
                                                </select>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-garden">Update Tool</button>
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