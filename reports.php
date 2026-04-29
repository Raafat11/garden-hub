<?php
$pageTitle = 'Reports - Admin';
require_once '../../core/Security.php';
require_once '../../classes/Admin.php';

Security::requireRole(['admin']);

$admin = new Admin($_SESSION['userId']);
$db = Database::getInstance()->getConnection();

$reportData = null;
$reportType = $_GET['type']?? null;

// Generate report based on type
if($reportType) {
    switch($reportType) {
        case 'users':
            $stmt = $db->query("SELECT role, COUNT(*) as count, AVG(communityPoints) as avg_points
                                FROM users GROUP BY role ORDER BY count DESC");
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'plots':
            $stmt = $db->query("SELECT status, COUNT(*) as count, AVG(area) as avg_area
                                FROM plots GROUP BY status");
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'trades':
            $stmt = $db->query("SELECT DATE(date) as trade_date, COUNT(*) as count, type
                                FROM trades
                                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                GROUP BY DATE(date), type
                                ORDER BY trade_date DESC");
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'incidents':
            $stmt = $db->query("SELECT type, severity, COUNT(*) as count
                                FROM incidents
                                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                                GROUP BY type, severity
                                ORDER BY count DESC");
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'revenue':
            $stmt = $db->query("SELECT DATE_FORMAT(startDate, '%Y-%m') as month,
                                SUM(rentAmount) as total_revenue, COUNT(*) as lease_count
                                FROM leases
                                WHERE status = 'active'
                                GROUP BY DATE_FORMAT(startDate, '%Y-%m')
                                ORDER BY month DESC LIMIT 12");
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
}

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php';?>

        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-graph-up"></i> System Reports
            </h2>

            <!-- Report Type Selection -->
            <div class="card card-glass mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Select Report Type</h5>
                    <div class="row g-2">
                        <div class="col-md-2">
                            <a href="?type=users" class="btn btn-outline-primary w-100 <?= $reportType === 'users'? 'active' : ''?>">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="?type=plots" class="btn btn-outline-success w-100 <?= $reportType === 'plots'? 'active' : ''?>">
                                <i class="bi bi-grid-3x3"></i> Plots
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="?type=trades" class="btn btn-outline-info w-100 <?= $reportType === 'trades'? 'active' : ''?>">
                                <i class="bi bi-arrow-left-right"></i> Trades
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?type=incidents" class="btn btn-outline-warning w-100 <?= $reportType === 'incidents'? 'active' : ''?>">
                                <i class="bi bi-exclamation-triangle"></i> Incidents
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?type=revenue" class="btn btn-outline-danger w-100 <?= $reportType === 'revenue'? 'active' : ''?>">
                                <i class="bi bi-cash-coin"></i> Revenue
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Display -->
            <?php if($reportData):?>
            <div class="card card-glass">
                <div class="card-header" style="background:var(--primary); color:white;">
                    <h5 class="mb-0">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <?= ucfirst($reportType)?> Report
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped datatable">
                            <thead>
                                <tr>
                                    <?php foreach(array_keys($reportData[0]) as $header):?>
                                        <th><?= ucwords(str_replace('_', ' ', $header))?></th>
                                    <?php endforeach;?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($reportData as $row):?>
                                <tr>
                                    <?php foreach($row as $value):?>
                                        <td><?= Security::escape($value)?></td>
                                    <?php endforeach;?>
                                </tr>
                                <?php endforeach;?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                        <button class="btn btn-outline-success" onclick="exportToCSV()">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                        </button>
                    </div>
                </div>
            </div>
            <?php else:?>
            <div class="card card-glass">
                <div class="card-body text-center p-5 text-muted">
                    <i class="bi bi-graph-up display-1"></i>
                    <p class="mt-3">Select a report type above to generate data</p>
                </div>
            <?php endif;?>
        </div>
    </div>
</div>

<script>
function exportToCSV() {
    let table = document.querySelector('.datatable');
    let csv = [];
    let rows = table.querySelectorAll('tr');

    for(let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for(let j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        csv.push(row.join(','));
    }

    let csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    let downloadLink = document.createElement('a');
    downloadLink.download = 'report_<?= $reportType?>_<?= date("Y-m-d")?>.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.click();
}
</script>

<?php include '../../templates/footer.php';?>