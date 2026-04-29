<?php
class AuditLog {
    private $db;
    public $logId, $userId, $action, $entityType, $entityId, $timestamp;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function recordAction($action, $userId, $entityType = null, $entityId = null) {
        $stmt = $this->db->prepare("INSERT INTO audit_logs (userId, action, entityType, entityId) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $entityType, $entityId]);
    }

    public function getLogs($limit = 100) {
        $stmt = $this->db->prepare("SELECT a.*, u.name FROM audit_logs a JOIN users u ON a.userId = u.userId ORDER BY timestamp DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filterLogs($actionType) {
        $stmt = $this->db->prepare("SELECT a.*, u.name FROM audit_logs a JOIN users u ON a.userId = u.userId WHERE a.action = ? ORDER BY timestamp DESC");
        $stmt->execute([$actionType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Use Case: system audit trail
    public function generateSystemReport($startDate, $endDate) {
        $stmt = $this->db->prepare("SELECT * FROM audit_logs WHERE timestamp BETWEEN ? AND ? ORDER BY timestamp DESC");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>