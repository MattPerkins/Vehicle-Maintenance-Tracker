<?php
require 'includes/auth.php';
require_login();
require 'config/db.php';

$sql = 'SELECT mr.service_date, mr.mileage, mr.cost, mr.notes,
               CONCAT(v.year, " ", v.make, " ", v.model) AS vehicle_name,
               mt.name AS maintenance_type,
               sl.name AS service_location
        FROM maintenance_records mr
        JOIN vehicles v ON v.id = mr.vehicle_id
        JOIN maintenance_types mt ON mt.id = mr.maintenance_type_id
        LEFT JOIN service_locations sl ON sl.id = mr.service_location_id
        WHERE v.user_id = ?
        ORDER BY mr.service_date DESC, mr.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([current_user_id()]);
$records = $stmt->fetchAll();

$pageTitle = 'Maintenance | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>Maintenance History</h1><p class="muted">Service records for vehicles on your account.</p></div></div>
<div class="table-wrap"><table>
<thead><tr><th>Date</th><th>Vehicle</th><th>Service</th><th>Mileage</th><th>Cost</th><th>Location</th><th>Notes</th></tr></thead>
<tbody>
<?php if (!$records): ?>
<tr><td colspan="7">No maintenance records are available yet.</td></tr>
<?php else: foreach ($records as $record): ?>
<tr>
<td><?= e($record['service_date']) ?></td><td><?= e($record['vehicle_name']) ?></td><td><?= e($record['maintenance_type']) ?></td><td><?= number_format((int)$record['mileage']) ?></td><td>$<?= number_format((float)$record['cost'], 2) ?></td><td><?= e($record['service_location'] ?? 'Not listed') ?></td><td><?= e($record['notes']) ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php require 'includes/footer.php'; ?>
