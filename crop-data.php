<?php
$pageTitle = 'Crop Data - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';
require_once '../../classes/Crop.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Get owner's plot
$lease = $db->prepare("SELECT plotId FROM leases WHERE userId = ? AND status = 'active' LIMIT 1");
$lease->execute([$_SESSION['userId']]);
$plotId = $lease->fetchColumn();

if(!$plotId) {
    $error = "You need an active plot lease to track crops.";
} else {
    // Handle crop update
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
            $error = "Invalid request.";
        } else {
            $cropId = (int)$_POST['crop_id'];
            $action = $_POST['action'];
            
            if($action === 'update_status') {
                $newStatus = Security::sanitize($_POST['status']);
                $notes = Security::sanitize($_POST['notes']);
                $stmt = $db->prepare("UPDATE crop_records SET status = ?, notes = ? WHERE cropId = ? AND plotId = ?");
                if($stmt->execute([$newStatus, $notes, $cropId, $plotId])) {
                    $success = "Crop data updated successfully!";
                } else {
                    $error = "Failed to update crop data.";
                }
            }
        }
    }
}

// Get all crops for this plot
$crops = $db->prepare("SELECT cr.*, p.plantName, p.growthDays, p.waterNeeds 
                       FROM crop_records cr 
                       JOIN plants p ON cr.plantId = p.plantId 
                       WHERE cr.plotId = ? 
                       ORDER BY cr.plantDate DESC");
$crops->execute([$plotId]);
$cropList = $crops->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-graph-up"></i> Crop Data - Plot #<?= $plotId ?>
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
            
            <?php if($plotId): ?>
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0"><i class="bi bi-flower1"></i> Your Crops - Growth Tracking</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($cropList)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-seedling display-1"></i>
                            <p class="mt-3">No crops planted yet</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Plant Name</th>
                                    <th>Planted Date</th>
                                    <th>Expected Harvest</th>
                                    <th>Days Growing</th>
                                    <th>Water Needs</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cropList as $crop): 
                                    $daysGrowing = floor((time() - strtotime($crop['plantDate'])) / 86400);
                                    $expectedHarvest = date('M d, Y', strtotime($crop['plantDate'] . " +{$crop['growthDays']} days"));
                                ?>
                                <tr>
                                    <td><?= Security::escape($crop['plantName']) ?></td>
                                    <td><?= date('M d, Y', strtotime($crop['plantDate'])) ?></td>
                                    <td><?= $expectedHarvest ?></td>
                                    <td>
                                        <strong><?= $daysGrowing ?></strong> / <?= $crop['growthDays'] ?> days
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-success" style="width: <?= min(100, ($daysGrowing / $crop['growthDays']) * 100) ?>%"></div>
                                        </div>
                                    </td>
                                    <td><?= Security::escape($crop['waterNeeds']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $crop['status'] === 'harvested' ? 'success' : ($crop['status'] === 'growing' ? 'primary' : 'warning') ?>">
                                            <?= ucfirst($crop['status']) ?>
                                        </span>
                                    </td>
                                    <td><small><?= Security::escape($crop['notes'] ?? '-') ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                data-bs-target="#updateModal<?= $crop['cropId'] ?>">
                                            <i class="bi bi-pencil"></i> Update
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Update Modal -->
                                <div class="modal fade" id="updateModal<?= $crop['cropId'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update: <?= Security::escape($crop['plantName']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                    <input type="hidden" name="crop_id" value="<?= $crop['cropId'] ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="planted" <?= $crop['status'] === 'planted' ? 'selected' : '' ?>>Planted</option>
                                                            <option value="growing" <?= $crop['status'] === 'growing' ? 'selected' : '' ?>>Growing</option>
                                                            <option value="flowering" <?= $crop['status'] === 'flowering' ? 'selected' : '' ?>>Flowering</option>
                                                            <option value="fruiting" <?= $crop['status'] === 'fruiting' ? 'selected' : '' ?>>Fruiting</option>
                                                            <option value="harvested" <?= $crop['status'] === 'harvested' ? 'selected' : '' ?>>Harvested</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Notes</label>
                                                        <textarea name="notes" class="form-control" rows="3" 
                                                                  placeholder="Add growth notes, observations..."><?= Security::escape($crop['notes'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-garden">Update Crop</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>