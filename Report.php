<?php
class Report {
    private $db;
    public $reportId, $createdBy, $plotId, $description, $photo, $date, $type;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createReport($createdBy, $plotId, $description, $photo, $type) {
        $stmt = $this->db->prepare("INSERT INTO reports (createdBy, plotId, description, photo, date, type) VALUES (?, ?, ?, ?, CURDATE(), ?)");
        return $stmt->execute([$createdBy, $plotId, $description, $photo, $type]);
    }

    public function getByUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM reports WHERE createdBy = ? ORDER BY date DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByPlot($plotId) {
        $stmt = $this->db->prepare("SELECT * FROM reports WHERE plotId = ? ORDER BY date DESC");
        $stmt->execute([$plotId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>