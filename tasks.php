<?php
$pageTitle = 'Communal Tasks - Volunteer';
require_once '../../core/Security.php';
require_once '../../classes/Volunteer.php';

Security::requireRole(['volunteer']);

$volunteer = new Volunteer($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle task completion
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $taskId = (int)$_POST['task_id'];
        $action = $_POST['action'];
        
        if($action === 'complete_task') {
            $pointsEarned = (int)$_POST['points'];
            
            $db->beginTransaction();
            try {
                // Mark task as completed
                $stmt = $db->prepare("UPDATE communal_tasks SET status = 'completed', completedBy = ?, completedAt = NOW() 
                                      WHERE taskId = ? AND assignedTo = ?");
                $stmt->execute([$_SESSION['userId'], $taskId, $_SESSION['userId']]);
                
                // Assign points
                $stmt = $db->prepare("UPDATE volunteers SET points = points + ? WHERE userId = ?");
                $stmt->execute([$pointsEarned, $_SESSION['userId']]);
                
                $db->commit();
                $success = "Task completed! You earned $pointsEarned points.";
            } catch(Exception $e) {
                $db->rollBack();
                $error = "Failed to complete task.";
            }
        }
    }
}

// Get available tasks
$availableTasks = $db->query("SELECT ct.*, u.name as assigned_name FROM communal_tasks ct 
                              LEFT JOIN users u ON ct.assignedTo = u.userId 
                              WHERE ct.status = 'pending' AND (ct.assignedTo IS NULL OR ct.assignedTo = {$_SESSION['userId']})
                              ORDER BY ct.deadline ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get completed tasks
$completedTasks = $db->prepare("SELECT * FROM communal_tasks WHERE completedBy = ? ORDER BY completedAt DESC LIMIT 10");
$completedTasks->execute([$_SESSION['userId']]);
$completed = $completedTasks->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-list-task"></i> Communal Tasks <<include>> Assign Points
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
                <!-- Available Tasks -->
                <div class="col-md-7">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-list-check"></i> Available Tasks</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($availableTasks)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-check-circle display-1"></i>
                                    <p class="mt-3">No tasks available right now</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($availableTasks as $task): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title"><?= Security::escape($task['taskName']) ?></h6>
                                                <p class="mb-2"><?= Security::escape($task['description']) ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> Deadline: <?= date('M d, Y', strtotime($task['deadline'])) ?> |
                                                    <i class="bi bi-star"></i> Points: <?= $task['pointsReward'] ?>
                                                </small>
                                            </div>
                                        </div>
                                        <form method="POST" class="mt-2">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                            <input type="hidden" name="action" value="complete_task">
                                            <input type="hidden" name="task_id" value="<?= $task['taskId'] ?>">
                                            <input type="hidden" name="points" value="<?= $task['pointsReward'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle"></i> Mark Complete (+<?= $task['pointsReward'] ?> pts)
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Completed Tasks -->
                <div class="col-md-5">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--accent); color:white;">
                            <h5 class="mb-0"><i class="bi bi-check-circle"></i> Completed Tasks</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($completed)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mt-2">No completed tasks yet</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($completed as $task): ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1"><?= Security::escape($task['taskName']) ?></h6>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted"><?= date('M d, Y', strtotime($task['completedAt'])) ?></small>
                                            <span class="badge bg-success">+<?= $task['pointsReward'] ?> pts</span>
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