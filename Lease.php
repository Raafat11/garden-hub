<?php
class Lease {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Use Case: rentplot <<include>> check block availability <<extend>> confirm payment
    public function rentPlot($userId, $plotId, $months = 6) {
        // 1. Check availability
        $plot = $this->db->prepare("SELECT status FROM plots WHERE plotId = ?");
        $plot->execute([$plotId]);
        if($plot->fetchColumn() !== 'available') return false;

        // 2. Create lease
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime("+$months months"));
        $stmt = $this->db->prepare("INSERT INTO leases (plotId, userId, startDate, endDate, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$plotId, $userId, $start, $end]);
        return $this->db->lastInsertId();
    }

    public function confirmPayment($leaseId) {
        $stmt = $this->db->prepare("UPDATE leases SET status = 'active' WHERE leaseId = ?");
        return $stmt->execute([$leaseId]);
    }

    public function renewLease($leaseId, $months = 6) {
        $newEnd = date('Y-m-d', strtotime("+$months months"));
        $stmt = $this->db->prepare("UPDATE leases SET endDate = ?, status = 'active' WHERE leaseId = ?");
        return $stmt->execute([$newEnd, $leaseId]);
    }
}

class Waitlist {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    public function calculatePriority($userId) {
        $user = $this->db->prepare("SELECT communityPoints, karmaPoints FROM users WHERE userId = ?");
        $user->execute([$userId]);
        $data = $user->fetch();
        return ($data['communityPoints'] * 0.7) + ($data['karmaPoints'] * 0.3);
    }

    public function addToWaitlist($userId) {
        $priority = $this->calculatePriority($userId);
        $stmt = $this->db->prepare("INSERT INTO waitlist (userId, priorityScore) VALUES (?, ?)");
        return $stmt->execute([$userId, $priority]);
    }
}
?>