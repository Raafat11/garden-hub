<?php
$pageTitle = 'Garden Security Access - Warden';
require_once '../../core/Security.php';
require_once '../../classes/Warden.php';

Security::requireRole(['warden']);

$warden = new Warden($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle alert sending
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'send_alert') {
            $alertType = Security::sanitize($_POST['alert_type']);
            $message = Security::sanitize($_POST['message']);
            $severity = Security::sanitize($_POST['severity']);
            
            if($warden->sendAlert($alertType, $message, $severity)) {
                $success = "Security alert sent successfully!";
            } else {
                $error = "Failed to send alert.";
            }
        } elseif($action === 'toggle_access') {
            $gateId = (int)$_POST['gate_id'];
            $newStatus = Security::sanitize($_POST['new_status']);
            
            $stmt = $db->prepare("UPDATE garden_gates SET status = ?, lastUpdated = NOW() WHERE gateId = ?");
            if($stmt->execute([$newStatus, $gateId])) {
                $success = "Gate access updated successfully!";
            } else {
                $error = "Failed to update gate access.";
            }
        }
    }
}

// Get gate access status
$gates = $db->query("SELECT * FROM garden_gates ORDER BY gateName")->fetchAll(PDO::FETCH_ASSOC);

// Get recent security alerts
$alerts = $db->query("SELECT sa.*, u.name as sender_name 
                      FROM security_alerts sa 
                      JOIN users u ON sa.senderId = u.userId 
                      ORDER BY sa.createdAt DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Get security log
$securityLog = $db->query("SELECT * FROM security_log ORDER BY timestamp DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-shield-check"></i> Garden Security Access + Send Alert
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
                <!-- Gate Access Control -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-door-open"></i> Gate Access Control</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach($gates as $gate): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= Security::escape($gate['gateName']) ?></h6>
                                            <small class="text-muted">Location: <?= Security::escape($gate['location']) ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?= $gate['status'] === 'locked' ? 'danger' : 'success' ?> me-2">
                                                <?= ucfirst($gate['status']) ?>
                                            </span>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                <input type="hidden" name="action" value="toggle_access">
                                                <input type="hidden" name="gate_id" value="<?= $gate['gateId'] ?>">
                                                <input type="hidden" name="new_status" value="<?= $gate['status'] === 'locked' ? 'unlocked' : 'locked' ?>">
                                                <button type="submit" class="btn btn-sm btn-<?= $gate['status'] === 'locked' ? 'success' : 'danger' ?>">
                                                    <i class="bi bi-<?= $gate['status'] === 'locked' ? 'unlock' : 'lock' ?>"></i>
                                                    <?= $gate['status'] === 'locked' ? 'Unlock' : 'Lock' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Send Alert -->
                    <div class="card card-glass mt-4">
                        <div class="card-header" style="background:var(--danger); color:white;">
                            <h5 class="mb-0"><i class="bi bi-bell"></i> Send Security Alert</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <input type="hidden" name="action" value="send_alert">
                                
                                <div class="mb-3">
                                    <label class="form-label">Alert Type</label>
                                    <select name="alert_type" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="intrusion">Intrusion Detected</option>
                                        <option value="suspicious_activity">Suspicious Activity</option>
                                        <option value="theft">Theft Report</option>
                                        <option value="vandalism">Vandalism</option>
                                        <option value="emergency">Emergency</option>
                                        <option value="general">General Alert</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Severity</label>
                                    <select name="severity" class="form-select" required>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Alert Message</label>
                                    <textarea name="message" class="form-control" rows="3" required 
                                              placeholder="Describe the security issue..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-send"></i> Send Alert
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Security Log -->
                <div class="col-md-6">
                    <div class="card card-glass mb-4">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Recent Alerts</h5>
                        </div>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <?php if(empty($alerts)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-shield-check"></i>
                                    <p class="mt-2">No security alerts</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($alerts as $alert): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?= ucfirst(str_replace('_', ' ', $alert['alertType'])) ?></h6>
                                            <span class="badge bg-<?= $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'high' ? 'danger' : ($alert['severity'] === 'medium' ? 'warning' : 'info')) ?>">
                                                <?= ucfirst($alert['severity']) ?>
                                            </span>
                                        </div>
                                        <p class="mb-1"><small><?= Security::escape($alert['message']) ?></small></p>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?= Security::escape($alert['sender_name']) ?> | 
                                            <?= date('M d, H:i', strtotime($alert['createdAt'])) ?>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Security Access Log</h5>
                        </div>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <?php if(empty($securityLog)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mt-2">No access logs</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($securityLog as $log): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <i class="bi bi-<?= $log['action'] === 'unlock' ? 'unlock' : 'lock' ?>"></i>
                                                <strong><?= ucfirst($log['action']) ?></strong> - <?= Security::escape($log['gateName']) ?>
                                            </div>
                                            <small class="text-muted"><?= date('M d, H:i', strtotime($log['timestamp'])) ?></small>
                                        </div>
                                        <small class="text-muted">By: <?= Security::escape($log['userName']) ?></small>
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