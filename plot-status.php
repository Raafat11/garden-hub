<?php
$pageTitle = 'Update Plot Status - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';
require_once '../../classes/Plot.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle plot status update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $plotId = (int)$_POST['plot_id'];
        $newStatus = Security::sanitize($_POST['status']);
        
        if($manager->maintainPlot($plotId, $newStatus)) {
            $success = "Plot status updated successfully!";
        } else {
            $error = "Failed to update plot status. Note: Rented plots cannot be changed manually.";
        }
    }
}

// Get all plots with details
$plots = $db->query("SELECT p.*, u.name as owner_name, l.endDate as lease_end
                     FROM plots p 
                     LEFT JOIN leases l ON p.plotId = l.plotId AND l.status = 'active'
                     LEFT JOIN users u ON l.userId = u.userId
                     ORDER BY p.plotId")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-house-gear"></i> Update Plot Status
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
                                <th>Plot ID</th>
                                <th>Area</th>
                                <th>Soil Type</th>
                                <th>Sunlight</th>
                                <th>Current Owner</th>
                                <th>Lease Ends</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($plots as $plot): ?>
                            <tr>
                                <td>Plot #<?= $plot['plotId'] ?></td>
                                <td><?= $plot['area'] ?> m²</td>
                                <td><?= Security::escape($plot['soilType']) ?></td>
                                <td><?= $plot['sunlightHours'] ?> hrs/day</td>
                                <td><?= Security::escape($plot['owner_name'] ?? 'Unassigned') ?></td>
                                <td><?= $plot['lease_end'] ? date('M d, Y', strtotime($plot['lease_end'])) : 'N/A' ?></td>
                                <td>
                                    <span class="badge bg-<?= $plot['status'] === 'available' ? 'success' : ($plot['status'] === 'rented' ? 'primary' : ($plot['status'] === 'maintenance' ? 'warning' : 'secondary')) ?>">
                                        <?= ucfirst($plot['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($plot['status'] !== 'rented'): ?>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                            data-bs-target="#statusModal<?= $plot['plotId'] ?>">
                                        <i class="bi bi-pencil"></i> Update
                                    </button>
                                    <?php else: ?>
                                        <span class="text-muted">Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Status Update Modal -->
                            <div class="modal fade" id="statusModal<?= $plot['plotId'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Plot #<?= $plot['plotId'] ?> Status</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                <input type="hidden" name="plot_id" value="<?= $plot['plotId'] ?>">
                                                
                                                <label class="form-label">Select New Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="available" <?= $plot['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                                    <option value="maintenance" <?= $plot['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                                    <option value="inactive" <?= $plot['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                                
                                                <div class="alert alert-info mt-3">
                                                    <small><i class="bi bi-info-circle"></i> Rented plots cannot be changed manually. They become available automatically when lease expires.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-garden">Update Status</button>
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