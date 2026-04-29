<?php
class Payment {
    private $db;
    public $paymentId, $userId, $amount, $date, $status, $type, $refId;

    public function __construct($paymentId = null) {
        $this->db = Database::getInstance()->getConnection();
        if($paymentId) $this->load($paymentId);
    }

    // Use Case: <<extend>> confirm payment
    public function processPayment($userId, $amount, $type, $refId) {
        $stmt = $this->db->prepare("INSERT INTO payments (userId, amount, date, status, type, refId) VALUES (?, ?, CURDATE(), 'completed', ?, ?)");
        $result = $stmt->execute([$userId, $amount, $type, $refId]);
        
        if($result) {
            $this->paymentId = $this->db->lastInsertId();
            
            // lease
            if($type === 'lease') {
                require_once 'Lease.php';
                $lease = new Lease();
                $lease->confirmPayment($refId);
            }
            
            //Audit Log
            require_once 'AuditLog.php';
            $log = new AuditLog();
            $log->recordAction('payment_completed', $userId, 'payment', $this->paymentId);
        }
        return $result;
    }

    public function getUserPayments($userId) {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE userId = ? ORDER BY date DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function load($paymentId) {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE paymentId = ?");
        $stmt->execute([$paymentId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if($data) {
            foreach($data as $key => $val) $this->$key = $val;
        }
    }
}
?>