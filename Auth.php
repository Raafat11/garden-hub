<?php
require_once __DIR__ . '/../classes/User.php';

class Auth {
    public static function login($email, $password) {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $user = new User();
        return $user->login($email, $password);
    }

    public static function logout() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header("Location: /garden-hub/public/index.php");
        exit();
    }

    public static function check() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['userId']);
    }

    public static function user() {
        if(!self::check()) return null;
        return new User($_SESSION['userId']);
    }

    public static function id() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['userId'] ?? null;
    }

    public static function role() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['role'] ?? null;
    }
}
?>