<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';

$labId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$labId) {
    http_response_code(400);
    die('Invalid laboratory ID.');
}

$lab = getLabById($pdo, (int) $labId);

if (!$lab) {
    http_response_code(404);
    die('Laboratory not found.');
}

$stations = getStationsByLab($pdo, (int) $labId);
$equipmentSummary = getLabEquipmentSummary($pdo, (int) $labId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laboratory Detail - Laboratory Reservation System</title>
</head>
<body>

<h1>Laboratory Detail</h1>

<p>
    <a href="labs.php">Back to Laboratories</a> |
    <a href="dashboard.php">Dashboard</a> |
    <a href="logout.php">Logout</a>
</p>

<hr>

<h2><?= htmlspecialchars($lab['lab_name']) ?></h2>

<ul>
    <li>Code: <?= htmlspecialchars($lab['lab_code']) ?></li>
    <li>Type: <?= htmlspecialchars($lab['lab_type']) ?></li>
    <li>Faculty: <?= htmlspecialchars($lab['faculty_name']) ?></li>
    <li>Department: <?= htmlspecialchars($lab['department_name']) ?></li>
    <li>Location: <?= htmlspecialchars($lab['location'] ?? '-') ?></li>
    <li>Phone: <?= htmlspecialchars($lab['phone'] ?? '-') ?></li>
</ul>

<h3>Description</h3>

<p>
    <?= nl2br(htmlspecialchars($lab['description'] ?? 'No description available.')) ?>
</p>

<hr>

<h2>Equipment Summary</h2>

<?php if (count($equipmentSummary) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Equipment</th>
                <th>Category</th>
                <th>Total Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($equipmentSummary as $equipment): ?>
                <tr>
                    <td><?= htmlspecialchars($equipment['equipment_name']) ?></td>
                    <td><?= htmlspecialchars($equipment['category']) ?></td>
                    <td><?= (int) $equipment['total_count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No equipment found for this laboratory.</p>
<?php endif; ?>

<hr>

<h2>Stations</h2>

<?php if (count($stations) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Station Code</th>
                <th>Station Name</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Equipment Count</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stations as $station): ?>
                <tr>
                    <td><?= htmlspecialchars($station['station_code']) ?></td>
                    <td><?= htmlspecialchars($station['station_name']) ?></td>
                    <td><?= htmlspecialchars($station['type_name']) ?></td>
                    <td><?= (int) $station['capacity'] ?></td>
                    <td><?= htmlspecialchars($station['status']) ?></td>
                    <td><?= (int) $station['equipment_count'] ?></td>
                    <td>
                        <a href="station-detail.php?id=<?= (int) $station['station_id'] ?>">View Station</a>

                        <?php if ($station['status'] === 'active'): ?>
                            |
                            <a href="reserve.php?lab_id=<?= (int) $lab['lab_id'] ?>&station_id=<?= (int) $station['station_id'] ?>">
                                Reserve
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No station found for this laboratory.</p>
<?php endif; ?>

</body>
</html>