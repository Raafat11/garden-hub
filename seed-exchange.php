<?php
$pageTitle = 'Seed Exchange - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';
require_once '../../classes/CommunitySeed.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle seed exchange request
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'request_seed') {
            $seedId = (int)$_POST['seed_id'];
            $stmt = $db->prepare("INSERT INTO seed_exchange_requests (requesterId, seedId, status, requestDate) 
                                  VALUES (?, ?, 'pending', NOW())");
            if($stmt->execute([$_SESSION['userId'], $seedId])) {
                $success = "Seed exchange request sent successfully!";
            } else {
                $error = "Failed to send request. You may have already requested this seed.";
            }
        }
    }
}

// Get available seeds for exchange
$availableSeeds = $db->query("SELECT cs.*, u.name as contributor_name, sb.type as batch_type 
                              FROM community_seeds cs 
                              JOIN users u ON cs.contributorId = u.userId 
                              JOIN seed_batches sb ON cs.batchId = sb.batchId 
                              WHERE sb.viabilityValidated = 1 
                              ORDER BY cs.seedId DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get user's exchange requests
$myRequests = $db->prepare("SELECT ser.*, cs.variety, p.plantName 
                            FROM seed_exchange_requests ser 
                            JOIN community_seeds cs ON ser.seedId = cs.seedId 
                            JOIN seed_batches sb ON cs.batchId = sb.batchId
                            JOIN plants p ON sb.plantId = p.plantId
                            WHERE ser.requesterId = ? 
                            ORDER BY ser.requestDate DESC");
$myRequests->execute([$_SESSION['userId']]);
$requests = $myRequests->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-arrow-left-right"></i> Seed Exchange
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
                <!-- Available Seeds -->
                <div class="col-md-8">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-flower1"></i> Available Seeds for Exchange</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($availableSeeds)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No seeds available for exchange</p>
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach($availableSeeds as $seed): ?>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title"><?= Security::escape($seed['variety']) ?></h6>
                                                <p class="mb-1"><small class="text-muted">Type: <?= Security::escape($seed['batch_type']) ?></small></p>
                                                <p class="mb-2"><small class="text-muted">
                                                    <i class="bi bi-person"></i> From: <?= Security::escape($seed['contributor_name']) ?>
                                                </small></p>
                                                <?php if($seed['geneticLineage']): ?>
                                                    <p class="mb-2"><small><strong>Lineage:</strong> <?= Security::escape($seed['geneticLineage']) ?></small></p>
                                                <?php endif; ?>
                                                
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                    <input type="hidden" name="action" value="request_seed">
                                                    <input type="hidden" name="seed_id" value="<?= $seed['seedId'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                                        <i class="bi bi-hand-index"></i> Request Seed
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- My Requests -->
                <div class="col-md-4">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> My Requests</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($requests)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mt-2">No requests yet</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($requests as $req): ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1"><?= Security::escape($req['plantName']) ?></h6>
                                        <p class="mb-1"><small>Variety: <?= Security::escape($req['variety']) ?></small></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted"><?= date('M d, Y', strtotime($req['requestDate'])) ?></small>
                                            <span class="badge bg-<?= $req['status'] === 'approved' ? 'success' : ($req['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst($req['status']) ?>
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