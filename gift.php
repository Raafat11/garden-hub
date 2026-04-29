<?php
$pageTitle = 'Gift Economy - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle gift creation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $productId = (int)$_POST['product_id'];
        $quantity = (float)$_POST['quantity'];
        $message = Security::sanitize($_POST['message']);
        
        $stmt = $db->prepare("INSERT INTO gifts (giverId, productId, quantity, message, status) 
                              VALUES (?, ?, ?, ?, 'available')");
        if($stmt->execute([$_SESSION['userId'], $productId, $quantity, $message])) {
            $success = "Gift posted to the community successfully!";
        } else {
            $error = "Failed to post gift.";
        }
    }
}

// Get owner's products
$products = $db->prepare("SELECT p.*, cr.plantName FROM products p 
                          JOIN crop_records cr ON p.cropId = cr.cropId 
                          JOIN plots pl ON cr.plotId = pl.plotId
                          JOIN leases l ON pl.plotId = l.plotId 
                          WHERE l.userId = ? AND l.status = 'active' AND p.qualityVerified = 1");
$products->execute([$_SESSION['userId']]);
$availableProducts = $products->fetchAll(PDO::FETCH_ASSOC);

// Get available gifts
$gifts = $db->query("SELECT g.*, u.name as giver_name, p.plantName 
                     FROM gifts g 
                     JOIN products pr ON g.productId = pr.productId 
                     JOIN crop_records cr ON pr.cropId = cr.cropId
                     JOIN plants p ON cr.plantId = p.plantId
                     JOIN users u ON g.giverId = u.userId 
                     WHERE g.status = 'available' 
                     ORDER BY g.createdAt DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-gift"></i> Gift Economy
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
                <!-- Share a Gift -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-gift-fill"></i> Share Your Harvest</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Select Product</label>
                                    <select name="product_id" class="form-select" required>
                                        <option value="">Choose product...</option>
                                        <?php foreach($availableProducts as $product): ?>
                                            <option value="<?= $product['productId'] ?>">
                                                <?= Security::escape($product['plantName']) ?> - <?= $product['yieldWeight'] ?> kg
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Quantity to Gift (kg)</label>
                                    <input type="number" name="quantity" class="form-control" step="0.1" min="0.1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Message (Optional)</label>
                                    <textarea name="message" class="form-control" rows="3" 
                                              placeholder="Share your harvest story..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-gift"></i> Share Gift
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Available Gifts -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-heart"></i> Community Gifts</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($gifts)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No gifts available right now</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($gifts as $gift): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <i class="bi bi-gift text-warning"></i>
                                                    <?= Security::escape($gift['plantName']) ?>
                                                </h6>
                                                <p class="mb-1"><strong><?= $gift['quantity'] ?> kg</strong> available</p>
                                                <?php if($gift['message']): ?>
                                                    <p class="mb-1 text-muted"><em>"<?= Security::escape($gift['message']) ?>"</em></p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> From: <?= Security::escape($gift['giver_name']) ?> | 
                                                    <i class="bi bi-clock"></i> <?= date('M d, Y', strtotime($gift['createdAt'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
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