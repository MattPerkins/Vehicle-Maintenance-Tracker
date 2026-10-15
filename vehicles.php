<?php
require 'includes/auth.php';
require_login();
require 'config/db.php';

$stmt = $pdo->prepare('SELECT year, make, model, vin, current_mileage FROM vehicles WHERE user_id = ? ORDER BY year DESC, make, model');
$stmt->execute([current_user_id()]);
$vehicles = $stmt->fetchAll();

$pageTitle = 'Vehicles | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>My Vehicles</h1><p class="muted">Vehicles currently connected to your account.</p></div></div>
<div class="table-wrap"><table>
<thead><tr><th>Year</th><th>Make</th><th>Model</th><th>VIN</th><th>Mileage</th></tr></thead>
<tbody>
<?php if (!$vehicles): ?>
<tr><td colspan="5">No vehicles are available yet.</td></tr>
<?php else: foreach ($vehicles as $vehicle): ?>
<tr>
<td><?= e((string)$vehicle['year']) ?></td><td><?= e($vehicle['make']) ?></td><td><?= e($vehicle['model']) ?></td><td><?= e($vehicle['vin']) ?></td><td><?= number_format((int)$vehicle['current_mileage']) ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php require 'includes/footer.php'; ?>
