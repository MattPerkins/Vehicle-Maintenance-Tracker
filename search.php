<?php
// Phase 3 search and maintenance-record management page.
// Users may search, add, edit, and delete only records connected to vehicles they own.
require 'includes/auth.php';
require_login();
require 'config/db.php';

$userId = current_user_id();
$error = '';
$successMessages = [
    'added' => 'Maintenance record added successfully.',
    'updated' => 'Maintenance record updated successfully.',
    'deleted' => 'Maintenance record deleted successfully.',
];
$success = $successMessages[$_GET['status'] ?? ''] ?? '';
$query = trim($_GET['q'] ?? $_POST['return_q'] ?? '');
$editId = (int)($_GET['edit'] ?? 0);
$editRecord = null;

// Load choices for the add/edit forms. Vehicle choices are restricted to the current user.
$stmt = $pdo->prepare('SELECT id, year, make, model FROM vehicles WHERE user_id = ? ORDER BY year DESC, make, model');
$stmt->execute([$userId]);
$vehicles = $stmt->fetchAll();
$maintenanceTypes = $pdo->query('SELECT id, name FROM maintenance_types ORDER BY name')->fetchAll();
$serviceLocations = $pdo->query('SELECT id, name FROM service_locations ORDER BY name')->fetchAll();

function vehicle_belongs_to_user(PDO $pdo, int $vehicleId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE id = ? AND user_id = ?');
    $stmt->execute([$vehicleId, $userId]);
    return (bool)$stmt->fetchColumn();
}

function maintenance_record_belongs_to_user(PDO $pdo, int $recordId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM maintenance_records mr JOIN vehicles v ON v.id = mr.vehicle_id WHERE mr.id = ? AND v.user_id = ?');
    $stmt->execute([$recordId, $userId]);
    return (bool)$stmt->fetchColumn();
}

function valid_lookup_id(PDO $pdo, string $table, int $id): bool
{
    // Table names are fixed by the application and are never supplied by the user.
    if (!in_array($table, ['maintenance_types', 'service_locations'], true)) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    return (bool)$stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        $error = 'The form expired. Please try again.';
    } elseif ($action === 'delete') {
        $recordId = (int)($_POST['record_id'] ?? 0);
        if (!$recordId || !maintenance_record_belongs_to_user($pdo, $recordId, $userId)) {
            $error = 'That maintenance record could not be found.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM maintenance_records WHERE id = ?');
            $stmt->execute([$recordId]);
            header('Location: search.php?q=' . urlencode($query) . '&status=deleted');
            exit;
        }
    } elseif (in_array($action, ['add', 'update'], true)) {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $maintenanceTypeId = (int)($_POST['maintenance_type_id'] ?? 0);
        $serviceLocationRaw = $_POST['service_location_id'] ?? '';
        $serviceLocationId = $serviceLocationRaw === '' ? null : (int)$serviceLocationRaw;
        $serviceDate = trim($_POST['service_date'] ?? '');
        $mileage = filter_var($_POST['mileage'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $cost = filter_var($_POST['cost'] ?? null, FILTER_VALIDATE_FLOAT);
        $notes = trim($_POST['notes'] ?? '');
        $dateObj = DateTime::createFromFormat('Y-m-d', $serviceDate);
        $validDate = $dateObj && $dateObj->format('Y-m-d') === $serviceDate;

        if ($action === 'update' && (!$recordId || !maintenance_record_belongs_to_user($pdo, $recordId, $userId))) {
            $error = 'That maintenance record could not be found.';
        } elseif (!$vehicleId || !vehicle_belongs_to_user($pdo, $vehicleId, $userId)) {
            $error = 'Choose a valid vehicle from your account.';
        } elseif (!$maintenanceTypeId || !valid_lookup_id($pdo, 'maintenance_types', $maintenanceTypeId)) {
            $error = 'Choose a valid maintenance type.';
        } elseif ($serviceLocationId !== null && !valid_lookup_id($pdo, 'service_locations', $serviceLocationId)) {
            $error = 'Choose a valid service location.';
        } elseif (!$validDate) {
            $error = 'Enter a valid service date.';
        } elseif ($mileage === false) {
            $error = 'Mileage must be zero or greater.';
        } elseif ($cost === false || $cost < 0) {
            $error = 'Cost must be zero or greater.';
        } elseif (strlen($notes) > 2000) {
            $error = 'Notes must be 2,000 characters or fewer.';
        } else {
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO maintenance_records (vehicle_id, maintenance_type_id, service_location_id, service_date, mileage, cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$vehicleId, $maintenanceTypeId, $serviceLocationId, $serviceDate, $mileage, $cost, $notes]);
                $status = 'added';
            } else {
                // Ownership was checked above before updating the record.
                $stmt = $pdo->prepare('UPDATE maintenance_records SET vehicle_id = ?, maintenance_type_id = ?, service_location_id = ?, service_date = ?, mileage = ?, cost = ?, notes = ? WHERE id = ?');
                $stmt->execute([$vehicleId, $maintenanceTypeId, $serviceLocationId, $serviceDate, $mileage, $cost, $notes, $recordId]);
                $status = 'updated';
            }
            header('Location: search.php?q=' . urlencode($query) . '&status=' . $status);
            exit;
        }
    }
}

