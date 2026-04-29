<?php
$pageTitle = 'Weather Cancellation - Admin';
require_once '../../core/Security.php';
require_once '../../classes/Weather.php';
require_once '../../classes/Alert.php';

Security::requireRole(['admin']);

$weather = new Weather();
$success = null;
$error = null;

// Handle weather event
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $eventType = Security::sanitize($_POST['event_type']);
        $severity = Security::sanitize($_POST['severity']);
        $description = Security::sanitize($_POST['description']);
        
        // Log weather event
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO weather (forecast, eventType, severity, date) VALUES (?, ?, ?, CURDATE())");
        $stmt->execute([$description, $eventType, $severity]);
        
        // Trigger alert if high/critical
        if(in_array($severity, ['high', 'critical'])) {
            $alert = new Alert();
            $alert->triggerAlert('weather', $severity, "Weather Alert: $description", $_SESSION['userId']);
        }
        
        $success = "Weather event logged successfully!";
    }
}

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-cloud-lightning"></i> Weather Driven Cancellation
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
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Log Weather Event</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Event Type</label>
                                <select name="event_type" class="form-select" required>
                                    <option value="">Select...</option>
                                    <option value="storm">Storm</option>
                                    <option value="flood">Flood</option>
                                    <option value="drought">Drought</option>
                                    <option value="frost">Frost</option>
                                    <option value="heatwave">Heatwave</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Severity</label>
                                <select name="severity" class="form-select" required>
                                    <option value="low">Low - Monitor</option>
                                    <option value="medium">Medium - Caution</option>
                                    <option value="high">High - Cancel Activities</option>
                                    <option value="critical">Critical - Emergency</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description & Impact</label>
                            <textarea name="description" class="form-control" rows="3" required 
                                      placeholder="Describe the weather event and affected areas..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-cloud-lightning"></i> Log Event & Trigger Alerts
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>