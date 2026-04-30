<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';

$pageTitle = 'Reservation Detail';
$pageCss = 'reservation-detail.css';

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

<section class="page-section">
    <div class="container">

        <!-- BACK -->
        <div style="margin-bottom:24px;">
            <a href="my-reservations.php" class="btn btn-outline">
                ← Back to My Reservations
            </a>
        </div>

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">

            <div class="grid grid-2" style="align-items:start;">

                <div>
                    <p style="color:var(--color-muted); margin-bottom:8px;">
                        Reservation #<?= (int) $reservation['reservation_id'] ?>
                    </p>

                    <h1 class="section-title" style="margin-bottom:12px;">
                        <?= htmlspecialchars($reservation['lab_name']) ?>
                    </h1>

                    <p class="section-subtitle">
                        <?= htmlspecialchars($reservation['station_code'] . ' - ' . $reservation['station_name']) ?>
                    </p>

                    <?php if ($reservation['status'] === 'active'): ?>
                        <span class="badge badge-success">Active</span>
                    <?php elseif ($reservation['status'] === 'cancelled'): ?>
                        <span class="badge badge-warning">Cancelled</span>
                    <?php else: ?>
                        <span class="badge badge-info">Completed</span>
                    <?php endif; ?>

                </div>

                <div class="card" style="background:var(--color-surface-soft);">
                    <p><strong>User:</strong> <?= htmlspecialchars($reservation['user_full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($reservation['user_email']) ?></p>
                    <p><strong>Laboratory Type:</strong> <?= htmlspecialchars($reservation['lab_type']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($reservation['location'] ?? '-') ?></p>
                    <p><strong>Capacity:</strong> <?= (int) $reservation['capacity'] ?></p>
                    <p><strong>Station Status:</strong> <?= htmlspecialchars($reservation['station_status']) ?></p>
                </div>

            </div>

        </div>

        <!-- MESSAGE -->
        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageStatus ? 'alert-success' : 'alert-error' ?>" style="margin-bottom:24px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- DETAILS -->
        <div class="card" style="margin-bottom:32px;">

            <h2 style="margin-top:0;">Reservation Information</h2>

            <div class="grid grid-2">

                <div>
                    <p><strong>Start Time:</strong> <?= htmlspecialchars($reservation['start_time']) ?></p>
                    <p><strong>End Time:</strong> <?= htmlspecialchars($reservation['end_time']) ?></p>
                    <p><strong>Purpose:</strong> <?= htmlspecialchars($reservation['purpose'] ?? '-') ?></p>
                </div>

                <div>
                    <p><strong>Created At:</strong> <?= htmlspecialchars($reservation['created_at']) ?></p>
                    <p><strong>Updated At:</strong> <?= htmlspecialchars($reservation['updated_at']) ?></p>
                </div>

            </div>

            <?php if ($canCancel): ?>

                <form
                    method="POST"
                    action=""
                    onsubmit="return confirm('Are you sure you want to cancel this reservation?');"
                    style="margin-top:24px;"
                >
                    <input type="hidden" name="action" value="cancel">

                    <button type="submit" class="btn btn-primary">
                        Cancel Reservation
                    </button>
                </form>

            <?php else: ?>

                <div class="alert alert-success" style="margin-top:24px;">
                    This reservation cannot be cancelled.
                </div>

            <?php endif; ?>

        </div>

        <!-- STATUS HISTORY -->
        <div class="card">

            <h2 style="margin-top:0;">Status History</h2>

            <?php if (count($history) > 0): ?>

                <div class="table-wrapper">

                    <table class="table">
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

                </div>

            <?php else: ?>

                <div class="alert alert-success">
                    No status history found.
                </div>

            <?php endif; ?>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>