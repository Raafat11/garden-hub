<?php
$pageTitle = 'Genetic Lineage Logger - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';
require_once '../../classes/CommunitySeed.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle lineage logging
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $seedId = (int)$_POST['seed_id'];
        $geneticLineage = Security::sanitize($_POST['genetic_lineage']);
        
        $stmt = $db->prepare("UPDATE community_seeds SET geneticLineage = ? WHERE seedId = ?");
        if($stmt->execute([$geneticLineage, $seedId])) {
            $success = "Genetic lineage logged successfully!";
        } else {
            $error = "Failed to log genetic lineage.";
        }
    }
}

// Get seeds with lineage info
$seeds = $db->query("SELECT cs.*, u.name as contributor_name, sb.type as batch_type
                     FROM community_seeds cs
                     LEFT JOIN users u ON cs.contributorId = u.userId
                     LEFT JOIN seed_batches sb ON cs.batchId = sb.batchId
                     ORDER BY cs.seedId DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-diagram-3"></i> Genetic Lineage Logger
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
                    <h5 class="mb-0"><i class="bi bi-flower1"></i> Community Seeds - Lineage Tracking</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Seed ID</th>
                                <th>Type</th>
                                <th>Variety</th>
                                <th>Contributor</th>
                                <th>Batch</th>
                                <th>Genetic Lineage</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($seeds as $seed): ?>
                            <tr>
                                <td>#<?= $seed['seedId'] ?></td>
                                <td><?= Security::escape($seed['batch_type']) ?></td>
                                <td><?= Security::escape($seed['variety']) ?></td>
                                <td><?= Security::escape($seed['contributor_name'] ?? 'Unknown') ?></td>
                                <td>Batch #<?= $seed['batchId'] ?></td>
                                <td>
                                    <?php if($seed['geneticLineage']): ?>
                                        <span class="badge bg-success">Logged</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                            data-bs-target="#lineageModal<?= $seed['seedId'] ?>">
                                        <i class="bi bi-pencil"></i> Log Lineage
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Lineage Modal -->
                            <div class="modal fade" id="lineageModal<?= $seed['seedId'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Log Lineage: Seed #<?= $seed['seedId'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                <input type="hidden" name="seed_id" value="<?= $seed['seedId'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Genetic Lineage / Parentage</label>
                                                    <textarea name="genetic_lineage" class="form-control" rows="4" required 
                                                              placeholder="Enter genetic lineage, parent plants, origin, etc..."><?= Security::escape($seed['geneticLineage'] ?? '') ?></textarea>
                                                </div>
                                                
                                                <div class="alert alert-info">
                                                    <strong>Seed Info:</strong><br>
                                                    Type: <?= Security::escape($seed['batch_type']) ?><br>
                                                    Variety: <?= Security::escape($seed['variety']) ?><br>
                                                    Contributor: <?= Security::escape($seed['contributor_name'] ?? 'Unknown') ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-garden">Save Lineage</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>