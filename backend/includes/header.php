<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = $pageTitle ?? 'Dashboard';
$currentSection = $currentSection ?? '';
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$prefix = defined('KIDSTORE_ADMIN_URL_PREFIX') ? KIDSTORE_ADMIN_URL_PREFIX : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - Kid Store Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo $prefix; ?>assets/admin.css" />
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <h1><i class="fas fa-star"></i> Kid Store</h1>
        <div>Welcome, <?= htmlspecialchars($adminName) ?></div>
        <nav>
            <ul class="admin-nav">
                <li><a href="<?php echo $prefix; ?>pages/dashboard.php" class="<?= $currentSection === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="<?php echo $prefix; ?>pages/products.php" class="<?= $currentSection === 'products' ? 'active' : '' ?>"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="<?php echo $prefix; ?>pages/categories.php" class="<?= $currentSection === 'categories' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="<?php echo $prefix; ?>pages/orders.php" class="<?= $currentSection === 'orders' ? 'active' : '' ?>"><i class="fas fa-receipt"></i> Orders</a></li>
                <li><a href="<?php echo $prefix; ?>pages/customers.php" class="<?= $currentSection === 'customers' ? 'active' : '' ?>"><i class="fas fa-users"></i> Customers</a></li>
            </ul>
        </nav>
        <div>
            <a href="<?php echo $prefix; ?>auth/logout.php" class="button secondary" style="padding:8px 14px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    <main class="admin-main">
        <div class="admin-header">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
        </div>