// Load the selected record for editing only after verifying ownership.
if ($editId > 0 && maintenance_record_belongs_to_user($pdo, $editId, $userId)) {
    $stmt = $pdo->prepare('SELECT mr.* FROM maintenance_records mr JOIN vehicles v ON v.id = mr.vehicle_id WHERE mr.id = ? AND v.user_id = ? LIMIT 1');
    $stmt->execute([$editId, $userId]);
    $editRecord = $stmt->fetch();
}

$records = [];
$searched = false;
if ($query !== '') {
    $searched = true;
    // Convert a user-friendly * wildcard to SQL's % wildcard. Normal searches use contains matching.
    $pattern = str_replace('*', '%', $query);
    if (!str_contains($pattern, '%') && !str_contains($pattern, '_')) {
        $pattern = '%' . $pattern . '%';
    }

    $sql = 'SELECT mr.id, mr.service_date, mr.mileage, mr.cost, mr.notes,
                   v.year, v.make, v.model, v.vin,
                   mt.name AS maintenance_type,
                   COALESCE(sl.name, "Not listed") AS service_location
            FROM maintenance_records mr
            JOIN vehicles v ON v.id = mr.vehicle_id
            JOIN maintenance_types mt ON mt.id = mr.maintenance_type_id
            LEFT JOIN service_locations sl ON sl.id = mr.service_location_id
            WHERE v.user_id = ?
              AND (
                    CAST(v.year AS CHAR) LIKE ? OR v.make LIKE ? OR v.model LIKE ? OR
                    COALESCE(v.vin, "") LIKE ? OR mt.name LIKE ? OR COALESCE(sl.name, "") LIKE ? OR
                    COALESCE(mr.notes, "") LIKE ? OR CAST(mr.service_date AS CHAR) LIKE ?
                  )
            ORDER BY mr.service_date DESC, mr.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern]);
    $records = $stmt->fetchAll();
}

$pageTitle = 'Search & Manage | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Search & Manage Maintenance Records</h1>
        <p class="muted">Search records, then add, edit, or delete maintenance information from this page.</p>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<section class="card search-card">
    <form method="get" action="search.php">
        <label for="q">Search</label>
        <div class="search-row">
            <input id="q" name="q" value="<?= e($query) ?>" placeholder="Example: Ford, Oil Change, *brake*, 2026-09" required>
            <button class="btn" type="submit">Search</button>
        </div>
        <p class="help-text">Use <strong>*</strong> as a wildcard. Normal searches automatically look for partial matches.</p>
    </form>
</section>

