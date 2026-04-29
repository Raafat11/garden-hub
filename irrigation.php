<?php
$pageTitle = 'Automated Irrigation - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';
require_once '../../classes/Irrigation.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Get owner's plot
$lease = $db->prepare("SELECT plotId FROM leases WHERE userId = ? AND status = 'active' LIMIT 1");
$lease->execute([$_SESSION['userId']]);
$plotId = $lease->fetchColumn();

if(!$plotId) {
    $error = "You need an active plot lease to use irrigation.";
} else {
    // Handle irrigation control
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
            $error = "Invalid request.";
        } else {
            $action = $_POST['action'];
            $irrigation = new Irrigation();
            $irrigation->plotId = $plotId;
            
            if($action === 'start') {
                $duration = (int)$_POST['duration'];
                if($irrigation->start($duration)) {
                    $success = "Irrigation started for $duration minutes!";
                } else {
                    $error = "Failed to start irrigation.";
                }
            } elseif($action === 'stop') {
                if($irrigation->stop()) {
                    $success = "Irrigation stopped successfully!";
                } else {
                    $error = "Failed to stop irrigation.";
                }
            } elseif($action === 'schedule') {
                $scheduleTime = Security::sanitize($_POST['schedule_time']);
                $duration = (int)$_POST['schedule_duration'];
                $stmt = $db->prepare("INSERT INTO irrigation_schedules (plotId, scheduledTime, duration) VALUES (?, ?, ?)");
                if($stmt->execute([$plotId, $scheduleTime, $duration])) {
                    $success = "Irrigation scheduled successfully!";
                } else {
                    $error = "Failed to schedule irrigation.";
                }
            }
        }
    }
}

// Get current irrigation status
$currentStatus = $db->prepare("SELECT * FROM irrigation_systems WHERE plotId = ? LIMIT 1");
$currentStatus->execute([$plotId]);
$system = $currentStatus->fetch(PDO::FETCH_ASSOC);

// Get irrigation history
$history = $db->prepare("SELECT * FROM irrigation_logs WHERE plotId = ? ORDER BY startTime DESC LIMIT 10");
$history->execute([$plotId]);
$logs = $history->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-droplet"></i> Automated Irrigation - Plot #<?= $plotId ?>
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
            
            <?php if($plotId): ?>
            <div class="row g-4">
                <!-- Control Panel -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-sliders"></i> Irrigation Control</h5>
                        </div>
                        <div class="card-body">
                            <!-- Current Status -->
                            <div class="alert alert-<?= $system && $system['status'] === 'on' ? 'success' : 'secondary' ?>">
                                <h6 class="mb-1">Current Status</h6>
                                <h4 class="mb-0">
                                    <i class="bi bi-<?= $system && $system['status'] === 'on' ? 'droplet-fill' : 'droplet' ?>"></i>
                                    <?= $system && $system['status'] === 'on' ? 'ON - Running' : 'OFF' ?>
                                </h4>
                                <?php if($system && $system['status'] === 'on'): ?>
                                    <small>Started: <?= date('H:i', strtotime($system['lastStartTime'])) ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Manual Control -->
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <?php if(!$system || $system['status'] === 'off'): ?>
                                    <input type="hidden" name="action" value="start">
                                    <label class="form-label">Duration (minutes)</label>
                                    <div class="input-group mb-2">
                                        <input type="number" name="duration" class="form-control" value="30" min="5" max="120" required>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-play-fill"></i> Start Irrigation
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="stop">
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="bi bi-stop-fill"></i> Stop Irrigation
                                    </button>
                                <?php endif; ?>
                            </form>
                            
                            <!-- Schedule Irrigation -->
                            <hr>
                            <h6>Schedule Automatic Irrigation</h6>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <input type="hidden" name="action" value="schedule">
                                
                                <div class="mb-2">
                                    <label class="form-label">Time</label>
                                    <input type="time" name="schedule_time" class="form-control" required>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" name="schedule_duration" class="form-control" value="30" min="5" max="120" required>
                                </div>
                                
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-clock"></i> Schedule
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- History -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Irrigation History</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($logs)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No irrigation history yet</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Duration</th>
                                                <th>Water Used</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($logs as $log): ?>
                                            <tr>
                                                <td><?= date('M d, H:i', strtotime($log['startTime'])) ?></td>
                                                <td><?= $log['duration'] ?> min</td>
                                                <td><?= $log['waterUsed'] ?> L</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>