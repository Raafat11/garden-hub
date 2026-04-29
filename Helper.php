<?php
class Helper {
    public static function redirect($url) {
        header("Location: $url");
        exit();
    }

    public static function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    public static function uploadImage($file, $directory = 'uploads/') {
        $targetDir = __DIR__ . '/../public/' . $directory;
        if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . '_' . basename($file['name']);
        $targetFile = $targetDir . $fileName;
        
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if(!in_array($imageFileType, $allowed)) return false;
        if($file['size'] > 5000000) return false; // 5MB max
        
        if(move_uploaded_file($file['tmp_name'], $targetFile)) {
            return $directory . $fileName;
        }
        return false;
    }

    public static function formatDate($date) {
        return date('d M Y', strtotime($date));
    }

    public static function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        if($time < 60) return 'just now';
        if($time < 3600) return floor($time/60) . 'm ago';
        if($time < 86400) return floor($time/3600) . 'h ago';
        return floor($time/86400) . 'd ago';
    }
}
?>