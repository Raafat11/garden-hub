<?php
$pageTitle = 'Notifications - Garden Hub';
require_once '../core/Security.php';
require_once '../config/Database.php';

Security::requireLogin();

$db = Database::getInstance()->getConnection();

// Get user notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE userId = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['userId']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="row">
        <?php include '../templates/sidebar.php'; ?>
        
        <div class="col-md-10">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-bell"></i> Notifications
            </h2>
            
            <div class="card card-glass">
                <div class="card-body p-0">
                    <?php if(empty($notifications)): ?>
                        <div class="text-center p-5 text-muted">
                            <i class="bi bi-bell-slash display-1"></i>
                            <p class="mt-3">No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($notifications as $notif): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1"><?= Security::escape($notif['message']) ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?= date('M d, Y H:i', strtotime($notif['created_at'])) ?>
                                        </small>
                                    </div>
                                    <?php if(!$notif['isRead']): ?>
                                        <span class="badge bg-primary">New</span>
                                    <?php endif; ?>
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

<?php include '../templates/footer.php'; ?>