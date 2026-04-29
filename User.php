<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    protected $db;
    protected $userId;
    protected $name;
    protected $email;
    protected $password;
    protected $role;
    protected $communityPoints;
    protected $karmaPoints;

    public function __construct($userId = null) {
        $this->db = Database::getInstance()->getConnection();
        if($userId) {
            $this->loadUser($userId);
        }
    }

    public function register($name, $email, $password, $role = 'volunteer') { // غيرت default لـ volunteer
        $hashedPass = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $hashedPass, $role]);
    }

    // <<include>> validation
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['userId'] = $user['userId'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            $this->userId = $user['userId'];
            $this->role = $user['role'];
            $this->name = $user['name'];
            $this->email = $user['email'];
            
            $this->logAction('login');
            return true;
        }
        return false;
    }

    public function updateProfile($data) {
        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ? WHERE userId = ?");
        return $stmt->execute([$data['name'], $data['email'], $this->userId]);
    }

    public function addPoints($points) {
        $stmt = $this->db->prepare("UPDATE users SET communityPoints = communityPoints + ? WHERE userId = ?");
        return $stmt->execute([$points, $this->userId]);
    }

    public function redeemPoints($points) {
        $stmt = $this->db->prepare("UPDATE users SET communityPoints = communityPoints - ? WHERE userId = ? AND communityPoints >= ?");
        return $stmt->execute([$points, $this->userId, $points]);
    }

    protected function loadUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE userId = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if($data) {
            $this->userId = $data['userId'];
            $this->name = $data['name'];
            $this->email = $data['email'];
            $this->role = $data['role'];
            $this->communityPoints = $data['communityPoints'];
            $this->karmaPoints = $data['karmaPoints'];
        }
    }

    protected function logAction($action, $entityType = null, $entityId = null) {
        if(!$this->userId) return false;
        
        $stmt = $this->db->prepare("INSERT INTO audit_logs (userId, action, entityType, entityId) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$this->userId, $action, $entityType, $entityId]);
    }

    // Getters
    public function getUserId() { return $this->userId; }
    public function getRole() { return $this->role; }
    public function getName() { return $this->name; }
}
?>