<?php
$pageTitle = 'Seed Viability Validation - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';
require_once '../../classes/SeedBatch.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle seed validation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $batchId = (int)$_POST['batch_id'];
        $action = $_POST['action'];
        
        $seedBatch = new SeedBatch();
        $seedBatch->batchId = $batchId;
        
        if($action === 'validate') {
            if($manager->validateSeedViability($batchId)) {
                $success = "Seed batch validated successfully!";
            } else {
                $error = "Failed to validate seed batch.";
            }
        }
    }
}

// Get seed batches with their status
$seedBatches = $db->query("SELECT sb.*, u.name as donor_name, COUNT(cs.seedId) as seed_count
                           FROM seed_batches sb
                           LEFT JOIN users u ON sb.donorId = u.userId
                           LEFT JOIN community_seeds cs ON sb.batchId = cs.batchId
                           GROUP BY sb.batchId
                           ORDER BY sb.batchDate DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-flower1"></i> Seed Viability Validation
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
                    <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Seed Batches - Viability Check</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Seed Type</th>
                                <th>Variety</th>
                                <th>Donor</th>
                                <th>Batch Date</th>
                                <th>Seeds Count</th>
                                <th>Validated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($seedBatches as $batch): ?>
                            <tr>
                                <td>#<?= $batch['batchId'] ?></td>
                                <td><?= Security::escape($batch['type']) ?></td>
                                <td><?= Security::escape($batch['variety']) ?></td>
                                <td><?= Security::escape($batch['donor_name'] ?? 'Unknown') ?></td>
                                <td><?= date('M d, Y', strtotime($batch['batchDate'])) ?></td>
                                <td><?= $batch['seed_count'] ?></td>
                                <td>
                                    <?php if($batch['viabilityValidated']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i> Validated</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="bi bi-hourglass"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!$batch['viabilityValidated']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                        <input type="hidden" name="batch_id" value="<?= $batch['batchId'] ?>">
                                        <input type="hidden" name="action" value="validate">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle"></i> Validate
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> <strong>Validation Process:</strong> Check germination rate, seed quality, and storage conditions before marking as validated.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>