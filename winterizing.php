<?php
$pageTitle = 'Seasonal Winterizing - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Get owner's plot
$lease = $db->prepare("SELECT plotId FROM leases WHERE userId = ? AND status = 'active' LIMIT 1");
$lease->execute([$_SESSION['userId']]);
$plotId = $lease->fetchColumn();

if(!$plotId) {
    $error = "You need an active plot lease.";
} else {
    // Handle winterizing checklist
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
            $error = "Invalid request.";
        } else {
            $tasks = $_POST['tasks'] ?? [];
            $notes = Security::sanitize($_POST['notes']);
            
            // Save winterizing record
            $stmt = $db->prepare("INSERT INTO winterizing_logs (plotId, userId, tasks, notes, date) 
                                  VALUES (?, ?, ?, ?, NOW())");
            $tasksJson = json_encode($tasks);
            if($stmt->execute([$plotId, $_SESSION['userId'], $tasksJson, $notes])) {
                $success = "Winterizing checklist saved successfully!";
            } else {
                $error = "Failed to save winterizing data.";
            }
        }
    }
}

// Get winterizing history
$history = $db->prepare("SELECT * FROM winterizing_logs WHERE plotId = ? ORDER BY date DESC LIMIT 5");
$history->execute([$plotId]);
$logs = $history->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-snow"></i> Seasonal Winterizing - Plot #<?= $plotId ?>
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
            
            <?php if($plotId): ?>
            <div class="row g-4">
                <!-- Winterizing Checklist -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-list-check"></i> Winter Preparation Checklist</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <h6 class="mb-3">Essential Tasks:</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="clear_debris" id="task1">
                                    <label class="form-check-label" for="task1">
                                        <strong>Clear Debris</strong> - Remove dead plants, weeds, and fallen leaves
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="mulch_beds" id="task2">
                                    <label class="form-check-label" for="task2">
                                        <strong>Mulch Garden Beds</strong> - Add 3-4 inches of mulch for insulation
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="drain_irrigation" id="task3">
                                    <label class="form-check-label" for="task3">
                                        <strong>Drain Irrigation System</strong> - Prevent pipe freezing
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="cover_plants" id="task4">
                                    <label class="form-check-label" for="task4">
                                        <strong>Cover Sensitive Plants</strong> - Use frost cloth or blankets
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="store_tools" id="task5">
                                    <label class="form-check-label" for="task5">
                                        <strong>Store Tools</strong> - Clean and store tools in dry place
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="soil_amendment" id="task6">
                                    <label class="form-check-label" for="task6">
                                        <strong>Add Soil Amendment</strong> - Add compost or organic matter
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="protect_trees" id="task7">
                                    <label class="form-check-label" for="task7">
                                        <strong>Protect Young Trees</strong> - Wrap trunks to prevent frost damage
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="harvest_final" id="task8">
                                    <label class="form-check-label" for="task8">
                                        <strong>Final Harvest</strong> - Harvest remaining vegetables before frost
                                    </label>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea name="notes" class="form-control" rows="3" 
                                              placeholder="Any additional winterizing notes..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-garden w-100">
                                    <i class="bi bi-check-circle"></i> Save Winterizing Checklist
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- History & Tips -->
                <div class="col-md-5">
                    <div class="card card-glass mb-3">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Winterizing History</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($logs)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mt-2">No winterizing records yet</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($logs as $log): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <small class="text-muted"><?= date('M d, Y', strtotime($log['date'])) ?></small>
                                                <p class="mb-0"><small><?= Security::escape($log['notes'] ?? 'No notes') ?></small></p>
                                            </div>
                                            <span class="badge bg-success">
                                                <?= count(json_decode($log['tasks'], true)) ?> tasks
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Winterizing Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Start winterizing 2-3 weeks before first frost</li>
                                <li>Water plants deeply before ground freezes</li>
                                <li>Label stored seeds and bulbs clearly</li>
                                <li>Check local frost dates for your area</li>
                                <li>Consider cold frames for extended harvest</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>