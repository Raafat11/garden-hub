<?php
$pageTitle = 'Shift Balancer - Volunteer';
require_once '../../core/Security.php';
require_once '../../classes/Volunteer.php';

Security::requireRole(['volunteer']);

$volunteer = new Volunteer($_SESSION['userId']);
$db = Database::getInstance()->getConnection();

// Get shift statistics
$shiftStats = $db->query("SELECT 
                          COUNT(*) as total_shifts,
                          SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                          SUM(CASE WHEN status = 'approved' AND startTime > NOW() THEN 1 ELSE 0 END) as upcoming
                          FROM shifts WHERE volunteerId = {$_SESSION['userId']}")->fetch(PDO::FETCH_ASSOC);

// Get shift distribution by day of week
$dayDistribution = $db->query("SELECT DAYNAME(startTime) as day, COUNT(*) as count 
                               FROM shifts WHERE volunteerId = {$_SESSION['userId']} 
                               GROUP BY DAYOFWEEK(startTime) ORDER BY DAYOFWEEK(startTime)")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-bar-chart"></i> Shift Balancer
            </h2>
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-calendar-check display-6" style="color:#2D6A4F;"></i>
                            <h3 class="mt-2"><?= $shiftStats['total_shifts'] ?></h3>
                            <p class="text-muted mb-0">Total Shifts</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-check-circle display-6" style="color:#2D6A4F;"></i>
                            <h3 class="mt-2"><?= $shiftStats['completed'] ?></h3>
                            <p class="text-muted mb-0">Completed</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-clock display-6" style="color:#FFB703;"></i>
                            <h3 class="mt-2"><?= $shiftStats['upcoming'] ?></h3>
                            <p class="text-muted mb-0">Upcoming</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Day Distribution -->
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0"><i class="bi bi-calendar-week"></i> Your Shift Distribution by Day</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($dayDistribution)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-inbox display-1"></i>
                            <p class="mt-3">No shift data available</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Day of Week</th>
                                    <th>Number of Shifts</th>
                                    <th>Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxCount = max(array_column($dayDistribution, 'count'));
                                foreach($dayDistribution as $day): 
                                    $percentage = ($day['count'] / $maxCount) * 100;
                                ?>
                                <tr>
                                    <td><strong><?= $day['day'] ?></strong></td>
                                    <td><?= $day['count'] ?> shifts</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: <?= $percentage ?>%; background: var(--primary);">
                                                <?= $day['count'] ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-lightbulb"></i> <strong>Balancing Tip:</strong> 
                            Try to distribute your shifts evenly across the week to maintain work-life balance and avoid burnout.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>