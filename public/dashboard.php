<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = getCurrentUserId();

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS active_reservation_count
    FROM reservations
    WHERE user_id = :user_id
      AND status = 'active'
");

$stmt->execute([
    ':user_id' => $userId
]);

$activeReservationCount = (int) $stmt->fetch()['active_reservation_count'];

$stmt = $pdo->prepare("
    SELECT
        r.reservation_id,
        r.start_time,
        r.end_time,
        r.status,
        l.lab_name,
        w.station_code,
        w.station_name
    FROM reservations r
    INNER JOIN laboratories l
        ON r.lab_id = l.lab_id
    INNER JOIN workstations w
        ON r.station_id = w.station_id
    WHERE r.user_id = :user_id
      AND r.status = 'active'
      AND r.start_time >= NOW()
    ORDER BY r.start_time ASC
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $userId
]);

$nextReservation = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Laboratory Reservation System</title>
</head>
<body>

<h1>User Dashboard</h1>

<p>
    Welcome, <?= htmlspecialchars(getCurrentUserName()) ?>.
</p>

<p>
    Your role: <?= htmlspecialchars($_SESSION['role_name']) ?>
</p>

<p>
    Active reservation count: <?= $activeReservationCount ?>
</p>

<?php if ($nextReservation): ?>
    <h2>Next Reservation</h2>

    <ul>
        <li>Reservation ID: <?= (int) $nextReservation['reservation_id'] ?></li>
        <li>Laboratory: <?= htmlspecialchars($nextReservation['lab_name']) ?></li>
        <li>Station: <?= htmlspecialchars($nextReservation['station_code'] . ' - ' . $nextReservation['station_name']) ?></li>
        <li>Start Time: <?= htmlspecialchars($nextReservation['start_time']) ?></li>
        <li>End Time: <?= htmlspecialchars($nextReservation['end_time']) ?></li>
        <li>Status: <?= htmlspecialchars($nextReservation['status']) ?></li>
    </ul>
<?php else: ?>
    <p>No upcoming active reservation found.</p>
<?php endif; ?>

<hr>

<h2>Session Debug</h2>

<pre><?php print_r($_SESSION); ?></pre>

<hr>

<p>
    <a href="labs.php">View Laboratories</a>
</p>

<p>
    <a href="my-reservations.php">My Reservations</a>
</p>

<p>
    <a href="logout.php">Logout</a>
</p>

</body>
</html>