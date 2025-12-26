<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php'; // make sure this returns a PDO connection
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/nepali_date.php';
require_once __DIR__ . '/../auth/session.php';

// Check if user is logged in for protected pages
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'login.php' && !isLoggedIn()) {
    redirect('../auth/login.php');
}

$pageTitle = $pageTitle ?? 'FarmSaathi';
$currentModule = $currentModule ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/modern-theme.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="<?php echo asset('images/logo.jpg'); ?>" alt="FarmSaathi Logo" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <span class="logo-fallback" style="display:none;">🌾</span>
                    <span class="logo-text">FarmSaathi</span>
                </div>
                <?php if (isLoggedIn()): ?>
                <div class="user-info">
                    <span class="username">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="user-role">(<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?php echo url('admin/users/add.php'); ?>" class="btn btn-secondary">+ User</a>
                    <?php endif; ?>
                    <a href="<?php echo url('auth/logout.php'); ?>" class="btn btn-logout">Logout</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if (isLoggedIn()): ?>
    <nav class="main-nav">
        <div class="container">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="<?php echo url('dashboard/index.php'); ?>" class="<?php echo $currentModule === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?php echo url('admin/users/index.php'); ?>" class="<?php echo $currentModule === 'users' ? 'active' : ''; ?>">User Management</a></li>
                    <li><a href="<?php echo url('admin/activity/index.php'); ?>" class="<?php echo $currentModule === 'activity' ? 'active' : ''; ?>">User Activity</a></li>
                    <li><a href="<?php echo url('reports/index.php'); ?>" class="<?php echo $currentModule === 'reports' ? 'active' : ''; ?>">Reports</a></li>
                <?php else: ?>
                    <li><a href="<?php echo url('crops/index.php'); ?>" class="<?php echo $currentModule === 'crops' ? 'active' : ''; ?>">🌾 Crops</a></li>
                    <li><a href="<?php echo url('livestock/index.php'); ?>" class="<?php echo $currentModule === 'livestock' ? 'active' : ''; ?>">🐄 Livestock</a></li>
                    <li><a href="<?php echo url('inventory/index.php'); ?>" class="<?php echo $currentModule === 'inventory' ? 'active' : ''; ?>">📦 Inventory</a></li>
                    <li><a href="<?php echo url('expenses/index.php'); ?>" class="<?php echo $currentModule === 'finance' ? 'active' : ''; ?>">💰 Finance</a></li>
                    <li><a href="<?php echo url('reports/index.php'); ?>" class="<?php echo $currentModule === 'reports' ? 'active' : ''; ?>">📊 Reports</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <?php endif; ?>

    <main class="main-content">
        <div class="container">
            <?php displayFlashMessage(); ?>
