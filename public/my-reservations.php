<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';

$userId = getCurrentUserId();

syncExpiredReservations($pdo);

$pageTitle = 'My Reservations';
$pageCss = 'my-reservations.css';

$statusFilter = $_GET['status'] ?? 'all';

if (!in_array($statusFilter, ['all', 'active', 'cancelled', 'completed'], true)) {
    $statusFilter = 'all';
}

$message = '';
$messageStatus = false;

function isFutureReservation(string $startTime): bool
{
    return strtotime($startTime) > time();
}

function formatReservationDateTime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    try {
        return (new DateTime($value))->format('d.m.Y H:i');
    } catch (Exception $e) {
        return $value;
    }
}

function reservationBadgeClass(string $status): string
{
    if ($status === 'active') {
        return 'badge-success';
    }

    if ($status === 'cancelled') {
        return 'badge-warning';
    }

    if ($status === 'completed') {
        return 'badge-secondary';
    }

    return 'badge-secondary';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);

    if ($action === 'cancel' && $reservationId) {
        $reservation = getReservationDetail($pdo, (int) $reservationId);

        if (!$reservation) {
            $messageStatus = false;
            $message = 'Reservation not found.';
        } elseif ((int) $reservation['user_id'] !== (int) $userId) {
            $messageStatus = false;
            $message = 'You are not allowed to cancel this reservation.';
        } elseif ($reservation['status'] !== 'active') {
            $messageStatus = false;
            $message = 'Only active reservations can be cancelled.';
        } elseif (!isFutureReservation($reservation['start_time'])) {
            $messageStatus = false;
            $message = 'Past or currently running reservations cannot be cancelled from this page.';
        } else {
            try {
                $pdo->beginTransaction();

                cancelReservation($pdo, (int) $reservationId);

                addReservationStatusHistory(
                    $pdo,
                    (int) $reservationId,
                    'active',
                    'cancelled',
                    (int) $userId,
                    'Reservation cancelled by user.'
                );

                $pdo->commit();

                $messageStatus = true;
                $message = 'Reservation cancelled successfully.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $messageStatus = false;
                $message = DEBUG_MODE
                    ? 'Reservation cancellation failed: ' . $e->getMessage()
                    : 'Reservation cancellation failed.';
            }
        }
    }
}

syncExpiredReservations($pdo);

$reservations = getUserReservations($pdo, (int) $userId, $statusFilter);

$allReservationsForKpi = getUserReservations($pdo, (int) $userId, 'all');

$activeCount = 0;
$cancelledCount = 0;
$completedCount = 0;

foreach ($allReservationsForKpi as $reservation) {
    if ($reservation['status'] === 'active') {
        $activeCount++;
    } elseif ($reservation['status'] === 'cancelled') {
        $cancelledCount++;
    } elseif ($reservation['status'] === 'completed') {
        $completedCount++;
    }
}

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">
            <h1 class="section-title" style="margin-bottom:8px;">
                My Reservations
            </h1>

            <p class="section-subtitle" style="margin-bottom:0;">
                Track, manage and review your full laboratory reservation history.
            </p>
        </div>

        <!-- MESSAGE -->
        <?php if ($message !== ''): ?>
            <div
                class="alert <?= $messageStatus ? 'alert-success' : 'alert-error' ?>"
                style="margin-bottom:24px;"
            >
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- KPI -->
        <div class="grid grid-3" style="margin-bottom:32px;">
            <div class="card card-hover">
                <h3>Active</h3>

                <p style="font-size:36px; font-weight:700; margin:0; color:var(--color-primary);">
                    <?= (int) $activeCount ?>
                </p>
            </div>

            <div class="card card-hover">
                <h3>Cancelled</h3>

                <p style="font-size:36px; font-weight:700; margin:0;">
                    <?= (int) $cancelledCount ?>
                </p>
            </div>

            <div class="card card-hover">
                <h3>Completed</h3>

                <p style="font-size:36px; font-weight:700; margin:0;">
                    <?= (int) $completedCount ?>
                </p>
            </div>
        </div>

        <!-- FILTER -->
        <div class="card" style="margin-bottom:32px;">
            <h2 style="margin-top:0;">Filter Reservations</h2>

            <div class="flex" style="gap:12px; flex-wrap:wrap;">
                <a
                    href="my-reservations.php?status=all"
                    class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>"
                >
                    All
                </a>

                <a
                    href="my-reservations.php?status=active"
                    class="btn <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-outline' ?>"
                >
                    Active
                </a>

                <a
                    href="my-reservations.php?status=cancelled"
                    class="btn <?= $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-outline' ?>"
                >
                    Cancelled
                </a>

                <a
                    href="my-reservations.php?status=completed"
                    class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline' ?>"
                >
                    Completed
                </a>
            </div>
        </div>

        <!-- LIST -->
        <?php if (count($reservations) > 0): ?>
            <div class="card">
                <h2 style="margin-top:0;">Reservation List</h2>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Laboratory</th>
                                <th>Station</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                                <th>Purpose</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <?php
                                $canCancel =
                                    $reservation['status'] === 'active'
                                    && isFutureReservation($reservation['start_time']);
                                ?>

                                <tr>
                                    <td>
                                        <?= (int) $reservation['reservation_id'] ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($reservation['lab_code']) ?>
                                        </strong>

                                        <br>

                                        <span style="color:var(--color-muted);">
                                            <?= htmlspecialchars($reservation['lab_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($reservation['station_code']) ?>
                                        </strong>

                                        <br>

                                        <span style="color:var(--color-muted);">
                                            <?= htmlspecialchars($reservation['station_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(formatReservationDateTime($reservation['start_time'])) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(formatReservationDateTime($reservation['end_time'])) ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= reservationBadgeClass($reservation['status']) ?>">
                                            <?= htmlspecialchars(ucfirst($reservation['status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['purpose'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <div class="flex" style="gap:8px; flex-wrap:wrap;">
                                            <a
                                                href="reservation-detail.php?id=<?= (int) $reservation['reservation_id'] ?>"
                                                class="btn btn-outline"
                                            >
                                                View
                                            </a>

                                            <?php if ($canCancel): ?>
                                                <form
                                                    method="POST"
                                                    action="my-reservations.php?status=<?= htmlspecialchars($statusFilter) ?>"
                                                    onsubmit="return confirm('Are you sure you want to cancel this reservation?');"
                                                    style="margin:0;"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="reservation_id"
                                                        value="<?= (int) $reservation['reservation_id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="action"
                                                        value="cancel"
                                                        class="btn btn-secondary"
                                                    >
                                                        Cancel
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color:var(--color-muted);">
                                                    -
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card labs-empty">
                <h3>No reservation found.</h3>

                <p class="section-subtitle">
                    Start by creating your first laboratory reservation.
                </p>

                <a href="reserve.php" class="btn btn-primary">
                    Create Reservation
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>