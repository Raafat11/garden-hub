<?php
$pageTitle = 'Join Shift - Volunteer';
require_once '../../core/Security.php';
require_once '../../classes/Volunteer.php';

Security::requireRole(['volunteer']);

$volunteer = new Volunteer($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle shift joining
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $shiftId = (int)$_POST['shift_id'];
        
        if($volunteer->joinShift($shiftId)) {
            $success = "Successfully joined the shift!";
        } else {
            $error = "Failed to join shift. It may be full or already started.";
        }
    }
}

// Get available shifts
$availableShifts = $db->query("SELECT s.*, COUNT(v.volunteerId) as volunteer_count 
                               FROM shifts s 
                               LEFT JOIN volunteers v ON s.shiftId = v.shiftId 
                               WHERE s.status = 'open' AND s.startTime > NOW() 
                               GROUP BY s.shiftId 
                               HAVING volunteer_count < s.maxVolunteers
                               ORDER BY s.startTime ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get user's shifts
$myShifts = $db->prepare("SELECT * FROM shifts WHERE volunteerId = ? AND startTime > NOW() ORDER BY startTime ASC");
$myShifts->execute([$_SESSION['userId']]);
$userShifts = $myShifts->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-calendar-plus"></i> Join Shift
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
                <!-- Available Shifts -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Available Shifts</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($availableShifts)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No shifts available right now</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($availableShifts as $shift): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= Security::escape($shift['shiftName']) ?></h6>
                                        <p class="mb-2"><?= Security::escape($shift['description']) ?></p>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?= date('M d, Y - H:i', strtotime($shift['startTime'])) ?> to <?= date('H:i', strtotime($shift['endTime'])) ?>
                                            </small>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-people"></i> <?= $shift['volunteer_count'] ?>/<?= $shift['maxVolunteers'] ?> volunteers
                                            </small>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                            <input type="hidden" name="shift_id" value="<?= $shift['shiftId'] ?>">
                                            <button type="submit" class="btn btn-sm btn-garden">
                                                <i class="bi bi-plus-circle"></i> Join Shift
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- My Shifts -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-calendar-check"></i> My Upcoming Shifts</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($userShifts)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-calendar-x"></i>
                                    <p class="mt-2">No shifts scheduled</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($userShifts as $shift): ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1"><?= Security::escape($shift['shiftName']) ?></h6>
                                        <p class="mb-1"><small><?= Security::escape($shift['description']) ?></small></p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?= date('M d, H:i', strtotime($shift['startTime'])) ?>
                                        </small>
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