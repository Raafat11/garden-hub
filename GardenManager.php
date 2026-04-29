<?php
require_once 'User.php';

class GardenManager extends User {
    
    // Use Case: land use compliance <<include>> report
    public function landUseCompliance($plotId, $notes, $photo = null) {
        $stmt = $this->db->prepare("INSERT INTO reports (createdBy, plotId, description, photo, date, type) VALUES (?, ?, ?, ?, CURDATE(), 'land_use')");
        $result = $stmt->execute([$this->userId, $plotId, $notes, $photo]);
        $this->logAction('create_report', 'plot', $plotId);
        return $result;
    }

    // Use Case: seed viability <<include>> validation
    public function validateSeed($seedId) {
        $stmt = $this->db->prepare("SELECT * FROM seeds WHERE seedId = ?");
        $stmt->execute([$seedId]);
        $seed = $stmt->fetch();
        
        $today = new DateTime();
        $expiry = new DateTime($seed['expiryDate']);
        $isValid = $expiry > $today;
        
        $this->logAction('validate_seed', 'seed', $seedId);
        return $isValid;
    }

    // Use Case: update plot status
    public function updatePlotStatus($plotId, $status) {
        $stmt = $this->db->prepare("UPDATE plots SET status = ? WHERE plotId = ?");
        return $stmt->execute([$status, $plotId]);
    }
}
?>