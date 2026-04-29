<?php
$pageTitle = 'Validation - Warden';
require_once '../../core/Security.php';
require_once '../../classes/Warden.php';

Security::requireRole(['warden']);

$warden = new Warden($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle validation actions
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'validate_item') {
            $itemId = (int)$_POST['item_id'];
            $itemType = Security::sanitize($_POST['item_type']);
            $validationStatus = Security::sanitize($_POST['validation_status']);
            $notes = Security::sanitize($_POST['notes']);
            
            $table = $itemType === 'seed' ? 'seed_batches' : 'products';
            $idField = $itemType === 'seed' ? 'batchId' : 'productId';
            
            $stmt = $db->prepare("UPDATE $table SET validationStatus = ?, validationNotes = ?, validatedBy = ?, validatedAt = NOW() 
                                  WHERE $idField = ?");
            if($stmt->execute([$validationStatus, $notes, $_SESSION['userId'], $itemId])) {
                $success = ucfirst($itemType) . " validation completed!";
            } else {
                $error = "Failed to validate item.";
            }
        }
    }
}

// Get pending validations - seeds
$pendingSeeds = $db->query("SELECT sb.*, u.name as contributor_name, p.plantName 
                            FROM seed_batches sb 
                            JOIN users u ON sb.contributorId = u.userId 
                            JOIN plants p ON sb.plantId = p.plantId 
                            WHERE sb.validationStatus = 'pending' 
                            ORDER BY sb.createdAt DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get pending validations - products
$pendingProducts = $db->query("SELECT pr.*, u.name as grower_name, pl.plantName 
                               FROM products pr 
                               JOIN crop_records cr ON pr.cropId = cr.cropId 
                               JOIN plots p ON cr.plotId = p.plotId 
                               JOIN leases l ON p.plotId = l.plotId 
                               JOIN users u ON l.userId = u.userId 
                               JOIN plants pl ON cr.plantId = pl.plantId 
                               WHERE pr.validationStatus = 'pending' 
                               ORDER BY pr.createdAt DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-check2-square"></i> Validation Queue
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
                <!-- Seed Validations -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-flower1"></i> Pending Seed Validations</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($pendingSeeds)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-check-circle display-1"></i>
                                    <p class="mt-3">No seeds pending validation</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($pendingSeeds as $seed): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= Security::escape($seed['plantName']) ?></h6>
                                        <p class="mb-1"><small><strong>Contributor:</strong> <?= Security::escape($seed['contributor_name']) ?></small></p>
                                        <p class="mb-1"><small><strong>Type:</strong> <?= Security::escape($seed['type']) ?></small></p>
                                        <p class="mb-1"><small><strong>Quantity:</strong> <?= $seed['quantity'] ?> g</small></p>
                                        <p class="mb-2"><small><strong>Harvest Date:</strong> <?= date('M d, Y', strtotime($seed['harvestDate'])) ?></small></p>
                                        
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                            <input type="hidden" name="action" value="validate_item">
                                            <input type="hidden" name="item_type" value="seed">
                                            <input type="hidden" name="item_id" value="<?= $seed['batchId'] ?>">
                                            
                                            <div class="mb-2">
                                                <select name="validation_status" class="form-select form-select-sm" required>
                                                    <option value="">Select action...</option>
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <textarea name="notes" class="form-control form-control-sm" rows="2" 
                                                          placeholder="Validation notes..."></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-sm btn-garden w-100">
                                                <i class="bi bi-check-circle"></i> Submit Validation
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Product Validations -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-box-seam"></i> Pending Product Validations</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($pendingProducts)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-check-circle display-1"></i>
                                    <p class="mt-3">No products pending validation</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($pendingProducts as $product): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= Security::escape($product['plantName']) ?></h6>
                                        <p class="mb-1"><small><strong>Grower:</strong> <?= Security::escape($product['grower_name']) ?></small></p>
                                        <p class="mb-1"><small><strong>Yield:</strong> <?= $product['yieldWeight'] ?> kg</small></p>
                                        <p class="mb-2"><small><strong>Harvest Date:</strong> <?= date('M d, Y', strtotime($product['harvestDate'])) ?></small></p>
                                        
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                            <input type="hidden" name="action" value="validate_item">
                                            <input type="hidden" name="item_type" value="product">
                                            <input type="hidden" name="item_id" value="<?= $product['productId'] ?>">
                                            
                                            <div class="mb-2">
                                                <select name="validation_status" class="form-select form-select-sm" required>
                                                    <option value="">Select action...</option>
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <textarea name="notes" class="form-control form-control-sm" rows="2" 
                                                          placeholder="Validation notes..."></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-sm btn-garden w-100">
                                                <i class="bi bi-check-circle"></i> Submit Validation
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>