<?php if ($editRecord): ?>
<section class="card form-section">
    <div class="page-heading"><div><h2>Edit Maintenance Record</h2><p class="muted">Save the form to immediately update the database record.</p></div><a class="btn btn-secondary" href="search.php?q=<?= urlencode($query) ?>">Cancel Edit</a></div>
    <form method="post" action="search.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="record_id" value="<?= (int)$editRecord['id'] ?>">
        <input type="hidden" name="return_q" value="<?= e($query) ?>">
        <div class="form-grid">
            <div><label for="edit_vehicle_id">Vehicle</label><select id="edit_vehicle_id" name="vehicle_id" required><?php foreach ($vehicles as $vehicle): ?><option value="<?= (int)$vehicle['id'] ?>" <?= (int)$editRecord['vehicle_id'] === (int)$vehicle['id'] ? 'selected' : '' ?>><?= e($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']) ?></option><?php endforeach; ?></select></div>
            <div><label for="edit_type_id">Maintenance Type</label><select id="edit_type_id" name="maintenance_type_id" required><?php foreach ($maintenanceTypes as $type): ?><option value="<?= (int)$type['id'] ?>" <?= (int)$editRecord['maintenance_type_id'] === (int)$type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option><?php endforeach; ?></select></div>
            <div><label for="edit_location_id">Service Location</label><select id="edit_location_id" name="service_location_id"><option value="">Not listed</option><?php foreach ($serviceLocations as $location): ?><option value="<?= (int)$location['id'] ?>" <?= (int)($editRecord['service_location_id'] ?? 0) === (int)$location['id'] ? 'selected' : '' ?>><?= e($location['name']) ?></option><?php endforeach; ?></select></div>
            <div><label for="edit_date">Service Date</label><input id="edit_date" name="service_date" type="date" value="<?= e($editRecord['service_date']) ?>" required></div>
            <div><label for="edit_mileage">Mileage</label><input id="edit_mileage" name="mileage" type="number" min="0" value="<?= (int)$editRecord['mileage'] ?>" required></div>
            <div><label for="edit_cost">Cost</label><input id="edit_cost" name="cost" type="number" min="0" step="0.01" value="<?= e((string)$editRecord['cost']) ?>" required></div>
        </div>
        <label for="edit_notes">Notes</label><textarea id="edit_notes" name="notes" rows="3" maxlength="2000"><?= e($editRecord['notes']) ?></textarea>
        <p><button class="btn" type="submit">Save Changes</button></p>
    </form>
</section>
<?php else: ?>
<section class="card form-section">
    <h2>Add Maintenance Record</h2>
    <p class="muted">Add a new service record. The new entry will appear in search results immediately.</p>
    <?php if (!$vehicles): ?>
        <div class="alert alert-error">Add a vehicle before creating a maintenance record. <a href="vehicles.php">Go to Vehicles</a>.</div>
    <?php else: ?>
    <form method="post" action="search.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="return_q" value="<?= e($query) ?>">
        <div class="form-grid">
            <div><label for="vehicle_id">Vehicle</label><select id="vehicle_id" name="vehicle_id" required><?php foreach ($vehicles as $vehicle): ?><option value="<?= (int)$vehicle['id'] ?>"><?= e($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']) ?></option><?php endforeach; ?></select></div>
            <div><label for="maintenance_type_id">Maintenance Type</label><select id="maintenance_type_id" name="maintenance_type_id" required><?php foreach ($maintenanceTypes as $type): ?><option value="<?= (int)$type['id'] ?>"><?= e($type['name']) ?></option><?php endforeach; ?></select></div>
            <div><label for="service_location_id">Service Location</label><select id="service_location_id" name="service_location_id"><option value="">Not listed</option><?php foreach ($serviceLocations as $location): ?><option value="<?= (int)$location['id'] ?>"><?= e($location['name']) ?></option><?php endforeach; ?></select></div>
            <div><label for="service_date">Service Date</label><input id="service_date" name="service_date" type="date" required></div>
            <div><label for="mileage">Mileage</label><input id="mileage" name="mileage" type="number" min="0" required></div>
            <div><label for="cost">Cost</label><input id="cost" name="cost" type="number" min="0" step="0.01" value="0.00" required></div>
        </div>
        <label for="notes">Notes</label><textarea id="notes" name="notes" rows="3" maxlength="2000"></textarea>
        <p><button class="btn" type="submit">Add Record</button></p>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($searched): ?>
    <div class="page-heading results-heading"><h2>Search Results</h2><span class="badge"><?= count($records) ?> result<?= count($records) === 1 ? '' : 's' ?></span></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Date</th><th>Vehicle</th><th>Service</th><th>Mileage</th><th>Cost</th><th>Location</th><th>Notes</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!$records): ?>
            <tr><td colspan="8">No records matched your search.</td></tr>
        <?php else: foreach ($records as $record): ?>
            <tr>
                <td><?= e($record['service_date']) ?></td>
                <td><?= e($record['year'] . ' ' . $record['make'] . ' ' . $record['model']) ?></td>
                <td><?= e($record['maintenance_type']) ?></td>
                <td><?= number_format((int)$record['mileage']) ?></td>
                <td>$<?= number_format((float)$record['cost'], 2) ?></td>
                <td><?= e($record['service_location']) ?></td>
                <td><?= e($record['notes']) ?></td>
                <td class="actions">
                    <a class="btn btn-small btn-secondary" href="search.php?q=<?= urlencode($query) ?>&edit=<?= (int)$record['id'] ?>">Edit</a>
                    <form method="post" action="search.php" onsubmit="return confirm('Delete this maintenance record?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="record_id" value="<?= (int)$record['id'] ?>">
                        <input type="hidden" name="return_q" value="<?= e($query) ?>">
                        <button class="btn btn-small btn-danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
