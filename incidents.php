<?php
$pageTitle = 'Incident Reporting - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';
require_once '../../classes/Incident.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle incident creation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $type = Security::sanitize($_POST['type']);
        $description = Security::sanitize($_POST['description']);
        $severity = Security::sanitize($_POST['severity']);
        $plotId = !empty($_POST['plot_id']) ? (int)$_POST['plot_id'] : null;
        
        $incident = new Incident();
        $incident->reportedBy = $_SESSION['userId'];
        $incident->type = $type;
        $incident->description = $description;
        $incident->severity = $severity;
        $incident->plotId = $plotId;
        
        if($incident->save()) {
            $success = "Incident reported successfully!";
        } else {
            $error = "Failed to report incident.";
        }
    }
}

// Get all incidents
$incidents = $db->query("SELECT i.*, u.name as reporter_name, p.plotId 
                         FROM incidents i 
                         LEFT JOIN users u ON i.reportedBy = u.userId 
                         LEFT JOIN plots p ON i.plotId = p.plotId 
                         ORDER BY i.date DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

// Get plots for dropdown
$plots = $db->query("SELECT plotId FROM plots ORDER BY plotId")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-exclamation-circle"></i> Incident Reporting
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
                <!-- Report New Incident -->
                <div class="col-md-4">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Report New Incident</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Incident Type</label>
                                    <select name="type" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="theft">Theft</option>
                                        <option value="vandalism">Vandalism</option>
                                        <option value="pest">Pest Infestation</option>
                                        <option value="disease">Plant Disease</option>
                                        <option value="equipment_damage">Equipment Damage</option>
                                        <option value="water_issue">Water Issue</option>
                                        <option value="other">Other</option>
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
                                    <label class="form-label">Related Plot (Optional)</label>
                                    <select name="plot_id" class="form-select">
                                        <option value="">None</option>
                                        <?php foreach($plots as $plot): ?>
                                            <option value="<?= $plot['plotId'] ?>">Plot #<?= $plot['plotId'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="4" required 
                                              placeholder="Describe the incident in detail..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-exclamation-triangle"></i> Report Incident
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Incident List -->
                <div class="col-md-8">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Incidents</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 datatable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Severity</th>
                                            <th>Plot</th>
                                            <th>Status</th>
                                            <th>Reporter</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($incidents as $incident): ?>
                                        <tr>
                                            <td><small><?= date('M d, H:i', strtotime($incident['date'])) ?></small></td>
                                            <td><?= Security::escape($incident['type']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $incident['severity'] === 'critical' ? 'danger' : ($incident['severity'] === 'high' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($incident['severity']) ?>
                                                </span>
                                            </td>
                                            <td><?= $incident['plotId'] ? 'Plot #' . $incident['plotId'] : 'N/A' ?></td>
                                            <td>
                                                <span class="badge bg-<?= $incident['status'] === 'closed' ? 'success' : ($incident['status'] === 'escalated' ? 'danger' : 'warning') ?>">
                                                    <?= ucfirst($incident['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= Security::escape($incident['reporter_name'] ?? 'Unknown') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>