<?php
$pageTitle = 'Consumable Inventory - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle inventory update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $action = $_POST['action'];
        
        if($action === 'add_item') {
            $itemName = Security::sanitize($_POST['item_name']);
            $quantity = (float)$_POST['quantity'];
            $unit = Security::sanitize($_POST['unit']);
            $category = Security::sanitize($_POST['category']);
            
            $stmt = $db->prepare("INSERT INTO consumables (userId, itemName, quantity, unit, category) 
                                  VALUES (?, ?, ?, ?, ?)");
            if($stmt->execute([$_SESSION['userId'], $itemName, $quantity, $unit, $category])) {
                $success = "Item added to inventory!";
            } else {
                $error = "Failed to add item.";
            }
        } elseif($action === 'use_item') {
            $itemId = (int)$_POST['item_id'];
            $usedQuantity = (float)$_POST['used_quantity'];
            
            $stmt = $db->prepare("UPDATE consumables SET quantity = quantity - ? 
                                  WHERE consumableId = ? AND userId = ? AND quantity >= ?");
            if($stmt->execute([$usedQuantity, $itemId, $_SESSION['userId'], $usedQuantity])) {
                $success = "Inventory updated!";
            } else {
                $error = "Failed to update. Insufficient quantity.";
            }
        }
    }
}

// Get user's inventory
$inventory = $db->prepare("SELECT * FROM consumables WHERE userId = ? ORDER BY category, itemName");
$inventory->execute([$_SESSION['userId']]);
$items = $inventory->fetchAll(PDO::FETCH_ASSOC);

// Group by category
$groupedItems = [];
foreach($items as $item) {
    $groupedItems[$item['category']][] = $item;
}

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-box-seam"></i> Consumable Inventory
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
                <!-- Add Item -->
                <div class="col-md-4">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add Item</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <input type="hidden" name="action" value="add_item">
                                
                                <div class="mb-3">
                                    <label class="form-label">Item Name</label>
                                    <input type="text" name="item_name" class="form-control" required 
                                           placeholder="e.g., Organic Fertilizer">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select" required>
                                        <option value="">Select category...</option>
                                        <option value="fertilizer">Fertilizer</option>
                                        <option value="pesticide">Pesticide</option>
                                        <option value="seeds">Seeds</option>
                                        <option value="tools">Tools</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="quantity" class="form-control" step="0.1" min="0.1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Unit</label>
                                    <select name="unit" class="form-select" required>
                                        <option value="kg">Kilograms (kg)</option>
                                        <option value="L">Liters (L)</option>
                                        <option value="packets">Packets</option>
                                        <option value="pieces">Pieces</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-garden w-100">
                                    <i class="bi bi-plus-circle"></i> Add to Inventory
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Inventory List -->
                <div class="col-md-8">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-list-check"></i> Your Inventory</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($items)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox display-1"></i>
                                    <p class="mt-3">Your inventory is empty</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($groupedItems as $category => $categoryItems): ?>
                                    <h6 class="mt-3 mb-2" style="color:var(--primary);">
                                        <i class="bi bi-tag"></i> <?= ucfirst($category) ?>
                                    </h6>
                                    <table class="table table-sm table-hover mb-3">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Quantity</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($categoryItems as $item): ?>
                                            <tr>
                                                <td><?= Security::escape($item['itemName']) ?></td>
                                                <td>
                                                    <strong><?= $item['quantity'] ?></strong> <?= Security::escape($item['unit']) ?>
                                                    <?php if($item['quantity'] < 5): ?>
                                                        <span class="badge bg-warning ms-1">Low</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                            data-bs-target="#useModal<?= $item['consumableId'] ?>">
                                                        <i class="bi bi-dash-circle"></i> Use
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Use Modal -->
                                            <div class="modal fade" id="useModal<?= $item['consumableId'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-sm">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Use: <?= Security::escape($item['itemName']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                                                <input type="hidden" name="action" value="use_item">
                                                                <input type="hidden" name="item_id" value="<?= $item['consumableId'] ?>">
                                                                
                                                                <label class="form-label">Quantity to Use</label>
                                                                <input type="number" name="used_quantity" class="form-control" 
                                                                       step="0.1" min="0.1" max="<?= $item['quantity'] ?>" required>
                                                                <small class="text-muted">Available: <?= $item['quantity'] ?> <?= Security::escape($item['unit']) ?></small>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-garden">Use Item</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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