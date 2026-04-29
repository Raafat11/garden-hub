<?php
$pageTitle = 'Rent Plot - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';
require_once '../../classes/Plot.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle plot rental
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'rent_plot') {
            $plotId = (int)$_POST['plot_id'];
            $duration = (int)$_POST['duration'];
            
            // Check block availability
            $availability = $db->prepare("SELECT status FROM plots WHERE plotId = ?");
            $availability->execute([$plotId]);
            $status = $availability->fetchColumn();
            
            if($status !== 'available') {
                $error = "This plot is not available for rent.";
            } else {
                // Confirm payment - simplified for demo
                $paymentConfirmed = isset($_POST['payment_confirmed']) && $_POST['payment_confirmed'] === '1';
                
                if(!$paymentConfirmed) {
                    $error = "Payment must be confirmed to rent the plot.";
                } else {
                    // Create lease
                    $rentAmount = 100.00 * $duration; // $100 per month
                    $startDate = date('Y-m-d');
                    $endDate = date('Y-m-d', strtotime("+$duration months"));
                    
                    $db->beginTransaction();
                    try {
                        $stmt = $db->prepare("INSERT INTO leases (plotId, userId, startDate, endDate, rentAmount, status) 
                                              VALUES (?, ?, ?, ?, ?, 'active')");
                        $stmt->execute([$plotId, $_SESSION['userId'], $startDate, $endDate, $rentAmount]);
                        
                        $stmt = $db->prepare("UPDATE plots SET status = 'rented' WHERE plotId = ?");
                        $stmt->execute([$plotId]);
                        
                        $db->commit();
                        $success = "Plot rented successfully! Your lease is active until $endDate.";
                    } catch(Exception $e) {
                        $db->rollBack();
                        $error = "Failed to rent plot. Please try again.";
                    }
                }
            }
        }
    }
}

// Get available plots
$availablePlots = $db->query("SELECT * FROM plots WHERE status = 'available' ORDER BY plotId")->fetchAll(PDO::FETCH_ASSOC);

// Get user's current lease
$currentLease = $db->prepare("SELECT l.*, p.plotId, p.area, p.soilType FROM leases l 
                              JOIN plots p ON l.plotId = p.plotId 
                              WHERE l.userId = ? AND l.status = 'active'");
$currentLease->execute([$_SESSION['userId']]);
$lease = $currentLease->fetch(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-house-add"></i> Rent Plot <<include>> Check Block Availability <<extend>> Confirm Payment
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
            
            <?php if($lease): ?>
            <!-- Current Lease -->
            <div class="card card-glass mb-4" style="background:linear-gradient(135deg, #2D6A4F 0%, #1D3557 100%); color:white;">
                <div class="card-body">
                    <h5><i class="bi bi-check-circle"></i> Your Active Lease</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Plot ID:</strong> #<?= $lease['plotId'] ?></p>
                            <p class="mb-1"><strong>Area:</strong> <?= $lease['area'] ?> m²</p>
                            <p class="mb-0"><strong>Soil Type:</strong> <?= Security::escape($lease['soilType']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Start Date:</strong> <?= date('M d, Y', strtotime($lease['startDate'])) ?></p>
                            <p class="mb-1"><strong>End Date:</strong> <?= date('M d, Y', strtotime($lease['endDate'])) ?></p>
                            <p class="mb-0"><strong>Rent:</strong> $<?= number_format($lease['rentAmount'], 2) ?>/month</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            
            <!-- Available Plots -->
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0"><i class="bi bi-grid"></i> Available Plots - Check Block Availability</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($availablePlots)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-inbox display-1"></i>
                            <p class="mt-3">No plots available at the moment</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach($availablePlots as $plot): ?>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title">Plot #<?= $plot['plotId'] ?></h5>
                                            <span class="badge bg-success">Available</span>
                                        </div>
                                        
                                        <p class="mb-2"><strong>Area:</strong> <?= $plot['area'] ?> m²</p>
                                        <p class="mb-2"><strong>Soil Type:</strong> <?= Security::escape($plot['soilType']) ?></p>
                                        <p class="mb-2"><strong>Sunlight:</strong> <?= $plot['sunlightHours'] ?> hours/day</p>
                                        <p class="mb-3"><strong>Rent:</strong> $100.00/month</p>
                                        
                                        <button class="btn btn-garden w-100" data-bs-toggle="modal" 
                                                data-bs-target="#rentModal<?= $plot['plotId'] ?>">
                                            <i class="bi bi-cart"></i> Rent This Plot
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Rent Modal with Payment -->
                            <div class="modal fade" id="rentModal<?= $plot['plotId'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Rent Plot #<?= $plot['plotId'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                <input type="hidden" name="action" value="rent_plot">
                                                <input type="hidden" name="plot_id" value="<?= $plot['plotId'] ?>">
                                                
                                                <div class="alert alert-info">
                                                    <strong>Plot Details:</strong><br>
                                                    Area: <?= $plot['area'] ?> m²<br>
                                                    Soil: <?= Security::escape($plot['soilType']) ?><br>
                                                    Sunlight: <?= $plot['sunlightHours'] ?> hours/day
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Lease Duration</label>
                                                    <select name="duration" class="form-select" required id="duration_<?= $plot['plotId'] ?>">
                                                        <option value="1">1 Month - $100</option>
                                                        <option value="3">3 Months - $300</option>
                                                        <option value="6">6 Months - $600</option>
                                                        <option value="12">12 Months - $1,200</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="alert alert-warning">
                                                    <strong><i class="bi bi-credit-card"></i> Payment Required:</strong><br>
                                                    Total: $<span id="total_<?= $plot['plotId'] ?>">100</span>.00
                                                </div>
                                                
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="payment_confirmed" 
                                                           value="1" id="payment_<?= $plot['plotId'] ?>" required>
                                                    <label class="form-check-label" for="payment_<?= $plot['plotId'] ?>">
                                                        I confirm payment has been processed
                                                    </label>
                                                </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-garden">Confirm & Rent</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Update total price on duration change
<?php foreach($availablePlots as $plot): ?>
document.getElementById('duration_<?= $plot['plotId'] ?>')?.addEventListener('change', function() {
    document.getElementById('total_<?= $plot['plotId'] ?>').textContent = this.value * 100;
});
<?php endforeach; ?>
</script>

<?php include '../../templates/footer.php'; ?>