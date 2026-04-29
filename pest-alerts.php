<?php
$pageTitle = 'Pest/Disease Alerts - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';
require_once '../../classes/Alert.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$alert = new Alert();
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle pest alert creation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $pestType = Security::sanitize($_POST['pest_type']);
        $severity = Security::sanitize($_POST['severity']);
        $message = Security::sanitize($_POST['message']);
        $affectedPlots = $_POST['affected_plots'] ?? [];
        
        $fullMessage = "Pest/Disease Alert: $pestType. " . $message;
        if(!empty($affectedPlots)) {
            $fullMessage .= " Affected Plots: " . implode(', ', $affectedPlots);
        }
        
        if($alert->triggerAlert('pest', $severity, $fullMessage, $_SESSION['userId'])) {
            $success = "Pest/Disease alert sent to all users successfully!";
        } else {
            $error = "Failed to send alert.";
        }
    }
}

// Get recent pest alerts
$pestAlerts = $db->query("SELECT a.*, u.name as triggered_by_name 
                          FROM alerts a 
                          LEFT JOIN users u ON a.triggeredBy = u.userId 
                          WHERE a.type = 'pest' 
                          ORDER BY a.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// Get plots for selection
$plots = $db->query("SELECT plotId FROM plots WHERE status = 'rented' ORDER BY plotId")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-bug"></i> Pest/Disease Alerts
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
            
            <div class="row g-4">
                <!-- Create Pest Alert -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Create Pest/Disease Alert</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Pest/Disease Type</label>
                                    <select name="pest_type" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="aphids">Aphids</option>
                                        <option value="whitefly">Whitefly</option>
                                        <option value="caterpillars">Caterpillars</option>
                                        <option value="fungal_disease">Fungal Disease</option>
                                        <option value="powdery_mildew">Powdery Mildew</option>
                                        <option value="blight">Blight</option>
                                        <option value="root_rot">Root Rot</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Severity Level</label>
                                    <select name="severity" class="form-select" required>
                                        <option value="low">Low - Monitor</option>
                                        <option value="medium">Medium - Take Action</option>
                                        <option value="high">High - Immediate Action</option>
                                        <option value="critical">Critical - Emergency</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Affected Plots (Optional)</label>
                                    <select name="affected_plots[]" class="form-select" multiple size="5">
                                        <?php foreach($plots as $plot): ?>
                                            <option value="Plot #<?= $plot['plotId'] ?>">Plot #<?= $plot['plotId'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl to select multiple</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Treatment Instructions</label>
                                    <textarea name="message" class="form-control" rows="4" required 
                                              placeholder="Provide treatment recommendations and preventive measures..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-broadcast"></i> Send Pest Alert
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Pest Alerts -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Pest/Disease Alerts</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($pestAlerts)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-shield-check display-1"></i>
                                    <p class="mt-3">No pest alerts. Garden is healthy!</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($pestAlerts as $pAlert): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <span class="badge bg-<?= $pAlert['severity'] === 'critical' ? 'danger' : ($pAlert['severity'] === 'high' ? 'warning' : 'info') ?> mb-2">
                                                    <?= ucfirst($pAlert['severity']) ?>
                                                </span>
                                                <p class="mb-1"><?= Security::escape($pAlert['message']) ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?= Security::escape($pAlert['triggered_by_name'] ?? 'System') ?> | 
                                                    <i class="bi bi-clock"></i> <?= date('M d, Y H:i', strtotime($pAlert['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>