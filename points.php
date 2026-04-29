<?php
$pageTitle = 'Points Display - Volunteer';
require_once '../../core/Security.php';
require_once '../../classes/Volunteer.php';

Security::requireRole(['volunteer']);

$volunteer = new Volunteer($_SESSION['userId']);
$db = Database::getInstance()->getConnection();

// Get total points
$totalPoints = $volunteer->points;

// Get points history
$history = $db->prepare("SELECT 'task' as type, ct.taskName as description, ct.pointsReward as points, ct.completedAt as date 
                         FROM communal_tasks ct WHERE ct.completedBy = ? 
                         UNION ALL 
                         SELECT 'advice' as type, 'Helpful advice vote' as description, 10 as points, av.votedAt as date 
                         FROM advice_votes av JOIN p2p_advice pa ON av.adviceId = pa.adviceId WHERE pa.authorId = ?
                         ORDER BY date DESC LIMIT 50");
$history->execute([$_SESSION['userId'], $_SESSION['userId']]);
$pointHistory = $history->fetchAll(PDO::FETCH_ASSOC);

// Get leaderboard
$leaderboard = $db->query("SELECT u.name, v.points 
                           FROM volunteers v 
                           JOIN users u ON v.userId = u.userId 
                           ORDER BY v.points DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Get user rank
$rankStmt = $db->prepare("SELECT COUNT(*) + 1 FROM volunteers WHERE points > ?");
$rankStmt->execute([$totalPoints]);
$userRank = $rankStmt->fetchColumn();

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-star"></i> Points Display
            </h2>
            
            <!-- Points Overview -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-glass" style="background:linear-gradient(135deg, #FFB703 0%, #D62828 100%); color:white;">
                        <div class="card-body text-center">
                            <i class="bi bi-star-fill display-1"></i>
                            <h2 class="mt-2"><?= number_format($totalPoints) ?></h2>
                            <p class="mb-0">Total Points</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-trophy display-6" style="color:var(--accent);"></i>
                            <h3 class="mt-2">#<?= $userRank ?></h3>
                            <p class="text-muted mb-0">Your Rank</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card card-glass text-center">
                        <div class="card-body">
                            <i class="bi bi-graph-up display-6" style="color:var(--secondary);"></i>
                            <h3 class="mt-2"><?= count($pointHistory) ?></h3>
                            <p class="text-muted mb-0">Activities</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Points History -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Points History</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($pointHistory)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No points earned yet. Start volunteering!</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                    <table class="table table-hover mb-0">
                                        <thead class="sticky-top bg-white">
                                            <tr>
                                                <th>Date</th>
                                                <th>Activity</th>
                                                <th>Type</th>
                                                <th>Points</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($pointHistory as $entry): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($entry['date'])) ?></td>
                                                <td><?= Security::escape($entry['description']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $entry['type'] === 'task' ? 'success' : 'info' ?>">
                                                        <?= ucfirst($entry['type']) ?>
                                                    </span>
                                                </td>
                                                <td><strong class="text-success">+<?= $entry['points'] ?></strong></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Leaderboard -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-trophy"></i> Leaderboard - Top 10</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach($leaderboard as $index => $leader): 
                                    $medal = $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : ''));
                                ?>
                                <div class="list-group-item <?= $leader['name'] === $_SESSION['name'] ? 'bg-light' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="me-2"><?= $medal ?: '#' . ($index + 1) ?></span>
                                            <strong><?= Security::escape($leader['name']) ?></strong>
                                            <?php if($leader['name'] === $_SESSION['name']): ?>
                                                <span class="badge bg-primary ms-1">You</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-star-fill"></i> <?= number_format($leader['points']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>