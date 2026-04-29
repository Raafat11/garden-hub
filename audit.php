<?php
$pageTitle = 'Audit Trail - Admin';
require_once '../../core/Security.php';
require_once '../../classes/Admin.php';

Security::requireRole(['admin']);

$admin = new Admin($_SESSION['userId']);
$db = Database::getInstance()->getConnection();

// Get filter parameters
$filterUser = $_GET['user']?? null;
$filterAction = $_GET['action']?? null;
$limit = 100;

// Build query with filters
$sql = "SELECT al.*, u.name as user_name, u.role
        FROM audit_logs al
        JOIN users u ON al.userId = u.userId
        WHERE 1=1";
$params = [];

if($filterUser) {
    $sql.= " AND al.userId =?";
    $params[] = $filterUser;
}

if($filterAction) {
    $sql.= " AND al.action LIKE?";
    $params[] = "%$filterAction%";
}

$sql.= " ORDER BY al.timestamp DESC LIMIT?";
$params[] = $limit;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get users for filter dropdown
$users = $db->query("SELECT userId, name FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php';?>

        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-clipboard-data"></i> System Audit Trail
            </h2>

            <!-- Filters -->
            <div class="card card-glass mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Filter by User</label>
                            <select name="user" class="form-select">
                                <option value="">All Users</option>
                                <?php foreach($users as $user):?>
                                    <option value="<?= $user['userId']?>" <?= $filterUser == $user['userId']? 'selected' : ''?>>
                                        <?= Security::escape($user['name'])?>
                                    </option>
                                <?php endforeach;?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Filter by Action</label>
                            <input type="text" name="action" class="form-control"
                                   value="<?= Security::escape($filterAction?? '')?>"
                                   placeholder="e.g., login, create, update">
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-garden me-2">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="audit.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Audit Logs Table -->
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check"></i> Audit Logs
                        <span class="badge bg-light text-dark"><?= count($auditLogs)?> records</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 datatable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Entity Type</th>
                                    <th>Entity ID</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($auditLogs as $log):?>
                                <tr>
                                    <td><small><?= date('M d, H:i:s', strtotime($log['timestamp']))?></small></td>
                                    <td><?= Security::escape($log['user_name'])?></td>
                                    <td><span class="badge bg-secondary"><?= ucfirst($log['role'])?></span></td>
                                    <td>
                                        <span class="badge bg-<?= str_contains($log['action'], 'delete')? 'danger' : (str_contains($log['action'], 'create')? 'success' : 'info')?>">
                                            <?= Security::escape($log['action'])?>
                                        </span>
                                    </td>
                                    <td><?= Security::escape($log['entityType']?? 'N/A')?></td>
                                    <td><?= $log['entityId']?? 'N/A'?></td>
                                    <td>
                                        <?php if($log['details']):?>
                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip"
                                                    title="<?= Security::escape($log['details'])?>">
                                                <i class="bi bi-info-circle"></i>
                                            </button>
                                        <?php else:?>
                                            -
                                        <?php endif;?>
                                    </td>
                                </tr>
                                <?php endforeach;?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})
</script>

<?php include '../../templates/footer.php';?>