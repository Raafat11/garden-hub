<?php
$pageTitle = 'Trade Details - Garden Hub';
require_once '../core/Security.php';
require_once '../config/Database.php';

Security::requireLogin();

$tradeId = $_GET['id'] ?? null;
if(!$tradeId) {
    header("Location: index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT t.*, u1.name as initiator_name, u2.name as receiver_name, 
                      p1.name as offered_product, p2.name as requested_product 
                      FROM trades t 
                      JOIN users u1 ON t.initiatorId = u1.userId 
                      LEFT JOIN users u2 ON t.receiverId = u2.userId
                      JOIN products p1 ON t.offeredProductId = p1.productId
                      JOIN products p2 ON t.requestedProductId = p2.productId
                      WHERE t.tradeId = ?");
$stmt->execute([$tradeId]);
$trade = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$trade) {
    header("Location: index.php?error=not_found");
    exit();
}

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="row">
        <?php include '../templates/sidebar.php'; ?>
        
        <div class="col-md-10">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-arrow-left-right"></i> Trade Details
            </h2>
            
            <div class="card card-glass">
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-success">
                                <i class="bi bi-box-arrow-up"></i> Offering
                            </h5>
                            <p class="fs-5"><?= Security::escape($trade['offered_product']) ?></p>
                            <small class="text-muted">By: <?= Security::escape($trade['initiator_name']) ?></small>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="text-primary">
                                <i class="bi bi-box-arrow-down"></i> Requesting
                            </h5>
                            <p class="fs-5"><?= Security::escape($trade['requested_product']) ?></p>
                            <small class="text-muted">From: <?= Security::escape($trade['receiver_name'] ?? 'Anyone') ?></small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Type:</strong> <?= ucfirst($trade['type']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $trade['status'] === 'completed' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($trade['status']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Date:</strong> <?= date('M d, Y', strtotime($trade['date'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>