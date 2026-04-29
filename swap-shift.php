<?php
$pageTitle = 'Shift Substitution - Volunteer';
require_once '../../core/Security.php';
require_once '../../classes/Volunteer.php';

Security::requireRole(['volunteer']);

$volunteer = new Volunteer($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle shift swap
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'request_swap') {
            $myShiftId = (int)$_POST['my_shift_id'];
            $requestedShiftId = (int)$_POST['requested_shift_id'];
            
            // Calculate delay between shifts
            $stmt = $db->prepare("SELECT startTime FROM shifts WHERE shiftId = ?");
            $stmt->execute([$myShiftId]);
            $myStartTime = strtotime($stmt->fetchColumn());
            
            $stmt->execute([$requestedShiftId]);
            $requestedStartTime = strtotime($stmt->fetchColumn());
            
            $delayHours = abs(($requestedStartTime - $myStartTime) / 3600);
            
            $stmt = $db->prepare("INSERT INTO shift_swaps (requesterId, originalShiftId, requestedShiftId, delayHours, status) 
                                  VALUES (?, ?, ?, ?, 'pending')");
            if($stmt->execute([$_SESSION['userId'], $myShiftId, $requestedShiftId, $delayHours])) {
                $success = "Swap request sent! Delay calculated: " . round($delayHours, 1) . " hours.";
            } else {
                $error = "Failed to send swap request.";
            }
        }
    }
}

// Get user's shifts
$myShifts = $db->prepare("SELECT * FROM shifts WHERE volunteerId = ? AND startTime > NOW() AND status = 'approved'");
$myShifts->execute([$_SESSION['userId']]);
$userShifts = $myShifts->fetchAll(PDO::FETCH_ASSOC);

// Get other available shifts
$otherShifts = $db->query("SELECT s.*, u.name as volunteer_name FROM shifts s 
                           LEFT JOIN volunteers v ON s.volunteerId = v.volunteerId 
                           LEFT JOIN users u ON v.userId = u.userId 
                           WHERE s.volunteerId != {$_SESSION['userId']} AND s.startTime > NOW() AND s.status = 'approved'")->fetchAll(PDO::FETCH_ASSOC);

// Get swap requests
$swapRequests = $db->prepare("SELECT ss.*, s1.shiftName as my_shift, s2.shiftName as their_shift, u.name as requester_name
                              FROM shift_swaps ss 
                              JOIN shifts s1 ON ss.originalShiftId = s1.shiftId 
                              JOIN shifts s2 ON ss.requestedShiftId = s2.shiftId 
                              JOIN users u ON ss.requesterId = u.userId 
                              WHERE s2.volunteerId = ? AND ss.status = 'pending'");
$swapRequests->execute([$_SESSION['userId']]);
$requests = $swapRequests->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-arrow-left-right"></i> Shift Substitution <<extend>> Calculate Delay
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
                <!-- Request Swap -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Request Shift Swap</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <input type="hidden" name="action" value="request_swap">
                                
                                <div class="mb-3">
                                    <label class="form-label">Your Shift</label>
                                    <select name="my_shift_id" class="form-select" required>
                                        <option value="">Select your shift...</option>
                                        <?php foreach($userShifts as $shift): ?>
                                            <option value="<?= $shift['shiftId'] ?>">
                                                <?= Security::escape($shift['shiftName']) ?> - <?= date('M d, H:i', strtotime($shift['startTime'])) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Request to Swap With</label>
                                    <select name="requested_shift_id" class="form-select" required>
                                        <option value="">Select shift to swap...</option>
                                        <?php foreach($otherShifts as $shift): ?>
                                            <option value="<?= $shift['shiftId'] ?>">
                                                <?= Security::escape($shift['shiftName']) ?> - <?= Security::escape($shift['volunteer_name']) ?> 
                                                (<?= date('M d, H:i', strtotime($shift['startTime'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Delay between shifts will be calculated automatically
                                </div>
                                
                                <button type="submit" class="btn btn-garden w-100">
                                    <i class="bi bi-arrow-left-right"></i> Request Swap
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Swap Requests -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-inbox"></i> Swap Requests Received</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($requests)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mt-2">No swap requests</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($requests as $req): ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1"><?= Security::escape($req['requester_name']) ?></h6>
                                        <p class="mb-1">
                                            <small>Wants to swap: <strong><?= Security::escape($req['my_shift']) ?></strong></small><br>
                                            <small>For: <strong><?= Security::escape($req['their_shift']) ?></strong></small>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-warning">
                                                <i class="bi bi-clock"></i> Delay: <?= round($req['delayHours'], 1) ?>h
                                            </span>
                                            <div>
                                                <button class="btn btn-sm btn-success">Accept</button>
                                                <button class="btn btn-sm btn-danger">Decline</button>
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