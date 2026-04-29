<?php
$pageTitle = 'Compost Contribution - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle compost contribution
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $weight = (float)$_POST['weight'];
        $type = Security::sanitize($_POST['type']);
        $notes = Security::sanitize($_POST['notes']);
        
        $stmt = $db->prepare("INSERT INTO compost_contributions (userId, weight, type, notes, status, date) 
                              VALUES (?, ?, ?, ?, 'pending', NOW())");
        if($stmt->execute([$_SESSION['userId'], $weight, $type, $notes])) {
            $success = "Compost contribution recorded! Thank you for contributing to sustainability.";
        } else {
            $error = "Failed to record contribution.";
        }
    }
}

// Get user's compost history
$history = $db->prepare("SELECT * FROM compost_contributions WHERE userId = ? ORDER BY date DESC LIMIT 20");
$history->execute([$_SESSION['userId']]);
$contributions = $history->fetchAll(PDO::FETCH_ASSOC);

// Get total contributed
$totalWeight = $db->prepare("SELECT SUM(weight) FROM compost_contributions WHERE userId = ? AND status = 'approved'");
$totalWeight->execute([$_SESSION['userId']]);
$totalContributed = $totalWeight->fetchColumn() ?: 0;

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-recycle"></i> Compost Contribution
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
                <!-- Contribution Form -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--secondary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Record Compost Contribution</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Compost Type</label>
                                    <select name="type" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="kitchen_waste">Kitchen Waste</option>
                                        <option value="garden_waste">Garden Waste</option>
                                        <option value="leaves">Leaves</option>
                                        <option value="grass_clippings">Grass Clippings</option>
                                        <option value="manure">Manure</option>
                                        <option value="mixed">Mixed Organic</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" name="weight" class="form-control" step="0.1" min="0.1" required 
                                           placeholder="Enter weight in kg">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Notes (Optional)</label>
                                    <textarea name="notes" class="form-control" rows="3" 
                                              placeholder="Any additional details..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-garden w-100">
                                    <i class="bi bi-recycle"></i> Submit Contribution
                                </button>
                            </form>
                            
                            <div class="alert alert-info mt-3">
                                <strong><i class="bi bi-lightbulb"></i> Tip:</strong> 
                                Composting reduces waste and creates nutrient-rich soil for the community garden!
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats & History -->
                <div class="col-md-7">
                    <div class="card card-glass mb-3">
                        <div class="card-body text-center">
                            <i class="bi bi-award display-1" style="color:var(--secondary);"></i>
                            <h3 class="mt-2"><?= number_format($totalContributed, 1) ?> kg</h3>
                            <p class="text-muted mb-0">Total Compost Contributed</p>
                        </div>
                    </div>
                    
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Your Contribution History</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($contributions)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No contributions yet. Start composting today!</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Weight</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($contributions as $contrib): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($contrib['date'])) ?></td>
                                                <td><?= ucfirst(str_replace('_', ' ', $contrib['type'])) ?></td>
                                                <td><?= $contrib['weight'] ?> kg</td>
                                                <td>
                                                    <span class="badge bg-<?= $contrib['status'] === 'approved' ? 'success' : ($contrib['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($contrib['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
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