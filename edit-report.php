<?php
$pageTitle = 'Update Report - Warden';
require_once '../../core/Security.php';
require_once '../../classes/Warden.php';

Security::requireRole(['warden']);

$warden = new Warden($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle report update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $reportId = (int)$_POST['report_id'];
        $status = Security::sanitize($_POST['status']);
        $resolution = Security::sanitize($_POST['resolution']);
        
        if($warden->updateReport($reportId, $status, $resolution)) {
            $success = "Report updated successfully!";
        } else {
            $error = "Failed to update report.";
        }
    }
}

// Get all reports
$reports = $db->query("SELECT ir.*, u.name as reporter_name 
                       FROM incident_reports ir 
                       LEFT JOIN users u ON ir.createdBy = u.userId 
                       ORDER BY ir.createdAt DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-pencil-square"></i> Update Report
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
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Incident Reports</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($reports)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-inbox display-1"></i>
                            <p class="mt-3">No reports found</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Severity</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Reporter</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($reports as $report): ?>
                                <tr>
                                    <td>#<?= $report['reportId'] ?></td>
                                    <td><?= Security::escape($report['title']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $report['severity'] === 'critical' ? 'danger' : ($report['severity'] === 'high' ? 'danger' : ($report['severity'] === 'medium' ? 'warning' : 'info')) ?>">
                                            <?= ucfirst($report['severity']) ?>
                                        </span>
                                    </td>
                                    <td><?= ucfirst($report['category']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $report['status'] === 'resolved' ? 'success' : ($report['status'] === 'in_progress' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst(str_replace('_', ' ', $report['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= Security::escape($report['reporter_name'] ?? 'System') ?></td>
                                    <td><?= date('M d, Y', strtotime($report['createdAt'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                data-bs-target="#updateModal<?= $report['reportId'] ?>">
                                            <i class="bi bi-pencil"></i> Update
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Update Modal -->
                                <div class="modal fade" id="updateModal<?= $report['reportId'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Report #<?= $report['reportId'] ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                    <input type="hidden" name="report_id" value="<?= $report['reportId'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label"><strong>Title:</strong></label>
                                                        <p><?= Security::escape($report['title']) ?></p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label"><strong>Description:</strong></label>
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
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Resolution Notes</label>
                                                        <textarea name="resolution" class="form-control" rows="4" 
                                                                  placeholder="Describe actions taken or resolution details..."><?= Security::escape($report['resolution'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-garden">Update Report</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>