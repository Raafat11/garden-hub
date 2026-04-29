<?php
require_once __DIR__ . '/User.php';

class Warden extends User {
    
    public function __construct($userId = null) {
        parent::__construct($userId); 
    }

    public function getDashboardStats() {
        $stats = [
            'pending_reports' => 0,
            'seed_records' => 0,
            'security_alerts' => 0,
            'tool_damages' => 0
        ];

        $stmt = $this->db->query("SELECT COUNT(*) FROM incident_reports WHERE status = 'pending'");
        $stats['pending_reports'] = $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM seed_records WHERE createdBy = ?");
        $stmt->execute([$this->userId]);
        $stats['seed_records'] = $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM security_alerts WHERE status = 'active'");
        $stats['security_alerts'] = $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM tool_damage_reports WHERE status = 'pending'");
        $stats['tool_damages'] = $stmt->fetchColumn();

        return $stats;
    }

    public function getRecentIncidents($limit = 5) {
        $stmt = $this->db->prepare("SELECT * FROM incident_reports ORDER BY createdAt DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT); 
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createReport($plotId, $notes, $photo = null) {
        $stmt = $this->db->prepare("INSERT INTO reports (createdBy, plotId, description, photo, date, type) VALUES (?, ?, ?, ?, CURDATE(), 'warden_inspect')");
        $result = $stmt->execute([$this->userId, $plotId, $notes, $photo]);
        
        if($result) {
            $this->logAction('warden_inspect', 'plot', $plotId);
        }
        return $result;
    }

    public function updateReport($reportId, $notes) {
        $stmt = $this->db->prepare("UPDATE reports SET description = ? WHERE reportId = ? AND createdBy = ?");
        $result = $stmt->execute([$notes, $reportId, $this->userId]);
        
        if($result) {
            $this->logAction('update_report', 'report', $reportId);
        }
        return $result;
    }

    public function deleteReport($reportId) {
        $stmt = $this->db->prepare("DELETE FROM reports WHERE reportId = ? AND createdBy = ?");
        $result = $stmt->execute([$reportId, $this->userId]);
        
        if($result) {
            $this->logAction('delete_report', 'report', $reportId);
        }
        return $result;
    }

    public function sendAlert($plotId, $message) {
        $stmt = $this->db->prepare("INSERT INTO alerts (type, severity, message, createdBy) VALUES ('security', 'high', ?, ?)");
        $result = $stmt->execute([$message, $this->userId]);
        
        if($result) {
            $this->logAction('security_alert', 'plot', $plotId);
        }
        return $result;
    }

    public function reportToolDamage($toolId, $description) {
        $stmt = $this->db->prepare("UPDATE tools SET status = 'damaged' WHERE toolId = ?");
        $stmt->execute([$toolId]);
        
        $incident = $this->db->prepare("INSERT INTO incidents (reportedBy, description, severity, status) VALUES (?, ?, 'high', 'open')");
        $result = $incident->execute([$this->userId, "Tool #$toolId damaged: " . $description]);
        
        if($result) {
            $this->logAction('tool_damage', 'tool', $toolId);
        }
        return $result;
    }

    public function createSeedRecord($data) {
        $stmt = $this->db->prepare("INSERT INTO seed_records (seedName, variety, harvestDate, createdBy) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$data['seedName'], $data['variety'], $data['harvestDate'], $this->userId]);
    }
}
?>