<?php
$pageTitle = 'Produce Quality Verification - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';
require_once '../../classes/Product.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle quality verification
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $productId = (int)$_POST['product_id'];
        $action = $_POST['action'];
        
        if($action === 'verify') {
            $qualityScore = (float)$_POST['quality_score'];
            if($manager->verifyProductQuality($productId)) {
                $stmt = $db->prepare("UPDATE products SET qualityVerified = 1, qualityScore = ? WHERE productId = ?");
                $stmt->execute([$qualityScore, $productId]);
                $success = "Product quality verified successfully!";
            } else {
                $error = "Failed to verify product quality.";
            }
        }
    }
}

// Get products pending verification
$products = $db->query("SELECT p.*, u.name as owner_name, cr.plantName, pl.plotId
                        FROM products p
                        JOIN crop_records cr ON p.cropId = cr.cropId
                        JOIN plots pl ON cr.plotId = pl.plotId
                        LEFT JOIN leases l ON pl.plotId = l.plotId AND l.status = 'active'
                        LEFT JOIN users u ON l.userId = u.userId
                        WHERE p.qualityVerified = 0
                        ORDER BY p.harvestDate DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-award"></i> Produce Quality Verification
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
                    <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Products Pending Quality Check</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($products)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-check-circle display-1"></i>
                            <p class="mt-3">All products have been verified!</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Plant Name</th>
                                    <th>Plot</th>
                                    <th>Owner</th>
                                    <th>Harvest Date</th>
                                    <th>Yield (kg)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $product): ?>
                                <tr>
                                    <td>#<?= $product['productId'] ?></td>
                                    <td><?= Security::escape($product['plantName']) ?></td>
                                    <td>Plot #<?= $product['plotId'] ?></td>
                                    <td><?= Security::escape($product['owner_name'] ?? 'Unknown') ?></td>
                                    <td><?= date('M d, Y', strtotime($product['harvestDate'])) ?></td>
                                    <td><?= $product['yieldWeight'] ?> kg</td>
                                    <td>
                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                                data-bs-target="#verifyModal<?= $product['productId'] ?>">
                                            <i class="bi bi-check-circle"></i> Verify Quality
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Quality Verification Modal -->
                                <div class="modal fade" id="verifyModal<?= $product['productId'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Verify: <?= Security::escape($product['plantName']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                    <input type="hidden" name="product_id" value="<?= $product['productId'] ?>">
                                                    <input type="hidden" name="action" value="verify">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Quality Score (0-100)</label>
                                                        <input type="number" name="quality_score" class="form-control" 
                                                               min="0" max="100" step="0.1" required 
                                                               placeholder="Enter quality score...">
                                                        <small class="text-muted">
                                                            90-100: Excellent | 70-89: Good | 50-69: Fair | Below 50: Poor
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="alert alert-info">
                                                        <strong>Product Details:</strong><br>
                                                        Plot: #<?= $product['plotId'] ?><br>
                                                        Yield: <?= $product['yieldWeight'] ?> kg<br>
                                                        Harvest Date: <?= date('M d, Y', strtotime($product['harvestDate'])) ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Verify Quality</button>
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