<?php
$pageTitle = 'Plot Details - Garden Hub';
require_once '../core/Security.php';
require_once '../classes/Plot.php';
require_once '../classes/Soil.php';

Security::requireLogin();

$plotId = $_GET['id'] ?? null;
if(!$plotId) {
    header("Location: index.php");
    exit();
}

$plot = new Plot($plotId);
if(!$plot->plotId) {
    header("Location: index.php?error=not_found");
    exit();
}

// Get soil data
$db = Database::getInstance()->getConnection();
$soilStmt = $db->prepare("SELECT * FROM soil WHERE plotId = ?");
$soilStmt->execute([$plotId]);
$soil = $soilStmt->fetch(PDO::FETCH_ASSOC);

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="row">
        <?php include '../templates/sidebar.php'; ?>
        
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color:#1D3557;">
                    <i class="bi bi-grid-3x3"></i> Plot #<?= $plot->plotId ?>
                </h2>
                <span class="badge bg-<?= $plot->status === 'available' ? 'success' : 'warning' ?> fs-6">
                    <?= ucfirst($plot->status) ?>
                </span>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card card-glass h-100">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Plot Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Area:</strong></td>
                                    <td><?= $plot->area ?> m²</td>
                                </tr>
                                <tr>
                                    <td><strong>Soil Type:</strong></td>
                                    <td><?= Security::escape($plot->soilType) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Sunlight Hours:</strong></td>
                                    <td><?= $plot->sunlightHours ?> hours/day</td>
                                </tr>
                                <tr>
                                    <td><strong>Estimated Rent:</strong></td>
                                    <td class="text-success fw-bold"><?= $plot->calculateRent() ?> EGP/month</td>
                                </tr>
                            </table>
                            
                            <?php if($plot->status === 'available' && in_array($_SESSION['role'], ['plot_owner', 'user'])): ?>
                                <a href="/garden-hub/public/owner/rent-plot.php?plot=<?= $plot->plotId ?>" 
                                   class="btn btn-garden w-100">
                                    <i class="bi bi-house-add"></i> Rent This Plot
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card card-glass h-100">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-droplet"></i> Soil Data</h5>
                        </div>
                        <div class="card-body">
                            <?php if($soil): ?>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>pH Level:</strong></td>
                                        <td><?= $soil['pH'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Current Crop:</strong></td>
                                        <td><?= Security::escape($soil['cropType'] ?? 'None') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fertilizer History:</strong></td>
                                        <td><?= Security::escape($soil['fertilizerHistory'] ?? 'N/A') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td><?= date('M d, Y', strtotime($soil['updated_at'])) ?></td>
                                    </tr>
                                </table>
                            <?php else: ?>
                                <p class="text-muted text-center">No soil data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>