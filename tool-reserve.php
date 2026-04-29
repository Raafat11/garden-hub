<?php
$pageTitle = 'Reserve Tool - Garden Hub';
require_once '../core/Security.php';
require_once '../classes/Tool.php';
require_once '../classes/Reservation.php';

Security::requireLogin();

$toolId = $_GET['tool'] ?? null;
$success = null;
$error = null;

if(!$toolId) {
    header("Location: index.php");
    exit();
}

$tool = new Tool($toolId);
if(!$tool->toolId) {
    header("Location: index.php?error=not_found");
    exit();
}

// Handle reservation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $startTime = Security::sanitize($_POST['start_time']);
        $endTime = Security::sanitize($_POST['end_time']);
        
        $reservation = new Reservation();
        if($reservation->reserveTool($_SESSION['userId'], $toolId, $startTime, $endTime)) {
            $success = "Tool reserved successfully!";
        } else {
            $error = "Tool is not available or reservation failed.";
        }
    }
}

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="row">
        <?php include '../templates/sidebar.php'; ?>
        
        <div class="col-md-10">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-tools"></i> Reserve: <?= Security::escape($tool->name) ?>
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
            
            <div class="card card-glass">
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $tool->status === 'available' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($tool->status) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Usage Hours:</strong> <?= $tool->usageHours ?> hrs</p>
                        </div>
                    </div>
                    
                    <?php if($tool->status === 'available'): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="datetime-local" name="start_time" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <input type="datetime-local" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-garden">
                            <i class="bi bi-calendar-check"></i> Confirm Reservation
                        </button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This tool is currently not available for reservation.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>