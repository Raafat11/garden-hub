<?php
$pageTitle = 'Lease Approval - Admin';
require_once '../../core/Security.php';
require_once '../../classes/Lease.php';
require_once '../../classes/Plot.php';

Security::requireRole(['admin']);

$db = Database::getInstance()->getConnection();
$success = null;

// Handle approval/rejection
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $leaseId = (int)$_POST['lease_id'];
        $action = $_POST['action'];
        
        $lease = new Lease();
        $lease->leaseId = $leaseId;
        
        if($action === 'approve') {
            $lease->status = 'active';
            $stmt = $db->prepare("UPDATE leases SET status = 'active' WHERE leaseId = ?");
            $stmt->execute([$leaseId]);
            $success = "Lease approved successfully!";
        } elseif($action === 'reject') {
            $stmt = $db->prepare("UPDATE leases SET status = 'rejected' WHERE leaseId = ?");
            $stmt->execute([$leaseId]);
            $success = "Lease rejected.";
        }
    }
}

// Get pending leases
$pendingLeases = $db->query("SELECT l.*, u.name as user_name, p.plotId, p.area 
                             FROM leases l 
                             JOIN users u ON l.userId = u.userId 
                             JOIN plots p ON l.plotId = p.plotId 
                             WHERE l.status = 'pending' 
                             ORDER BY l.startDate DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-file-earmark-check"></i> Lease Approval Workflow
            </h2>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= Security::escape($success) ?>
                </div>
            <?php endif; ?>
            
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> Pending Lease Requests</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($pendingLeases)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-inbox"></i> No pending lease requests
                        </div>
                    <?php else: ?>
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Plot</th>
                                    <th>Area</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Rent</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pendingLeases as $lease): ?>
                                <tr>
                                    <td><?= Security::escape($lease['user_name']) ?></td>
                                    <td>Plot #<?= $lease['plotId'] ?></td>
                                    <td><?= $lease['area'] ?> m²</td>
                                    <td><?= date('M d, Y', strtotime($lease['startDate'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($lease['endDate'])) ?></td>
                                    <td><?= $lease['rentAmount'] ?> EGP</td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                            <input type="hidden" name="lease_id" value="<?= $lease['leaseId'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                            <input type="hidden" name="lease_id" value="<?= $lease['leaseId'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-x"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
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