<?php
$pageTitle = 'Create Report - Warden';
require_once '../../core/Security.php';
require_once '../../classes/Warden.php';

Security::requireRole(['warden']);

$warden = new Warden($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle report creation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $title = Security::sanitize($_POST['title']);
        $description = Security::sanitize($_POST['description']);
        $severity = Security::sanitize($_POST['severity']);
        $location = Security::sanitize($_POST['location']);
        $category = Security::sanitize($_POST['category']);
        
        if($warden->createReport($title, $description, $severity, $location, $category)) {
            $success = "Incident report created successfully!";
        } else {
            $error = "Failed to create report.";
        }
    }
}

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-file-earmark-plus"></i> Create Incident Report
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
                <div class="card-header" style="background:var(--danger); color:white;">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Report Incident</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Report Title</label>
                            <input type="text" name="title" class="form-control" required 
                                   placeholder="e.g., Vandalism in Section B">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Severity</label>
                                <select name="severity" class="form-select" required>
                                    <option value="">Select severity...</option>
                                    <option value="low">Low - Minor issue</option>
                                    <option value="medium">Medium - Requires attention</option>
                                    <option value="high">High - Urgent action needed</option>
                                    <option value="critical">Critical - Immediate response</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select category...</option>
                                    <option value="security">Security Breach</option>
                                    <option value="vandalism">Vandalism</option>
                                    <option value="theft">Theft</option>
                                    <option value="safety">Safety Hazard</option>
                                    <option value="maintenance">Maintenance Issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" required 
                                   placeholder="e.g., Plot #15, Tool Shed, Main Gate">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Detailed Description</label>
                            <textarea name="description" class="form-control" rows="5" required 
                                      placeholder="Provide detailed information about the incident..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-send"></i> Submit Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>