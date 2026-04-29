<?php
$pageTitle = 'Emergency Broadcasts - Admin';
require_once '../../core/Security.php';
require_once '../../classes/Alert.php';

Security::requireRole(['admin']);

$alert = new Alert();
$success = null;
$error = null;

// Handle broadcast creation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $type = Security::sanitize($_POST['type']);
        $severity = Security::sanitize($_POST['severity']);
        $message = Security::sanitize($_POST['message']);
        
        if($alert->triggerAlert($type, $severity, $message, $_SESSION['userId'])) {
            $success = "Emergency broadcast sent successfully to all users!";
        } else {
            $error = "Failed to send broadcast.";
        }
    }
}

// Get active alerts
$activeAlerts = $alert->getActiveAlerts();

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-megaphone"></i> Emergency Broadcasts
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
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-send"></i> Send New Broadcast</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Alert Type</label>
                                    <select name="type" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="weather">Weather Alert</option>
                                        <option value="pest">Pest/Disease Alert</option>
                                        <option value="security">Security Alert</option>
                                        <option value="maintenance">Maintenance Notice</option>
                                        <option value="general">General Announcement</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Severity Level</label>
                                    <select name="severity" class="form-select" required>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea name="message" class="form-control" rows="4" required 
                                              placeholder="Enter alert message..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-broadcast"></i> Send Broadcast
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Broadcasts (Last 7 Days)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($activeAlerts)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox"></i> No recent broadcasts
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($activeAlerts as $alert): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <span class="badge bg-<?= $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'high' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($alert['severity']) ?>
                                                </span>
                                                <span class="badge bg-secondary ms-1"><?= ucfirst($alert['type']) ?></span>
                                                <p class="mb-1 mt-2"><?= Security::escape($alert['message']) ?></p>
                                                <small class="text-muted">
                                                    <?= date('M d, Y H:i', strtotime($alert['created_at'])) ?>
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