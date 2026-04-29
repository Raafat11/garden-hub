<?php 
$pageTitle = 'Garden Hub - Grow Together';
require_once '../core/Security.php';
require_once '../config/Database.php';

$stats = [];
if(Security::isLoggedIn()) {
    $db = Database::getInstance()->getConnection();
    $stats['plots'] = $db->query("SELECT COUNT(*) FROM plots WHERE status = 'available'")->fetchColumn();
    $stats['users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['tools'] = $db->query("SELECT COUNT(*) FROM tools WHERE status = 'available'")->fetchColumn();
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #2D6A4F;
            --secondary: #95D5B2;
            --accent: #FFB703;
            --dark: #1D3557;
        }
        body { background: #F1FAEE; font-family: 'Segoe UI', sans-serif; }
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
        .hero { 
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 100px 0;
        }
        .stat-card { border-left: 4px solid var(--primary); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/garden-hub/public/index.php">
        <i class="bi bi-flower1"></i> Garden Hub
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if(Security::isLoggedIn()): ?>
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

<?php if(isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
<div class="container mt-3">
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> 
        <strong>Unauthorized!</strong> You don't have permission to access this page. Please make sure you're logged in with the correct account.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<div class="hero text-center">
    <div class="container">
        <h1 class="display-2 fw-bold mb-3">Garden Hub</h1>
        <p class="lead fs-4 mb-4">Smart community platform for garden management, crop trading, and volunteering</p>
        <?php if(!Security::isLoggedIn()): ?>
            <a href="register.php" class="btn btn-garden btn-lg me-2">
                <i class="bi bi-person-plus"></i> Join Us Now
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Log In
            </a>
        <?php else: ?>
            <a href="/garden-hub/public/<?= $_SESSION['role'] ?>/dashboard.php" class="btn btn-garden btn-lg">
                <i class="bi bi-speedometer2"></i> Go to Dashboard
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if(Security::isLoggedIn()): ?>
<div class="container my-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-glass stat-card text-center">
                <div class="card-body">
                    <i class="bi bi-house-gear display-4" style="color:#2D6A4F;"></i>
                    <h3 class="mt-2"><?= $stats['plots'] ?></h3>
                    <p class="text-muted mb-0">Available Plots</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass stat-card text-center">
                <div class="card-body">
                    <i class="bi bi-people-fill display-4" style="color:#2D6A4F;"></i>
                    <h3 class="mt-2"><?= $stats['users'] ?></h3>
                    <p class="text-muted mb-0">Community Members</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass stat-card text-center">
                <div class="card-body">
                    <i class="bi bi-tools display-4" style="color:#2D6A4F;"></i>
                    <h3 class="mt-2"><?= $stats['tools'] ?></h3>
                    <p class="text-muted mb-0">Tools Available</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container my-5">
    <h2 class="text-center mb-5" style="color:#1D3557;">Why Garden Hub?</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-glass h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-house-gear display-3" style="color:#2D6A4F;"></i>
                    <h5 class="card-title mt-3">Plot Management</h5>
                    <p class="text-muted">Rent your plot and track crops with Crop Data and Automated Irrigation. Full rental system with payment confirmation workflow.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-arrow-left-right display-3" style="color:#2D6A4F;"></i>
                    <h5 class="card-title mt-3">Community Trade</h5>
                    <p class="text-muted">Produce Flash Trade, Gift Economy, and Seed Exchange. Trade seeds and crops with the community.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-shield-check display-3" style="color:#2D6A4F;"></i>
                    <h5 class="card-title mt-3">Secure & Monitored</h5>
                    <p class="text-muted">Warden and Garden Manager oversight with 24/7 Incident Reporting. Full RBAC system and high security.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-calendar-check display-3" style="color:#2D6A4F;"></i>
                    <h5 class="card-title mt-3">Volunteer System</h5>
                    <p class="text-muted">Communal Tasks, Shift Balancer, and Mentorship Pairing. Earn Community Points and Karma Points.</p>
                </div>
            </div>
        <div class="col-md-4">
            <div class="card card-glass h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-droplet-half display-3" style="color:#2D6A4F;"></i>
                    <h5 class="card-title mt-3">Smart Monitoring</h5>
                    <p class="text-muted">Weather Driven Alerts, Soil pH Tracking, and Seed Viability checks. Monitor your plot in detail.</p>
                </div>
            </div>
        <div class="col-md-4">
            <div class="card card-glass h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-recycle display-3" style="color:#2D6A4F;"></i>
                    <h5 class="card-title mt-3">Compost Program</h5>
                    <p class="text-muted">Compost Contribution and Allergy Guard. Contribute to the community and earn points.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="background:#fff; padding: 60px 0;">
    <div class="container">
        <h2 class="text-center mb-5" style="color:#1D3557;">How It Works</h2>
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="p-3">
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width:80px; height:80px; background:var(--secondary);">
                        <h3 class="mb-0" style="color:var(--dark);">1</h3>
                    </div>
                    <h5>Create Account</h5>
                    <p class="text-muted">Choose your role: Plot Owner, Volunteer, Warden...</p>
                </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width:80px; height:80px; background:var(--secondary);">
                        <h3 class="mb-0" style="color:var(--dark);">2</h3>
                    </div>
                    <h5>Rent or Volunteer</h5>
                    <p class="text-muted">Rent Plot with availability check or Join Shifts</p>
                </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width:80px; height:80px; background:var(--secondary);">
                        <h3 class="mb-0" style="color:var(--dark);">3</h3>
                    </div>
                    <h5>Grow & Trade</h5>
                    <p class="text-muted">Track Crop Data and participate in Seed Exchange</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width:80px; height:80px; background:var(--secondary);">
                        <h3 class="mb-0" style="color:var(--dark);">4</h3>
                    </div>
                    <h5>Earn Points</h5>
                    <p class="text-muted">Complete Communal Tasks and get assigned points</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(!Security::isLoggedIn()): ?>
<div class="container my-5 text-center">
    <div class="card card-glass p-5">
        <h2 class="mb-3" style="color:#1D3557;">Ready to Join the Community?</h2>
        <p class="lead text-muted mb-4">Start your gardening journey today and become part of Garden Hub</p>
        <a href="register.php" class="btn btn-garden btn-lg">
            <i class="bi bi-person-plus"></i> Join Us Now - It's Free
        </a>
    </div>
</div>
<?php endif; ?>

<?php include '../templates/footer.php'; ?>