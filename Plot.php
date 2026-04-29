<?php
class Plot {
    private $db;
    public $plotId, $area, $soilType, $sunlightHours, $status, $currentUserId;

    public function __construct($plotId = null) {
        $this->db = Database::getInstance()->getConnection();
        if($plotId) $this->load($plotId);
    }

    public function calculateRent() {
        $basePrice = 50;
        $sunBonus = $this->sunlightHours * 2;
        return ($this->area * $basePrice) + $sunBonus;
    }

    public function assignUser($userId) {
        $stmt = $this->db->prepare("UPDATE plots SET currentUserId = ?, status = 'rented' WHERE plotId = ?");
        return $stmt->execute([$userId, $this->plotId]);
    }

    private function load($plotId) {
        $stmt = $this->db->prepare("SELECT * FROM plots WHERE plotId = ?");
        $stmt->execute([$plotId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        foreach($data as $key => $val) $this->$key = $val;
    }
}

class Soil {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function updateSoil($plotId, $pH, $fertilizerHistory, $cropType) {
        $stmt = $this->db->prepare("INSERT INTO soil (plotId, pH, fertilizerHistory, cropType) VALUES (?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE pH = ?, fertilizerHistory = ?, cropType = ?");
        return $stmt->execute([$plotId, $pH, $fertilizerHistory, $cropType, $pH, $fertilizerHistory, $cropType]);
    }
}
?>