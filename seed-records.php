<?php
$pageTitle = 'Seed Records - Warden';
require_once '../../core/Security.php';
require_once '../../classes/Warden.php';
require_once '../../classes/CommunitySeed.php';

Security::requireRole(['warden']);

$warden = new Warden($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle seed record creation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $plantId = (int)$_POST['plant_id'];
        $type = Security::sanitize($_POST['type']);
        $quantity = (float)$_POST['quantity'];
        $harvestDate = Security::sanitize($_POST['harvest_date']);
        $viabilityValidated = isset($_POST['viability_validated']) ? 1 : 0;
        $geneticLineage = Security::sanitize($_POST['genetic_lineage']);
        
        if($warden->createSeedRecord($plantId, $type, $quantity, $harvestDate, $viabilityValidated, $geneticLineage)) {
            $success = "Seed record created successfully!";
        } else {
            $error = "Failed to create seed record.";
        }
    }
}

// Get all seed records
$seedRecords = $db->query("SELECT cs.*, p.plantName, u.name as contributor_name 
                          FROM community_seeds cs 
                          JOIN seed_batches sb ON cs.batchId = sb.batchId 
                          JOIN plants p ON sb.plantId = p.plantId 
                          JOIN users u ON cs.contributorId = u.userId 
                          ORDER BY cs.seedId DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get all plants for dropdown
$plants = $db->query("SELECT plantId, plantName FROM plants ORDER BY plantName")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-flower1"></i> Created Seed Record
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
                <!-- Create Seed Record -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Seed Record</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Plant</label>
                                    <select name="plant_id" class="form-select" required>
                                        <option value="">Select plant...</option>
                                        <?php foreach($plants as $plant): ?>
                                            <option value="<?= $plant['plantId'] ?>"><?= Security::escape($plant['plantName']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Seed Type</label>
                                    <select name="type" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="open_pollinated">Open Pollinated</option>
                                        <option value="heirloom">Heirloom</option>
                                        <option value="hybrid">Hybrid</option>
                                        <option value="organic">Organic</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Quantity (grams)</label>
                                    <input type="number" name="quantity" class="form-control" step="0.1" min="0.1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Harvest Date</label>
                                    <input type="date" name="harvest_date" class="form-control" required max="<?= date('Y-m-d') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Genetic Lineage</label>
                                    <input type="text" name="genetic_lineage" class="form-control" 
                                           placeholder="e.g., Cherry Tomato - Sungold F1">
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" name="viability_validated" class="form-check-input" id="viabilityCheck">
                                    <label class="form-check-label" for="viabilityCheck">
                                        Viability Validated (Germination Tested)
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-garden w-100">
                                    <i class="bi bi-save"></i> Create Seed Record
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Seed Records List -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Seed Records</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($seedRecords)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No seed records yet</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                    <table class="table table-hover mb-0">
                                        <thead class="sticky-top bg-white">
                                            <tr>
                                                <th>ID</th>
                                                <th>Plant</th>
                                                <th>Type</th>
                                                <th>Qty</th>
                                                <th>Viability</th>
                                                <th>Contributor</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($seedRecords as $record): ?>
                                            <tr>
                                                <td>#<?= $record['seedId'] ?></td>
                                                <td>
                                                    <strong><?= Security::escape($record['plantName']) ?></strong>
                                                    <?php if($record['geneticLineage']): ?>
                                                        <br><small class="text-muted"><?= Security::escape($record['geneticLineage']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $record['type'])) ?></span></td>
                                                <td><?= $record['quantity'] ?>g</td>
                                                <td>
                                                    <?php if($record['viabilityValidated']): ?>
                                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Yes</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning"><i class="bi bi-x-circle"></i> No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= Security::escape($record['contributor_name']) ?></td>
                                                <td><?= date('M d, Y', strtotime($record['harvestDate'])) ?></td>
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