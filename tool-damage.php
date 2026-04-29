<?php
$pageTitle = 'Tool Damage Report - Warden';
require_once '../../core/Security.php';
require_once '../../classes/Warden.php';

Security::requireRole(['warden']);

$warden = new Warden($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle damage report
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'report_damage') {
            $toolId = (int)$_POST['tool_id'];
            $damageType = Security::sanitize($_POST['damage_type']);
            $severity = Security::sanitize($_POST['severity']);
            $description = Security::sanitize($_POST['description']);
            $estimatedCost = (float)$_POST['estimated_cost'];
            
            $stmt = $db->prepare("INSERT INTO tool_damage_reports (toolId, reportedBy, damageType, severity, description, estimatedCost, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            if($stmt->execute([$toolId, $_SESSION['userId'], $damageType, $severity, $description, $estimatedCost])) {
                $success = "Tool damage report submitted successfully!";
            } else {
                $error = "Failed to submit report.";
            }
        } elseif($action === 'update_status') {
            $reportId = (int)$_POST['report_id'];
            $status = Security::sanitize($_POST['status']);
            
            $stmt = $db->prepare("UPDATE tool_damage_reports SET status = ?, resolvedBy = ?, resolvedAt = NOW() WHERE reportId = ?");
            if($stmt->execute([$status, $_SESSION['userId'], $reportId])) {
                $success = "Report status updated!";
            } else {
                $error = "Failed to update status.";
            }
        }
    }
}

// Get all tools
$tools = $db->query("SELECT * FROM tools ORDER BY toolName")->fetchAll(PDO::FETCH_ASSOC);

// Get damage reports
$damageReports = $db->query("SELECT tdr.*, t.toolName, u.name as reporter_name 
                             FROM tool_damage_reports tdr 
                             JOIN tools t ON tdr.toolId = t.toolId 
                             JOIN users u ON tdr.reportedBy = u.userId 
                             ORDER BY tdr.reportedAt DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-tools"></i> Tool Damage Report
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
                <!-- Report Damage -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--danger); color:white;">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Report Tool Damage</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <input type="hidden" name="action" value="report_damage">
                                
                                <div class="mb-3">
                                    <label class="form-label">Select Tool</label>
                                    <select name="tool_id" class="form-select" required>
                                        <option value="">Select tool...</option>
                                        <?php foreach($tools as $tool): ?>
                                            <option value="<?= $tool['toolId'] ?>">
                                                <?= Security::escape($tool['toolName']) ?> - <?= Security::escape($tool['condition']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Damage Type</label>
                                    <select name="damage_type" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="broken">Broken/Non-functional</option>
                                        <option value="worn">Worn Out</option>
                                        <option value="rusted">Rusted</option>
                                        <option value="missing_parts">Missing Parts</option>
                                        <option value="cosmetic">Cosmetic Damage</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Severity</label>
                                    <select name="severity" class="form-select" required>
                                        <option value="low">Low - Still usable</option>
                                        <option value="medium">Medium - Limited use</option>
                                        <option value="high">High - Not usable</option>
                                        <option value="critical">Critical - Safety hazard</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Estimated Repair/Replacement Cost ($)</label>
                                    <input type="number" name="estimated_cost" class="form-control" step="0.01" min="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Damage Description</label>
                                    <textarea name="description" class="form-control" rows="4" required 
                                              placeholder="Describe the damage in detail..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-send"></i> Submit Damage Report
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Damage Reports List -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Damage Reports</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($damageReports)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-check-circle display-1"></i>
                                    <p class="mt-3">No damage reports</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                    <table class="table table-hover mb-0">
                                        <thead class="sticky-top bg-white">
                                            <tr>
                                                <th>Tool</th>
                                                <th>Damage Type</th>
                                                <th>Severity</th>
                                                <th>Cost</th>
                                                <th>Status</th>
                                                <th>Reporter</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($damageReports as $report): ?>
                                            <tr>
                                                <td><strong><?= Security::escape($report['toolName']) ?></strong></td>
                                                <td><?= ucfirst(str_replace('_', ' ', $report['damageType'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $report['severity'] === 'critical' ? 'danger' : ($report['severity'] === 'high' ? 'danger' : ($report['severity'] === 'medium' ? 'warning' : 'info')) ?>">
                                                        <?= ucfirst($report['severity']) ?>
                                                    </span>
                                                </td>
                                                <td>$<?= number_format($report['estimatedCost'], 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $report['status'] === 'resolved' ? 'success' : ($report['status'] === 'in_progress' ? 'warning' : 'secondary') ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $report['status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= Security::escape($report['reporter_name']) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                            data-bs-target="#statusModal<?= $report['reportId'] ?>">
                                                        <i class="bi bi-pencil"></i> Update
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Update Status Modal -->
                                            <div class="modal fade" id="statusModal<?= $report['reportId'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Report #<?= $report['reportId'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="report_id" value="<?= $report['reportId'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Tool:</strong></label>
                                                                    <p><?= Security::escape($report['toolName']) ?></p>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Damage:</strong></label>
                                                                    <p><?= Security::escape($report['description']) ?></p>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="pending" <?= $report['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                        <option value="in_progress" <?= $report['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                                        <option value="resolved" <?= $report['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                                        <option value="closed" <?= $report['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                                                    </select>
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
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>