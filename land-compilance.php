<?php
$pageTitle = 'Land Use Compliance - Manager';
require_once '../../core/Security.php';
require_once '../../classes/GardenManager.php';

Security::requireRole(['garden_manager']);

$manager = new GardenManager($_SESSION['userId']);
$db = Database::getInstance()->getConnection();

// Generate compliance report
$complianceData = $db->query("SELECT 
    p.plotId, p.area, p.status, p.soilType,
    u.name as owner_name,
    l.startDate, l.endDate, l.rentAmount,
    COUNT(DISTINCT cr.cropId) as crop_count,
    SUM(CASE WHEN cr.status = 'active' THEN 1 ELSE 0 END) as active_crops
    FROM plots p
    LEFT JOIN leases l ON p.plotId = l.plotId AND l.status = 'active'
    LEFT JOIN users u ON l.userId = u.userId
    LEFT JOIN crop_records cr ON p.plotId = cr.plotId
    GROUP BY p.plotId
    ORDER BY p.plotId")->fetchAll(PDO::FETCH_ASSOC);

// Calculate compliance stats
$totalPlots = count($complianceData);
$activePlots = 0;
$inactivePlots = 0;
$nonCompliantPlots = 0;

foreach($complianceData as $plot) {
    if($plot['status'] === 'rented') $activePlots++;
    if($plot['status'] === 'inactive') $inactivePlots++;
    if($plot['status'] === 'rented' && $plot['active_crops'] == 0) $nonCompliantPlots++;
}

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color:#1D3557;">
                    <i class="bi bi-clipboard-check"></i> Land Use Compliance Report
                </h2>
                <button class="btn btn-garden" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
            
            <!-- Compliance Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-grid-3x3 display-6" style="color:#2D6A4F;"></i>
                            <h3 class="mt-2"><?= $totalPlots ?></h3>
                            <p class="text-muted mb-0">Total Plots</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-check-circle display-6" style="color:#2D6A4F;"></i>
                            <h3 class="mt-2"><?= $activePlots ?></h3>
                            <p class="text-muted mb-0">Active/Rented</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-x-circle display-6" style="color:#FFB703;"></i>
                            <h3 class="mt-2"><?= $inactivePlots ?></h3>
                            <p class="text-muted mb-0">Inactive</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-exclamation-triangle display-6" style="color:#D62828;"></i>
                            <h3 class="mt-2"><?= $nonCompliantPlots ?></h3>
                            <p class="text-muted mb-0">Non-Compliant</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Compliance Table -->
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Plot Compliance Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Plot ID</th>
                                <th>Owner</th>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Lease End</th>
                                <th>Active Crops</th>
                                <th>Compliance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($complianceData as $plot): 
                                $isCompliant = ($plot['status'] === 'rented' && $plot['active_crops'] > 0) || $plot['status'] === 'available';
                            ?>
                            <tr class="<?= !$isCompliant ? 'table-warning' : '' ?>">
                                <td>Plot #<?= $plot['plotId'] ?></td>
                                <td><?= Security::escape($plot['owner_name'] ?? 'Unassigned') ?></td>
                                <td><?= $plot['area'] ?> m²</td>
                                <td>
                                    <span class="badge bg-<?= $plot['status'] === 'rented' ? 'success' : ($plot['status'] === 'available' ? 'info' : 'secondary') ?>">
                                        <?= ucfirst($plot['status']) ?>
                                    </span>
                                </td>
                                <td><?= $plot['endDate'] ? date('M d, Y', strtotime($plot['endDate'])) : 'N/A' ?></td>
                                <td><?= $plot['active_crops'] ?> / <?= $plot['crop_count'] ?></td>
                                <td>
                                    <?php if($isCompliant): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i> Compliant</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x"></i> Non-Compliant</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> <strong>Compliance Rule:</strong> Rented plots must have at least 1 active crop. Non-compliant plots are highlighted in yellow.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>