<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';

$pageTitle = 'My Reservations';

$userId = getCurrentUserId();

$statusFilter = $_GET['status'] ?? 'all';

if (!in_array($statusFilter, ['all', 'active', 'cancelled', 'completed'], true)) {
    $statusFilter = 'all';
}

$reservations = getUserReservations($pdo, (int) $userId, $statusFilter);

function isFutureReservation(string $startTime): bool
{
    return strtotime($startTime) > time();
}

require_once __DIR__ . '/../includes/header.php';

?>

<h1>My Reservations</h1>

<h2>Filter</h2>

<p>
    <a href="my-reservations.php?status=all">All</a> |
    <a href="my-reservations.php?status=active">Active</a> |
    <a href="my-reservations.php?status=cancelled">Cancelled</a> |
    <a href="my-reservations.php?status=completed">Completed</a>
</p>

<p>
    Current filter: <strong><?= htmlspecialchars($statusFilter) ?></strong>
</p>

<hr>

<?php if (count($reservations) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Laboratory</th>
                <th>Station</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Purpose</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td><?= (int) $reservation['reservation_id'] ?></td>

                    <td>
                        <?= htmlspecialchars($reservation['lab_code'] . ' - ' . $reservation['lab_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($reservation['station_code'] . ' - ' . $reservation['station_name']) ?>
                    </td>

                    <td><?= htmlspecialchars($reservation['start_time']) ?></td>

                    <td><?= htmlspecialchars($reservation['end_time']) ?></td>

                    <td><?= htmlspecialchars($reservation['status']) ?></td>

                    <td><?= htmlspecialchars($reservation['purpose'] ?? '-') ?></td>

                    <td>
                        <a href="reservation-detail.php?id=<?= (int) $reservation['reservation_id'] ?>">
                            View Details
                        </a>

                        <?php if ($reservation['status'] === 'active' && isFutureReservation($reservation['start_time'])): ?>
                            |
                            <span>Cancelable</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No reservation found.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>