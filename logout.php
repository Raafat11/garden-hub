<?php
require_once '../core/Security.php';

// Clear session and redirect to home
session_start();
session_destroy();
header("Location: index.php?logout=1");
exit();
?>