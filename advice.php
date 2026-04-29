<?php
$pageTitle = 'P2P Advice - Volunteer';
require_once '../../core/Security.php';
require_once '../../classes/Volunteer.php';

Security::requireRole(['volunteer']);

$volunteer = new Volunteer($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle advice posting
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'post_advice') {
            $title = Security::sanitize($_POST['title']);
            $content = Security::sanitize($_POST['content']);
            $category = Security::sanitize($_POST['category']);
            
            $stmt = $db->prepare("INSERT INTO p2p_advice (authorId, title, content, category, creditsReward) 
                                  VALUES (?, ?, ?, ?, 10)");
            if($stmt->execute([$_SESSION['userId'], $title, $content, $category])) {
                $success = "Advice posted! You'll earn 10 credits when someone finds it helpful.";
            } else {
                $error = "Failed to post advice.";
            }
        } elseif($action === 'helpful_vote') {
            $adviceId = (int)$_POST['advice_id'];
            $authorId = (int)$_POST['author_id'];
            
            $db->beginTransaction();
            try {
                // Record helpful vote
                $stmt = $db->prepare("INSERT INTO advice_votes (adviceId, voterId) VALUES (?, ?)");
                $stmt->execute([$adviceId, $_SESSION['userId']]);
                
                // Reward author with credits
                $stmt = $db->prepare("UPDATE volunteers SET points = points + 10 WHERE userId = ?");
                $stmt->execute([$authorId]);
                
                $db->commit();
                $success = "Marked as helpful! Author earned 10 credits.";
            } catch(Exception $e) {
                $db->rollBack();
                $error = "You already voted on this advice.";
            }
        }
    }
}

// Get all advice posts
$advicePosts = $db->query("SELECT pa.*, u.name as author_name, 
                           (SELECT COUNT(*) FROM advice_votes WHERE adviceId = pa.adviceId) as helpful_count
                           FROM p2p_advice pa 
                           JOIN users u ON pa.authorId = u.userId 
                           ORDER BY pa.createdAt DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-chat-dots"></i> P2P Advice <<include>> Reward with Credits
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
                <!-- Post Advice -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Share Your Knowledge</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <input type="hidden" name="action" value="post_advice">
                                
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select" required>
                                        <option value="">Select category...</option>
                                        <option value="planting">Planting Tips</option>
                                        <option value="pest_control">Pest Control</option>
                                        <option value="irrigation">Irrigation</option>
                                        <option value="harvesting">Harvesting</option>
                                        <option value="soil_health">Soil Health</option>
                                        <option value="composting">Composting</option>
                                        <option value="general">General</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="title" class="form-control" required 
                                           placeholder="e.g., How to prevent aphids naturally">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Your Advice</label>
                                    <textarea name="content" class="form-control" rows="5" required 
                                              placeholder="Share your gardening wisdom..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-garden w-100">
                                    <i class="bi bi-send"></i> Post Advice
                                </button>
                            </form>
                            
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-star"></i> <strong>Earn Credits:</strong> 
                                Get 10 points each time someone marks your advice as helpful!
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Advice Feed -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-chat-square-text"></i> Community Advice</h5>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <?php if(empty($advicePosts)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No advice posted yet. Be the first!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($advicePosts as $advice): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0"><?= Security::escape($advice['title']) ?></h6>
                                            <span class="badge bg-info"><?= ucfirst($advice['category']) ?></span>
                                        </div>
                                        <p class="card-text"><?= Security::escape($advice['content']) ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?= Security::escape($advice['author_name']) ?> | 
                                                <i class="bi bi-clock"></i> <?= date('M d, Y', strtotime($advice['createdAt'])) ?>
                                            </small>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                <input type="hidden" name="action" value="helpful_vote">
                                                <input type="hidden" name="advice_id" value="<?= $advice['adviceId'] ?>">
                                                <input type="hidden" name="author_id" value="<?= $advice['authorId'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-hand-thumbs-up"></i> Helpful (<?= $advice['helpful_count'] ?>)
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>