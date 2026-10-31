<?php
// Phase 3 vehicle management. All reads and writes are restricted to the signed-in user.
require 'includes/auth.php';
require_login();
require 'config/db.php';

$userId = current_user_id();
$error = '';
$successMessages = [
    'added' => 'Vehicle added successfully.',
    'updated' => 'Vehicle updated successfully.',
    'deleted' => 'Vehicle deleted successfully.',
];
$success = $successMessages[$_GET['status'] ?? ''] ?? '';
$editId = (int)($_GET['edit'] ?? 0);
$editVehicle = null;

function valid_vehicle_year(int $year): bool
{
    $maxYear = (int)date('Y') + 1;
    return $year >= 1900 && $year <= $maxYear;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        $error = 'The form expired. Please try again.';
    } elseif ($action === 'delete') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE id = ? AND user_id = ?');
        $stmt->execute([$vehicleId, $userId]);
        if (!$vehicleId || !$stmt->fetchColumn()) {
            $error = 'That vehicle could not be found.';
        } else {
            // Related maintenance records are removed by the database foreign-key cascade.
            $stmt = $pdo->prepare('DELETE FROM vehicles WHERE id = ? AND user_id = ?');
            $stmt->execute([$vehicleId, $userId]);
            header('Location: vehicles.php?status=deleted');
            exit;
        }
    } elseif (in_array($action, ['add', 'update'], true)) {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $year = (int)($_POST['year'] ?? 0);
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $vin = strtoupper(trim($_POST['vin'] ?? ''));
        $mileage = filter_var($_POST['current_mileage'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($action === 'update') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE id = ? AND user_id = ?');
            $stmt->execute([$vehicleId, $userId]);
            if (!$vehicleId || !$stmt->fetchColumn()) {
                $error = 'That vehicle could not be found.';
            }
        }

        if ($error === '' && !valid_vehicle_year($year)) {
            $error = 'Enter a valid vehicle year.';
        } elseif ($error === '' && ($make === '' || strlen($make) > 50)) {
            $error = 'Enter a vehicle make using 50 characters or fewer.';
        } elseif ($error === '' && ($model === '' || strlen($model) > 50)) {
            $error = 'Enter a vehicle model using 50 characters or fewer.';
        } elseif ($error === '' && $vin !== '' && strlen($vin) !== 17) {
            $error = 'VIN must be blank or exactly 17 characters.';
        } elseif ($error === '' && $mileage === false) {
            $error = 'Mileage must be zero or greater.';
        } elseif ($error === '') {
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO vehicles (user_id, year, make, model, vin, current_mileage) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $year, $make, $model, $vin !== '' ? $vin : null, $mileage]);
                $status = 'added';
            } else {
                $stmt = $pdo->prepare('UPDATE vehicles SET year = ?, make = ?, model = ?, vin = ?, current_mileage = ? WHERE id = ? AND user_id = ?');
                $stmt->execute([$year, $make, $model, $vin !== '' ? $vin : null, $mileage, $vehicleId, $userId]);
                $status = 'updated';
            }
            header('Location: vehicles.php?status=' . $status);
            exit;
        }
    }
}

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, year, make, model, vin, current_mileage FROM vehicles WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$editId, $userId]);
    $editVehicle = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT id, year, make, model, vin, current_mileage FROM vehicles WHERE user_id = ? ORDER BY year DESC, make, model');
$stmt->execute([$userId]);
$vehicles = $stmt->fetchAll();

$pageTitle = 'Vehicles | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<div class="page-heading"><div><h1>My Vehicles</h1><p class="muted">Add, edit, and delete vehicles connected to your account.</p></div></div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<section class="card form-section">
    <h2><?= $editVehicle ? 'Edit Vehicle' : 'Add Vehicle' ?></h2>
    <form method="post" action="vehicles.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $editVehicle ? 'update' : 'add' ?>">
        <?php if ($editVehicle): ?><input type="hidden" name="vehicle_id" value="<?= (int)$editVehicle['id'] ?>"><?php endif; ?>
        <div class="form-grid">
            <div><label for="year">Year</label><input id="year" name="year" type="number" min="1900" max="<?= (int)date('Y') + 1 ?>" value="<?= e((string)($editVehicle['year'] ?? '')) ?>" required></div>
            <div><label for="make">Make</label><input id="make" name="make" maxlength="50" value="<?= e($editVehicle['make'] ?? '') ?>" required></div>
            <div><label for="model">Model</label><input id="model" name="model" maxlength="50" value="<?= e($editVehicle['model'] ?? '') ?>" required></div>
            <div><label for="vin">VIN</label><input id="vin" name="vin" maxlength="17" value="<?= e($editVehicle['vin'] ?? '') ?>" placeholder="Optional 17-character VIN"></div>
            <div><label for="current_mileage">Current Mileage</label><input id="current_mileage" name="current_mileage" type="number" min="0" value="<?= e((string)($editVehicle['current_mileage'] ?? '0')) ?>" required></div>
        </div>
        <p class="button-row"><button class="btn" type="submit"><?= $editVehicle ? 'Save Vehicle' : 'Add Vehicle' ?></button><?php if ($editVehicle): ?><a class="btn btn-secondary" href="vehicles.php">Cancel</a><?php endif; ?></p>
    </form>
</section>

<div class="table-wrap"><table>
<thead><tr><th>Year</th><th>Make</th><th>Model</th><th>VIN</th><th>Mileage</th><th>Actions</th></tr></thead>
<tbody>
<?php if (!$vehicles): ?>
<tr><td colspan="6">No vehicles are available yet.</td></tr>
<?php else: foreach ($vehicles as $vehicle): ?>
<tr>
<td><?= e((string)$vehicle['year']) ?></td><td><?= e($vehicle['make']) ?></td><td><?= e($vehicle['model']) ?></td><td><?= e($vehicle['vin']) ?></td><td><?= number_format((int)$vehicle['current_mileage']) ?></td>
<td class="actions"><a class="btn btn-small btn-secondary" href="vehicles.php?edit=<?= (int)$vehicle['id'] ?>">Edit</a><form method="post" action="vehicles.php" onsubmit="return confirm('Delete this vehicle and its maintenance records?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="vehicle_id" value="<?= (int)$vehicle['id'] ?>"><button class="btn btn-small btn-danger" type="submit">Delete</button></form></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php require 'includes/footer.php'; ?>
