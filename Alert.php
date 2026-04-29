<?php
class Alert {
    private $db;
    public $alertId, $type, $severity, $message, $createdBy, $created_at;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Class Diagram + Use Case: emergency broadcasts
    public function triggerAlert($type, $severity, $message, $createdBy) {
        $stmt = $this->db->prepare("INSERT INTO alerts (type, severity, message, createdBy) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$type, $severity, $message, $createdBy]);
        
        if($result) {
            $this->alertId = $this->db->lastInsertId();
            $this->sendAlert($this->alertId);
        }
        return $result;
    }

    public function sendAlert($alertId) {
        $alert = $this->db->prepare("SELECT * FROM alerts WHERE alertId = ?");
        $alert->execute([$alertId]);
        $data = $alert->fetch();
        
        // Notification 
        $users = $this->db->query("SELECT userId FROM users")->fetchAll();
        $notifStmt = $this->db->prepare("INSERT INTO notifications (userId, message) VALUES (?, ?)");
        
        foreach($users as $u) {
            $notifStmt->execute([$u['userId'], "[" . strtoupper($data['severity']) . "] " . $data['message']]);
        }
        return true;
    }

    public function getAffectedUsers($alertId) {
        $stmt = $this->db->prepare("SELECT * FROM alerts WHERE alertId = ?");
        $stmt->execute([$alertId]);
        $alert = $stmt->fetch();
        
        if($alert['type'] === 'pest') {
            //Plot Owners
            return $this->db->query("SELECT userId FROM users WHERE role = 'plot_owner'")->fetchAll();
        }
        
        // Default: each user gets the alert
        return $this->db->query("SELECT userId FROM users")->fetchAll();
    }

    public function getActiveAlerts() {
        $stmt = $this->db->query("SELECT * FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>