<?php
if(session_status() === PHP_SESSION_NONE) session_start();

class Security {
    public static function requireLogin() {
        if(!isset($_SESSION['userId'])) {
            header("Location: /garden-hub/public/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit();
        }
    }

    public static function requireRole($allowedRoles) {
        self::requireLogin();
        if(!in_array($_SESSION['role'], (array)$allowedRoles)) {
            header("Location: /garden-hub/public/index.php?error=unauthorized");
            exit();
        }
    }

    public static function generateCSRF() {
        if(!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function sanitize($data) {
        if(is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public static function escape($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function isLoggedIn() {
        return isset($_SESSION['userId']);
    }

    public static function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
}
?>