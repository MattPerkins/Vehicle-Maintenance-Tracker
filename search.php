<?php
// Week 3 search feature. Users can search only records belonging to their account.
require 'includes/auth.php';
require_login();
require 'config/db.php';

$query = trim($_GET['q'] ?? '');
$records = [];
$searched = false;

if ($query !== '') {
    $searched = true;

    // Convert a user-friendly * wildcard to SQL's % wildcard.
    // If the user does not enter any wildcard, perform a partial "contains" search.
    $pattern = str_replace('*', '%', $query);
    if (!str_contains($pattern, '%') && !str_contains($pattern, '_')) {
        $pattern = '%' . $pattern . '%';
    }

    $sql = 'SELECT mr.service_date, mr.mileage, mr.cost, mr.notes,
                   v.year, v.make, v.model, v.vin,
                   mt.name AS maintenance_type,
                   COALESCE(sl.name, "Not listed") AS service_location
            FROM maintenance_records mr
            JOIN vehicles v ON v.id = mr.vehicle_id
            JOIN maintenance_types mt ON mt.id = mr.maintenance_type_id
            LEFT JOIN service_locations sl ON sl.id = mr.service_location_id
            WHERE v.user_id = ?
              AND (
                    CAST(v.year AS CHAR) LIKE ? OR
                    v.make LIKE ? OR
                    v.model LIKE ? OR
                    COALESCE(v.vin, "") LIKE ? OR
                    mt.name LIKE ? OR
                    COALESCE(sl.name, "") LIKE ? OR
                    COALESCE(mr.notes, "") LIKE ? OR
                    CAST(mr.service_date AS CHAR) LIKE ?
                  )
            ORDER BY mr.service_date DESC, mr.id DESC';

    // The query uses a prepared statement even when wildcard characters are used.
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        current_user_id(),
        $pattern, $pattern, $pattern, $pattern,
        $pattern, $pattern, $pattern, $pattern,
    ]);
    $records = $stmt->fetchAll();
}

$pageTitle = 'Search | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Search Maintenance Records</h1>
        <p class="muted">Search by vehicle year/make/model/VIN, service type, location, notes, or service date.</p>
    </div>
</div>

<section class="card search-card">
    <form method="get" action="search.php">
        <label for="q">Search</label>
        <div class="search-row">
            <input id="q" name="q" value="<?= e($query) ?>" placeholder="Example: Ford, Oil Change, *brake*, 2026-09" required>
            <button class="btn" type="submit">Search</button>
        </div>
        <p class="help-text">Use <strong>*</strong> as a wildcard. Example: <strong>*oil*</strong> returns records containing “oil.” Normal searches automatically look for partial matches.</p>
    </form>
</section>

<?php if ($searched): ?>
    <div class="page-heading results-heading">
        <h2>Search Results</h2>
        <span class="badge"><?= count($records) ?> result<?= count($records) === 1 ? '' : 's' ?></span>
    </div>

    <div class="table-wrap"><table>
        <thead><tr><th>Date</th><th>Vehicle</th><th>Service</th><th>Mileage</th><th>Cost</th><th>Location</th><th>Notes</th></tr></thead>
        <tbody>
        <?php if (!$records): ?>
            <tr><td colspan="7">No records matched your search.</td></tr>
        <?php else: foreach ($records as $record): ?>
            <tr>
                <td><?= e($record['service_date']) ?></td>
                <td><?= e($record['year'] . ' ' . $record['make'] . ' ' . $record['model']) ?></td>
                <td><?= e($record['maintenance_type']) ?></td>
                <td><?= number_format((int)$record['mileage']) ?></td>
                <td>$<?= number_format((float)$record['cost'], 2) ?></td>
                <td><?= e($record['service_location']) ?></td>
                <td><?= e($record['notes']) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
