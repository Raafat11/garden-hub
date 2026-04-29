<?php
$pageTitle = 'Rate User - Garden Hub';
require_once '../core/Security.php';
require_once '../config/Database.php';

Security::requireLogin();

$userId = $_GET['user'] ?? null;
$success = null;
$error = null;

if(!$userId) {
    header("Location: index.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// Get user info
$userStmt = $db->prepare("SELECT userId, name, role FROM users WHERE userId = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if(!$user || $user['userId'] == $_SESSION['userId']) {
    header("Location: index.php?error=invalid");
    exit();
}

// Handle rating submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $score = (int)$_POST['score'];
        $comment = Security::sanitize($_POST['comment']);
        
        if($score < 1 || $score > 5) {
            $error = "Score must be between 1 and 5.";
        } else {
            $stmt = $db->prepare("INSERT INTO ratings (fromUserId, toUserId, score, comment) VALUES (?, ?, ?, ?)");
            if($stmt->execute([$_SESSION['userId'], $userId, $score, $comment])) {
                $success = "Rating submitted successfully!";
            } else {
                $error = "Failed to submit rating.";
            }
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
                <i class="bi bi-star"></i> Rate User
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
                    <div class="mb-4">
                        <h5>Rating: <?= Security::escape($user['name']) ?></h5>
                        <p class="text-muted">Role: <?= ucfirst(str_replace('_', ' ', $user['role'])) ?></p>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Score (1-5 Stars)</label>
                            <select name="score" class="form-select" required>
                                <option value="">Select rating...</option>
                                <option value="5">⭐⭐⭐⭐⭐ (5) Excellent</option>
                                <option value="4">⭐⭐⭐⭐ (4) Good</option>
                                <option value="3">⭐⭐⭐ (3) Average</option>
                                <option value="2">⭐⭐ (2) Below Average</option>
                                <option value="1">⭐ (1) Poor</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Comment (Optional)</label>
                            <textarea name="comment" class="form-control" rows="4" 
                                      placeholder="Share your experience..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-garden">
                            <i class="bi bi-send"></i> Submit Rating
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>