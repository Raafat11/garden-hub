<?php
class Tool {
    private $db;
    public $toolId, $name, $status, $usageHours;

    public function __construct($toolId = null) {
        $this->db = Database::getInstance()->getConnection();
        if($toolId) $this->load($toolId);
    }

    public function updateStatus($status) {
        $stmt = $this->db->prepare("UPDATE tools SET status = ? WHERE toolId = ?");
        return $stmt->execute([$status, $this->toolId]);
    }

    public function logUsage($hours) {
        $stmt = $this->db->prepare("UPDATE tools SET usageHours = usageHours + ? WHERE toolId = ?");
        return $stmt->execute([$hours, $this->toolId]);
    }

    public function getAllAvailable() {
        $stmt = $this->db->query("SELECT * FROM tools WHERE status = 'available'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function load($toolId) {
        $stmt = $this->db->prepare("SELECT * FROM tools WHERE toolId = ?");
        $stmt->execute([$toolId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if($data) {
            foreach($data as $key => $val) $this->$key = $val;
        }
    }
}
?>