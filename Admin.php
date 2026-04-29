<?php
require_once 'User.php';

class Admin extends User {
    
    //Use Case: RBAC
    public function manageUsers($targetUserId, $newRole) {
        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE userId = ?");
        $this->logAction('RBAC_change', 'user', $targetUserId);
        return $stmt->execute([$newRole, $targetUserId]);
    }

    // Use Case: emergency broadcasts
    public function emergencyBroadcasts($message, $severity) {
        $stmt = $this->db->prepare("INSERT INTO alerts (type, severity, message, createdBy) VALUES ('emergency', ?, ?, ?)");
        $stmt->execute([$severity, $message, $this->userId]);
        
        //Notification
        $users = $this->db->query("SELECT userId FROM users")->fetchAll();
        $notifStmt = $this->db->prepare("INSERT INTO notifications (userId, message) VALUES (?, ?)");
        foreach($users as $u) {
            $notifStmt->execute([$u['userId'], "EMERGENCY: " . $message]);
        }
        return true;
    }

    // Use Case: system audit trail
    public function generateReports() {
        $stmt = $this->db->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 100");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Use Case: late return penalty engine <<include>> calculate delay
    public function calculateDelay($reservationId) {
        $stmt = $this->db->prepare("SELECT * FROM reservations WHERE reservationId = ?");
        $stmt->execute([$reservationId]);
        $res = $stmt->fetch();
        
        $now = new DateTime();
        $due = new DateTime($res['dueDate']);
        if($now > $due) {
            $diff = $now->diff($due);
            $hours = $diff->h + ($diff->days * 24);
            $penalty = $hours * 5; 
            
            $update = $this->db->prepare("UPDATE reservations SET fineAmount = ?, penaltyHours = ?, status = 'late' WHERE reservationId = ?");
            $update->execute([$penalty, $hours, $reservationId]);
            return $penalty;
        }
        return 0;
    }
}
?>