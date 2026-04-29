<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';

$pageTitle = 'Reservation Detail';

$userId = getCurrentUserId();

$reservationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$reservationId) {
    http_response_code(400);
    die('Invalid reservation ID.');
}

$reservation = getReservationDetail($pdo, (int) $reservationId);

if (!$reservation) {
    http_response_code(404);
    die('Reservation not found.');
}

if (!isAdmin() && (int) $reservation['user_id'] !== (int) $userId) {
    http_response_code(403);
    die('You are not allowed to view this reservation.');
}

$message = '';
$messageStatus = null;

if (isset($_GET['cancelled']) && $_GET['cancelled'] === '1') {
    $message = 'Reservation cancelled successfully.';
    $messageStatus = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        if ($reservation['status'] !== 'active') {
            $message = 'Only active reservations can be cancelled.';
            $messageStatus = false;
        } elseif (!isReservationStartInFuture($reservation['start_time'])) {
            $message = 'Past reservations cannot be cancelled.';
            $messageStatus = false;
        } else {
            try {
                $pdo->beginTransaction();

                $oldStatus = $reservation['status'];

                cancelReservation($pdo, (int) $reservationId);

                addReservationStatusHistory(
                    $pdo,
                    (int) $reservationId,
                    $oldStatus,
                    'cancelled',
                    (int) $userId,
                    'Reservation cancelled.'
                );

                $pdo->commit();

                header('Location: reservation-detail.php?id=' . (int) $reservationId . '&cancelled=1');
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $message = DEBUG_MODE
                    ? 'Reservation cancellation failed: ' . $e->getMessage()
                    : 'Reservation cancellation failed.';

                $messageStatus = false;
            }
        }
    }
}

$reservation = getReservationDetail($pdo, (int) $reservationId);
$history = getReservationStatusHistory($pdo, (int) $reservationId);

$canCancel = $reservation['status'] === 'active'
    && isReservationStartInFuture($reservation['start_time']);

require_once __DIR__ . '/../includes/header.php';

?>

<h1>Reservation Detail</h1>

<p>
    <a href="my-reservations.php">Back to My Reservations</a>
</p>

<hr>

<?php if ($message !== ''): ?>
    <p style="color: <?= $messageStatus ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($message) ?>
    </p>
<?php endif; ?>

<h2>Reservation Information</h2>

<ul>
    <li>Reservation ID: <?= (int) $reservation['reservation_id'] ?></li>
    <li>User: <?= htmlspecialchars($reservation['user_full_name']) ?></li>
    <li>Email: <?= htmlspecialchars($reservation['user_email']) ?></li>
    <li>Laboratory: <?= htmlspecialchars($reservation['lab_code'] . ' - ' . $reservation['lab_name']) ?></li>
    <li>Laboratory Type: <?= htmlspecialchars($reservation['lab_type']) ?></li>
    <li>Location: <?= htmlspecialchars($reservation['location'] ?? '-') ?></li>
    <li>Station: <?= htmlspecialchars($reservation['station_code'] . ' - ' . $reservation['station_name']) ?></li>
    <li>Station Capacity: <?= (int) $reservation['capacity'] ?></li>
    <li>Station Status: <?= htmlspecialchars($reservation['station_status']) ?></li>
    <li>Start Time: <?= htmlspecialchars($reservation['start_time']) ?></li>
    <li>End Time: <?= htmlspecialchars($reservation['end_time']) ?></li>
    <li>Status: <?= htmlspecialchars($reservation['status']) ?></li>
    <li>Purpose: <?= htmlspecialchars($reservation['purpose'] ?? '-') ?></li>
    <li>Created At: <?= htmlspecialchars($reservation['created_at']) ?></li>
    <li>Updated At: <?= htmlspecialchars($reservation['updated_at']) ?></li>
</ul>

<?php if ($canCancel): ?>
    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
        <input type="hidden" name="action" value="cancel">
        <button type="submit">Cancel Reservation</button>
    </form>
<?php else: ?>
    <p>This reservation cannot be cancelled.</p>
<?php endif; ?>

<hr>

<h2>Status History</h2>

<?php if (count($history) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>History ID</th>
                <th>Old Status</th>
                <th>New Status</th>
                <th>Changed By</th>
                <th>Changed At</th>
                <th>Note</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?= (int) $item['history_id'] ?></td>
                    <td><?= htmlspecialchars($item['old_status'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['new_status']) ?></td>
                    <td><?= htmlspecialchars($item['changed_by_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['changed_at']) ?></td>
                    <td><?= htmlspecialchars($item['note'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No status history found.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>