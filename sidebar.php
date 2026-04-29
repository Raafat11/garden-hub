<?php
$role = $_SESSION['role'] ?? 'guest';
$menus = [
    'admin' => [
        ['icon'=>'speedometer2','label'=>'Dashboard','link'=>'dashboard.php'],
        ['icon'=>'people','label'=>'RBAC','link'=>'rbac.php'],
        ['icon'=>'megaphone','label'=>'Emergency Broadcasts','link'=>'broadcasts.php'],
        ['icon'=>'cloud-lightning','label'=>'Weather Cancellation','link'=>'weather.php'],
        ['icon'=>'exclamation-triangle','label'=>'Penalty Engine','link'=>'penalties.php'],
        ['icon'=>'file-earmark-check','label'=>'Lease Approvals','link'=>'lease-approval.php'],
        ['icon'=>'clipboard-data','label'=>'Audit Trail','link'=>'audit.php'],
        ['icon'=>'graph-up','label'=>'Reports','link'=>'reports.php']
    ],
    'garden_manager' => [
        ['icon'=>'speedometer2','label'=>'Dashboard','link'=>'dashboard.php'],
        ['icon'=>'clipboard-check','label'=>'Land Use Compliance','link'=>'land-compliance.php'],
        ['icon'=>'exclamation-circle','label'=>'Incident Reports','link'=>'incidents.php'],
        ['icon'=>'bug','label'=>'Pest/Disease Alerts','link'=>'pest-alerts.php'],
        ['icon'=>'check-circle','label'=>'Seed Viability','link'=>'seed-validation.php'],
        ['icon'=>'patch-check','label'=>'Produce Quality','link'=>'quality.php'],
        ['icon'=>'geo-alt','label'=>'Update Plot Status','link'=>'plot-status.php'],
        ['icon'=>'diagram-3','label'=>'Genetic Lineage','link'=>'lineage.php'],
        ['icon'=>'tools','label'=>'Usage Maintenance','link'=>'maintenance.php']
    ],
    'warden' => [
        ['icon'=>'speedometer2','label'=>'Dashboard','link'=>'dashboard.php'],
        ['icon'=>'file-earmark-text','label'=>'Create Report','link'=>'create-report.php'],
        ['icon'=>'patch-check','label'=>'Validation','link'=>'validation.php'],
        ['icon'=>'seedling','label'=>'Seed Records','link'=>'seed-records.php'],
        ['icon'=>'shield-lock','label'=>'Security Access','link'=>'security.php'],
        ['icon'=>'wrench','label'=>'Tool Damage Report','link'=>'tool-damage.php']
    ],
    'plot_owner' => [
        ['icon'=>'speedometer2','label'=>'Dashboard','link'=>'dashboard.php'],
        ['icon'=>'droplet','label'=>'Automated Irrigation','link'=>'irrigation.php'],
        ['icon'=>'bar-chart','label'=>'Crop Data','link'=>'crop-data.php'],
        ['icon'=>'snow','label'=>'Seasonal Winterizing','link'=>'winterizing.php'],
        ['icon'=>'lightning','label'=>'Produce Flash Trade','link'=>'flash-trade.php'],
        ['icon'=>'gift','label'=>'Gift Economy','link'=>'gift.php'],
        ['icon'=>'shield-plus','label'=>'Allergy Guard','link'=>'allergy.php'],
        ['icon'=>'recycle','label'=>'Compost Contribution','link'=>'compost.php'],
        ['icon'=>'arrow-left-right','label'=>'Seed Exchange','link'=>'seed-exchange.php'],
        ['icon'=>'box','label'=>'Consumable Inventory','link'=>'inventory.php'],
        ['icon'=>'house-add','label'=>'Rent Plot','link'=>'rent-plot.php']
    ],
    'volunteer' => [
        ['icon'=>'speedometer2','label'=>'Dashboard','link'=>'dashboard.php'],
        ['icon'=>'list-task','label'=>'Communal Tasks','link'=>'tasks.php'],
        ['icon'=>'calendar-check','label'=>'Shift Balancer','link'=>'shifts.php'],
        ['icon'=>'people-fill','label'=>'Mentorship Pairing','link'=>'mentorship.php'],
        ['icon'=>'chat-dots','label'=>'P2P Advice','link'=>'advice.php'],
        ['icon'=>'arrow-repeat','label'=>'Shift Substitution','link'=>'swap-shift.php'],
        ['icon'=>'award','label'=>'My Points','link'=>'points.php']
    ]
];

// Fallback if role not found
if(!isset($menus[$role])) {
    $role = 'guest';
    $menus['guest'] = [];
}
?>
<div class="col-md-2 sidebar p-0">
    <div class="p-3 text-white">
        <h5><?= ucfirst(str_replace('_',' ',$role)) ?> Panel</h5>
        <hr>
        <ul class="nav flex-column">
            <?php foreach($menus[$role] as $item): ?>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == $item['link'] ? 'active' : '' ?>" 
                   href="<?= $item['link'] ?>">
                    <i class="bi bi-<?= $item['icon'] ?>"></i> <?= $item['label'] ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>