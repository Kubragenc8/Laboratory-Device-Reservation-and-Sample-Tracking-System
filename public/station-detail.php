<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';

$stationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$stationId) {
    http_response_code(400);
    die('Invalid station ID.');
}

$station = getStationById($pdo, (int) $stationId);

if (!$station) {
    http_response_code(404);
    die('Station not found.');
}

$equipmentList = getStationEquipment($pdo, (int) $stationId);
$upcomingReservations = getUpcomingReservationsByStation($pdo, (int) $stationId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Station Detail - Laboratory Reservation System</title>
</head>
<body>

<h1>Station Detail</h1>

<p>
    <a href="lab-detail.php?id=<?= (int) $station['lab_id'] ?>">Back to Laboratory</a> |
    <a href="labs.php">Laboratories</a> |
    <a href="dashboard.php">Dashboard</a> |
    <a href="logout.php">Logout</a>
</p>

<hr>

<h2><?= htmlspecialchars($station['station_code'] . ' - ' . $station['station_name']) ?></h2>

<ul>
    <li>Laboratory: <?= htmlspecialchars($station['lab_name']) ?></li>
    <li>Laboratory Code: <?= htmlspecialchars($station['lab_code']) ?></li>
    <li>Laboratory Type: <?= htmlspecialchars($station['lab_type']) ?></li>
    <li>Faculty: <?= htmlspecialchars($station['faculty_name']) ?></li>
    <li>Department: <?= htmlspecialchars($station['department_name']) ?></li>
    <li>Location: <?= htmlspecialchars($station['location'] ?? '-') ?></li>
    <li>Station Type: <?= htmlspecialchars($station['type_name']) ?></li>
    <li>Capacity: <?= (int) $station['capacity'] ?></li>
    <li>Status: <?= htmlspecialchars($station['status']) ?></li>
</ul>

<h3>Notes</h3>

<p>
    <?= nl2br(htmlspecialchars($station['notes'] ?? 'No notes available.')) ?>
</p>

<?php if ($station['status'] === 'active'): ?>
    <p>
        <a href="reserve.php?lab_id=<?= (int) $station['lab_id'] ?>&station_id=<?= (int) $station['station_id'] ?>">
            Reserve This Station
        </a>
    </p>
<?php else: ?>
    <p>This station is not available for reservation.</p>
<?php endif; ?>

<hr>

<h2>Equipment in This Station</h2>

<?php if (count($equipmentList) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Asset Code</th>
                <th>Equipment</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($equipmentList as $equipment): ?>
                <tr>
                    <td><?= htmlspecialchars($equipment['asset_code']) ?></td>
                    <td><?= htmlspecialchars($equipment['equipment_name']) ?></td>
                    <td><?= htmlspecialchars($equipment['category']) ?></td>
                    <td><?= htmlspecialchars($equipment['brand'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($equipment['model'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($equipment['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No equipment found for this station.</p>
<?php endif; ?>

<hr>

<h2>Upcoming Active Reservations</h2>

<?php if (count($upcomingReservations) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Reservation ID</th>
                <th>User</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($upcomingReservations as $reservation): ?>
                <tr>
                    <td><?= (int) $reservation['reservation_id'] ?></td>
                    <td><?= htmlspecialchars($reservation['user_full_name']) ?></td>
                    <td><?= htmlspecialchars($reservation['start_time']) ?></td>
                    <td><?= htmlspecialchars($reservation['end_time']) ?></td>
                    <td><?= htmlspecialchars($reservation['status']) ?></td>
                    <td><?= htmlspecialchars($reservation['purpose'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No upcoming active reservation found for this station.</p>
<?php endif; ?>

</body>
</html>