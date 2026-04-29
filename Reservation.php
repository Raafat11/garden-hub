<?php
class Reservation {
    private $db;
    public $reservationId, $userId, $toolId, $startTime, $endTime, $fineAmount, $penaltyHours, $dueDate, $status;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function reserveTool($userId, $toolId, $startTime, $endTime) {
        $check = $this->db->prepare("SELECT status FROM tools WHERE toolId = ?");
        $check->execute([$toolId]);
        if($check->fetchColumn() !== 'available') return false;

        $stmt = $this->db->prepare("INSERT INTO reservations (userId, toolId, startTime, endTime, dueDate) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$userId, $toolId, $startTime, $endTime]);
        
        if($result) {
            $this->db->prepare("UPDATE tools SET status = 'in_use' WHERE toolId = ?")->execute([$toolId]);
        }
        return $result;
    }

    public function isLate($reservationId) {
        $stmt = $this->db->prepare("SELECT dueDate FROM reservations WHERE reservationId = ?");
        $stmt->execute([$reservationId]);
        $due = new DateTime($stmt->fetchColumn());
        return new DateTime() > $due;
    }

    public function calculatePenalty($reservationId) {
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