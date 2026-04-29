<?php
$pageTitle = 'Allergy Guard - Owner';
require_once '../../core/Security.php';
require_once '../../classes/PlotOwner.php';

Security::requireRole(['plot_owner']);

$owner = new PlotOwner($_SESSION['userId']);
$db = Database::getInstance()->getConnection();
$success = null;
$error = null;

// Handle allergy preferences update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $allergies = $_POST['allergies'] ?? [];
        $allergiesJson = json_encode($allergies);
        
        $stmt = $db->prepare("INSERT INTO user_allergies (userId, allergies) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE allergies = VALUES(allergies)");
        if($stmt->execute([$_SESSION['userId'], $allergiesJson])) {
            $success = "Allergy preferences updated successfully!";
        } else {
            $error = "Failed to update preferences.";
        }
    }
}

// Get user's current allergies
$stmt = $db->prepare("SELECT allergies FROM user_allergies WHERE userId = ?");
$stmt->execute([$_SESSION['userId']]);
$currentAllergies = $stmt->fetchColumn();
$userAllergies = $currentAllergies ? json_decode($currentAllergies, true) : [];

// Get all plants with allergen info
$plants = $db->query("SELECT plantId, plantName, allergens FROM plants WHERE allergens IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

// Filter plants that match user allergies
$warningPlants = [];
foreach($plants as $plant) {
    $plantAllergens = json_decode($plant['allergens'], true) ?: [];
    if(array_intersect($userAllergies, $plantAllergens)) {
        $warningPlants[] = $plant;
    }
}

$commonAllergens = ['pollen', 'nuts', 'soy', 'wheat', 'dairy', 'eggs', 'shellfish', 'latex'];

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>
        
        <div class="col-md-10 p-4">
            <h2 style="color:#1D3557;" class="mb-4">
                <i class="bi bi-shield-exclamation"></i> Allergy Guard
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
                <!-- Allergy Preferences -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--primary); color:white;">
                            <h5 class="mb-0"><i class="bi bi-gear"></i> Your Allergy Preferences</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                
                                <p class="text-muted mb-3">Select allergens you need to avoid:</p>
                                
                                <?php foreach($commonAllergens as $allergen): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="allergies[]" 
                                           value="<?= $allergen ?>" id="allergy_<?= $allergen ?>"
                                           <?= in_array($allergen, $userAllergies) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="allergy_<?= $allergen ?>">
                                        <?= ucfirst($allergen) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                
                                <button type="submit" class="btn btn-garden w-100 mt-3">
                                    <i class="bi bi-check-circle"></i> Save Preferences
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Allergy Warnings -->
                <div class="col-md-6">
                    <div class="card card-glass">
                        <div class="card-header" style="background:var(--danger); color:white;">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Plants to Avoid</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($userAllergies)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-shield-check display-1"></i>
                                    <p class="mt-3">Set your allergies to see warnings</p>
                                </div>
                            <?php elseif(empty($warningPlants)): ?>
                                <div class="text-center p-4 text-success">
                                    <i class="bi bi-check-circle display-1"></i>
                                    <p class="mt-3">No allergy warnings! All plants are safe for you.</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <strong><i class="bi bi-exclamation-triangle"></i> Warning:</strong> 
                                    The following plants contain allergens you selected:
                                </div>
                                <div class="list-group">
                                    <?php foreach($warningPlants as $plant): 
                                        $plantAllergens = json_decode($plant['allergens'], true) ?: [];
                                        $matchedAllergens = array_intersect($userAllergies, $plantAllergens);
                                    ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1"><?= Security::escape($plant['plantName']) ?></h6>
                                        <small class="text-danger">
                                            Contains: <?= implode(', ', array_map('ucfirst', $matchedAllergens)) ?>
                                        </small>
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