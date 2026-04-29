<?php
$pageTitle = 'Produce Flash Trade - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle flash trade creation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $productId = (int)$_POST['product_id'];
        $quantity = (float)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $expiresAt = Security::sanitize($_POST['expires_at']);
        
        $stmt = $db->prepare("INSERT INTO flash_trades (sellerId, productId, quantity, price, expiresAt, status) 
                              VALUES (?, ?, ?, ?, ?, 'active')");
        if($stmt->execute([$_SESSION['userId'], $productId, $quantity, $price, $expiresAt])) {
            $success = "Flash trade created successfully! Expires at " . date('M d, H:i', strtotime($expiresAt));
        } else {
            $error = "Failed to create flash trade.";
        }
    }
}

// Get owner's available products
$products = $db->prepare("SELECT p.*, cr.plantName FROM products p 
                          JOIN crop_records cr ON p.cropId = cr.cropId 
                          JOIN plots pl ON cr.plotId = pl.plotId
                          JOIN leases l ON pl.plotId = l.plotId 
                          WHERE l.userId = ? AND l.status = 'active' AND p.qualityVerified = 1");
$products->execute([$_SESSION['userId']]);
$availableProducts = $products->fetchAll(PDO::FETCH_ASSOC);

// Get active flash trades
$activeTrades = $db->prepare("SELECT ft.*, p.plantName, u.name as seller_name 
                              FROM flash_trades ft 
                              JOIN products pr ON ft.productId = pr.productId 
                              JOIN crop_records cr ON pr.cropId = cr.cropId
                              JOIN plants p ON cr.plantId = p.plantId
                              JOIN users u ON ft.sellerId = u.userId 
                              WHERE ft.status = 'active' AND ft.expiresAt > NOW() 
                              ORDER BY ft.expiresAt ASC");
$activeTrades->execute();
$trades = $activeTrades->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-lightning"></i> Produce Flash Trade
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
                <!-- Create Flash Trade -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--danger); color:white;">
                            <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Create Flash Trade</h5>
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
                                    <label class="form-label">Quantity (kg)</label>
                                    <input type="number" name="quantity" class="form-control" step="0.1" min="0.1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Price ($)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" min="0.01" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Expires At</label>
                                    <input type="datetime-local" name="expires_at" class="form-control" required 
                                           min="<?= date('Y-m-d\TH:i') ?>">
                                    <small class="text-muted">Flash trades are time-limited offers</small>
                                </div>
                                
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-lightning"></i> Create Flash Trade
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Active Flash Trades -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock"></i> Active Flash Trades</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($trades)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No active flash trades</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($trades as $trade): 
                                        $timeLeft = strtotime($trade['expiresAt']) - time();
                                        $hoursLeft = floor($timeLeft / 3600);
                                        $minutesLeft = floor(($timeLeft % 3600) / 60);
                                    ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= Security::escape($trade['plantName']) ?></h6>
                                                <p class="mb-1">
                                                    <strong><?= $trade['quantity'] ?> kg</strong> @ 
                                                    <strong class="text-success">$<?= number_format($trade['price'], 2) ?></strong>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?= Security::escape($trade['seller_name']) ?> | 
                                                    <i class="bi bi-clock"></i> Expires in: <?= $hoursLeft ?>h <?= $minutesLeft ?>m
                                                </small>
                                            </div>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-lightning-fill"></i> FLASH
                                            </span>
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