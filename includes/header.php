<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'Vehicle Maintenance Tracker';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-row">
        <a class="brand" href="<?= !empty($_SESSION['user_id']) ? 'dashboard.php' : 'index.php' ?>">Vehicle Maintenance Tracker</a>
        <nav>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="vehicles.php">Vehicles</a>
                <a href="maintenance.php">Maintenance</a>
                <a href="search.php">Search</a>
                <a href="service_locations.php">Locations</a>
                <a href="account.php">Account</a>
                <a href="logout.php">Log Out</a>
            <?php else: ?>
                <a href="index.php">Home</a>
                <a href="login.php">Log In</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
