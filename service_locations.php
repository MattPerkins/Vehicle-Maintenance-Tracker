<?php
require 'includes/auth.php';
require_login();
require 'config/db.php';
$locations = $pdo->query('SELECT name, address, city, state, phone FROM service_locations ORDER BY name')->fetchAll();
$pageTitle = 'Service Locations | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Service Locations</h1><p class="muted">Shops and service locations referenced by maintenance records.</p></div></div>
<div class="grid">
<?php foreach ($locations as $location): ?>
<div class="card"><h3><?= e($location['name']) ?></h3><p><?= e($location['address']) ?><br><?= e($location['city']) ?>, <?= e($location['state']) ?></p><p class="muted"><?= e($location['phone']) ?></p></div>
<?php endforeach; ?>
</div>
<?php require 'includes/footer.php'; ?>
