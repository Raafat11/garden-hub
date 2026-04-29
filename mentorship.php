<?php
$pageTitle = 'Mentorship Pairing - Volunteer';
require_once '../../core/Security.php';
require_once '../../classes/Volunteer.php';

Security::requireRole(['volunteer']);

$volunteer = new Volunteer($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle mentorship request
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'request_mentorship') {
            $mentorId = (int)$_POST['mentor_id'];
            $message = Security::sanitize($_POST['message']);
            
            $stmt = $db->prepare("INSERT INTO mentorships (mentorId, menteeId, message, status) 
                                  VALUES (?, ?, ?, 'pending')");
            if($stmt->execute([$mentorId, $_SESSION['userId'], $message])) {
                $success = "Mentorship request sent successfully!";
            } else {
                $error = "Failed to send request.";
            }
        }
    }
}

// Get available mentors
$mentors = $db->query("SELECT u.userId, u.name, u.email, v.points, v.skills 
                       FROM users u 
                       JOIN volunteers v ON u.userId = v.userId 
                       WHERE u.role = 'volunteer' AND u.userId != {$_SESSION['userId']} 
                       ORDER BY v.points DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get my mentorships
$myMentorships = $db->prepare("SELECT m.*, u.name as partner_name, 
                               CASE WHEN m.mentorId = ? THEN 'mentor' ELSE 'mentee' END as role
                               FROM mentorships m 
                               JOIN users u ON (m.mentorId = u.userId OR m.menteeId = u.userId) AND u.userId != ?
                               WHERE m.mentorId = ? OR m.menteeId = ?");
$myMentorships->execute([$_SESSION['userId'], $_SESSION['userId'], $_SESSION['userId'], $_SESSION['userId']]);
$mentorships = $myMentorships->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-people"></i> Mentorship Pairing
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
                <!-- Available Mentors -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-person-badge"></i> Available Mentors</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($mentors)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">No mentors available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($mentors as $mentor): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title"><?= Security::escape($mentor['name']) ?></h6>
                                                <p class="mb-1"><small class="text-muted">
                                                    <i class="bi bi-star"></i> <?= $mentor['points'] ?> points
                                                </small></p>
                                                <?php if($mentor['skills']): ?>
                                                    <p class="mb-2"><small><strong>Skills:</strong> <?= Security::escape($mentor['skills']) ?></small></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                data-bs-target="#mentorModal<?= $mentor['userId'] ?>">
                                            <i class="bi bi-hand-index"></i> Request Mentorship
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Request Modal -->
                                <div class="modal fade" id="mentorModal<?= $mentor['userId'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Request Mentorship from <?= Security::escape($mentor['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                    <input type="hidden" name="action" value="request_mentorship">
                                                    <input type="hidden" name="mentor_id" value="<?= $mentor['userId'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Message to Mentor</label>
                                                        <textarea name="message" class="form-control" rows="3" required 
                                                                  placeholder="Introduce yourself and explain what you'd like to learn..."></textarea>
                                                    </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-garden">Send Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- My Mentorships -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-people-fill"></i> My Mentorships</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($mentorships)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mt-2">No active mentorships</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($mentorships as $mentor): ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1"><?= Security::escape($mentor['partner_name']) ?></h6>
                                        <p class="mb-1">
                                            <span class="badge bg-<?= $mentor['role'] === 'mentor' ? 'success' : 'info' ?>">
                                                <?= ucfirst($mentor['role']) ?>
                                            </span>
                                        </p>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted"><?= date('M d, Y', strtotime($mentor['startDate'])) ?></small>
                                            <span class="badge bg-<?= $mentor['status'] === 'active' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($mentor['status']) ?>
                                            </span>
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