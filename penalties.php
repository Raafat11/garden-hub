<?php
$pageTitle = 'Penalty Engine - Admin';
require_once '../../core/Security.php';
require_once '../../classes/Reservation.php';

Security::requireRole(['admin']);

$db = Database::getInstance()->getConnection();

// Get late reservations
$stmt = $db->query("SELECT r.*, u.name as user_name, t.name as tool_name 
                    FROM reservations r 
                    JOIN users u ON r.userId = u.userId 
                    JOIN tools t ON r.toolId = t.toolId 
                    WHERE r.dueDate < NOW() AND r.status != 'returned'
                    ORDER BY r.dueDate ASC");
$lateReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = null;

// Calculate penalty for selected reservation
if(isset($_GET['calculate'])) {
    $reservationId = (int)$_GET['calculate'];
    $reservation = new Reservation();
    $penalty = $reservation->calculatePenalty($reservationId);
    if($penalty > 0) {
        $success = "Penalty calculated: $penalty EGP";
    }
}

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-exclamation-triangle"></i> Late Return Penalty Engine
            </h2>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= Security::escape($success) ?>
                </div>
            <?php endif; ?>
            
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Overdue Tool Reservations</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($lateReservations)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-check-circle display-1"></i>
                            <p class="mt-3">No overdue reservations. All tools returned on time!</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Tool</th>
                                    <th>Due Date</th>
                                    <th>Days Late</th>
                                    <th>Fine Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($lateReservations as $res): 
                                    $daysLate = floor((time() - strtotime($res['dueDate'])) / 86400);
                                ?>
                                <tr>
                                    <td><?= Security::escape($res['user_name']) ?></td>
                                    <td><?= Security::escape($res['tool_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($res['dueDate'])) ?></td>
                                    <td><span class="badge bg-danger"><?= $daysLate ?> days</span></td>
                                    <td><?= $res['fineAmount'] ? $res['fineAmount'] . ' EGP' : 'Not calculated' ?></td>
                                    <td>
                                        <a href="?calculate=<?= $res['reservationId'] ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-calculator"></i> Calculate Penalty
                                        </a>
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