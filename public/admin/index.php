<?php

require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Admin Dashboard';

$totalUsers = (int) $pdo->query("SELECT COUNT(*) AS total FROM users")->fetch()['total'];
$totalLabs = (int) $pdo->query("SELECT COUNT(*) AS total FROM laboratories")->fetch()['total'];
$totalStations = (int) $pdo->query("SELECT COUNT(*) AS total FROM workstations")->fetch()['total'];
$totalActiveReservations = (int) $pdo->query("
    SELECT COUNT(*) AS total
    FROM reservations
    WHERE status = 'active'
")->fetch()['total'];

$stmt = $pdo->query("
    SELECT
        r.reservation_id,
        r.start_time,
        r.end_time,
        r.status,
        CONCAT(u.first_name, ' ', u.last_name) AS user_full_name,
        l.lab_name,
        w.station_code,
        w.station_name
    FROM reservations r
    INNER JOIN users u
        ON r.user_id = u.user_id
    INNER JOIN laboratories l
        ON r.lab_id = l.lab_id
    INNER JOIN workstations w
        ON r.station_id = w.station_id
    ORDER BY r.created_at DESC
    LIMIT 5
");

$latestReservations = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';

?>

<h1>Admin Dashboard</h1>

<p>
    Welcome, <?= htmlspecialchars(getCurrentUserName()) ?>.
</p>

<p>
    Your role: <?= htmlspecialchars($_SESSION['role_name']) ?>
</p>

<h2>System Summary</h2>

<ul>
    <li>Total users: <?= $totalUsers ?></li>
    <li>Total laboratories: <?= $totalLabs ?></li>
    <li>Total stations: <?= $totalStations ?></li>
    <li>Active reservations: <?= $totalActiveReservations ?></li>
</ul>

<h2>Latest Reservations</h2>

<?php if (count($latestReservations) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Laboratory</th>
                <th>Station</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($latestReservations as $reservation): ?>
                <tr>
                    <td><?= (int) $reservation['reservation_id'] ?></td>
                    <td><?= htmlspecialchars($reservation['user_full_name']) ?></td>
                    <td><?= htmlspecialchars($reservation['lab_name']) ?></td>
                    <td><?= htmlspecialchars($reservation['station_code'] . ' - ' . $reservation['station_name']) ?></td>
                    <td><?= htmlspecialchars($reservation['start_time']) ?></td>
                    <td><?= htmlspecialchars($reservation['end_time']) ?></td>
                    <td><?= htmlspecialchars($reservation['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No reservation found.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>