<?php 
if(session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../config/Database.php';

// Get unread notifications count
$notifCount = 0;
if(isset($_SESSION['userId'])) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE userId = ? AND isRead = 0");
    $stmt->execute([$_SESSION['userId']]);
    $notifCount = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Garden Hub' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2D6A4F;
            --secondary: #95D5B2;
            --accent: #FFB703;
            --dark: #1D3557;
            --danger: #D62828;
        }
        body { background: #F1FAEE; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background: var(--primary) !important; }
        .btn-garden { background: var(--accent); color: var(--dark); font-weight: 600; border: none; }
        .btn-garden:hover { background: #FB8500; color: white; }
        .card-glass {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(45,106,79,0.2);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .sidebar {
            background: var(--dark);
            min-height: calc(100vh - 56px);
        }
        .sidebar .nav-link { color: #B7C9E2; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { 
            color: white; 
            background: rgba(255,255,255,0.1);
            border-left: 3px solid var(--accent);
        }
        .stat-card { border-left: 4px solid var(--primary); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/garden-hub/public/index.php">
        <i class="bi bi-flower1"></i> Garden Hub
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if(isset($_SESSION['userId'])): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i> 
                    <?php if($notifCount > 0): ?>
                        <span class="badge bg-danger"><?= $notifCount ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/garden-hub/public/notifications.php">View All Notifications</a></li>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> <?= Security::escape($_SESSION['name']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/garden-hub/public/<?= $_SESSION['role'] ?>/dashboard.php">Dashboard</a></li>
                    <li><a class="dropdown-item" href="/garden-hub/public/profile.php">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/garden-hub/public/logout.php">Logout</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="/garden-hub/public/login.php">Log In</a></li>
            <li class="nav-item"><a class="btn btn-garden ms-2" href="/garden-hub/public/register.php">Join Us</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>