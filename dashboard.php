<?php
$pageTitle = 'Warden Dashboard - Garden Hub';
require_once '../../core/Security.php';
require_once '../../classes/Warden.php';
require_once '../../config/Database.php';

Security::requireRole(['warden']);

$warden = new Warden($_SESSION['userId']);

$stats = $warden->getDashboardStats(); 
$recentIncidents = $warden->getRecentIncidents(5);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color:#1D3557;">
                    <i class="bi bi-shield-check"></i> Warden Dashboard
                </h2>
                <span class="text-muted">Welcome, <?= Security::escape($warden->getName()) ?></span>
            </div>
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-file-earmark-text display-6" style="color:#D62828;"></i>
                            <h3 class="mt-2"><?= $stats['pending_reports'] ?></h3>
                            <p class="text-muted mb-0">Pending Reports</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-flower1 display-6" style="color:#2D6A4F;"></i>
                            <h3 class="mt-2"><?= $stats['seed_records'] ?></h3>
                            <p class="text-muted mb-0">Seed Records</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-exclamation-triangle display-6" style="color:#FFB703;"></i>
                            <h3 class="mt-2"><?= $stats['security_alerts'] ?></h3>
                            <p class="text-muted mb-0">Security Alerts</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-tools display-6" style="color:#D62828;"></i>
                            <h3 class="mt-2"><?= $stats['tool_damages'] ?></h3>
                            <p class="text-muted mb-0">Tool Damages</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Quick Actions -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="create-report.php" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-file-plus"></i> Create Report
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="security.php" class="btn btn-outline-warning w-100">
                                        <i class="bi bi-shield"></i> Security
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="seed-records.php" class="btn btn-outline-success w-100">
                                        <i class="bi bi-flower1"></i> Seed Records
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="tool-damage.php" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-tools"></i> Tool Damage
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Incidents -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Incidents</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($recentIncidents)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-check-circle"></i>
                                    <p class="mt-2">No recent incidents</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($recentIncidents as $incident): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?= Security::escape($incident['title']) ?></h6>
                                            <span class="badge bg-<?= $incident['severity'] === 'high' ? 'danger' : ($incident['severity'] === 'medium' ? 'warning' : 'info') ?>">
                                                <?= ucfirst($incident['severity']) ?>
                                            </span>
                                        </div>
                                        <p class="mb-1"><small><?= Security::escape(substr($incident['description'], 0, 80)) ?>...</small></p>
                                        <small class="text-muted"><?= date('M d, Y H:i', strtotime($incident['createdAt'])) ?></small>
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