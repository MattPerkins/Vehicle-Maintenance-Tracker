<?php
// Dashboard summary for the currently authenticated user.
require 'includes/auth.php';
require_login();
require 'config/db.php';

$userId = current_user_id();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE user_id = ?');
$stmt->execute([$userId]);
$vehicleCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM maintenance_records mr JOIN vehicles v ON v.id = mr.vehicle_id WHERE v.user_id = ?');
$stmt->execute([$userId]);
$maintenanceCount = (int)$stmt->fetchColumn();

$pageTitle = 'Dashboard | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Dashboard</h1><p class="muted">Welcome, <?= e($_SESSION['user_name'] ?? 'User') ?>.</p></div></div>
<section class="grid">
    <div class="card"><div class="stat"><?= $vehicleCount ?></div><strong>Vehicles</strong><p><a href="vehicles.php">View vehicle list</a></p></div>
    <div class="card"><div class="stat"><?= $maintenanceCount ?></div><strong>Maintenance Records</strong><p><a href="maintenance.php">View maintenance history</a></p></div>
    <div class="card"><h3>Phase 3 Status</h3><p>Search, registration, password security, and full add/edit/delete management for vehicles and maintenance records are now included.</p><p><a href="search.php">Search and manage records</a></p></div>
</section>
<?php require 'includes/footer.php'; ?>
