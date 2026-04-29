<?php
$pageTitle = 'Usage Based Maintenance - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';
require_once '../../classes/Tool.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle maintenance
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $toolId = (int)$_POST['tool_id'];
        $action = $_POST['action'];
        
        if($action === 'schedule_maintenance') {
            if($manager->scheduleMaintenance($toolId)) {
                $success = "Maintenance scheduled successfully!";
            } else {
                $error = "Failed to schedule maintenance.";
            }
        } elseif($action === 'complete_maintenance') {
            $stmt = $db->prepare("UPDATE tools SET status = 'available', lastMaintenance = CURDATE(), usageHours = 0 WHERE toolId = ?");
            if($stmt->execute([$toolId])) {
                $success = "Maintenance completed successfully!";
            } else {
                $error = "Failed to complete maintenance.";
            }
        }
    }
}

// Get tools needing maintenance (usage >= 100 hours OR status = maintenance)
$toolsNeedingMaintenance = $db->query("SELECT * FROM tools 
                                      WHERE usageHours >= 100 OR status = 'maintenance' 
                                      ORDER BY usageHours DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get all tools for overview
$allTools = $db->query("SELECT * FROM tools ORDER BY toolId")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-tools"></i> Usage Based Maintenance
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
            
            <!-- Tools Needing Maintenance -->
            <div class="card card-glass mb-4">
                <div class="card-header" style="background:var(--danger); color:white;">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Tools Requiring Maintenance</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($toolsNeedingMaintenance)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-check-circle display-1"></i>
                            <p class="mt-3">All tools are in good condition!</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tool ID</th>
                                    <th>Name</th>
                                    <th>Usage Hours</th>
                                    <th>Last Maintenance</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($toolsNeedingMaintenance as $tool): ?>
                                <tr class="<?= $tool['usageHours'] >= 100 ? 'table-warning' : '' ?>">
                                    <td>#<?= $tool['toolId'] ?></td>
                                    <td><?= Security::escape($tool['name']) ?></td>
                                    <td>
                                        <strong><?= $tool['usageHours'] ?> hrs</strong>
                                        <?php if($tool['usageHours'] >= 100): ?>
                                            <span class="badge bg-danger ms-1">Due</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $tool['lastMaintenance'] ? date('M d, Y', strtotime($tool['lastMaintenance'])) : 'Never' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $tool['status'] === 'maintenance' ? 'warning' : 'danger' ?>">
                                            <?= ucfirst($tool['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($tool['status'] !== 'maintenance'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                            <input type="hidden" name="tool_id" value="<?= $tool['toolId'] ?>">
                                            <input type="hidden" name="action" value="schedule_maintenance">
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <i class="bi bi-wrench"></i> Schedule
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                            <input type="hidden" name="tool_id" value="<?= $tool['toolId'] ?>">
                                            <input type="hidden" name="action" value="complete_maintenance">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-check"></i> Complete
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- All Tools Overview -->
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0"><i class="bi bi-list"></i> All Tools - Usage Overview</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Tool ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Usage Hours</th>
                                <th>Last Maintenance</th>
                                <th>Maintenance Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($allTools as $tool): 
                                $maintenanceDue = $tool['usageHours'] >= 100;
                            ?>
                            <tr>
                                <td>#<?= $tool['toolId'] ?></td>
                                <td><?= Security::escape($tool['name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $tool['status'] === 'available' ? 'success' : ($tool['status'] === 'reserved' ? 'warning' : 'danger') ?>">
                                        <?= ucfirst($tool['status']) ?>
                                    </span>
                                </td>
                                <td><?= $tool['usageHours'] ?> hrs</td>
                                <td><?= $tool['lastMaintenance'] ? date('M d, Y', strtotime($tool['lastMaintenance'])) : 'Never' ?></td>
                                <td>
                                    <?php if($maintenanceDue): ?>
                                        <span class="badge bg-danger">Due Now</span>
                                    <?php elseif($tool['usageHours'] >= 80): ?>
                                        <span class="badge bg-warning">Soon (<?= 100 - $tool['usageHours'] ?> hrs)</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> <strong>Maintenance Schedule:</strong> Tools require maintenance every 100 usage hours. Schedule maintenance to prevent breakdowns.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>