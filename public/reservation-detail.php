<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';

$userId = getCurrentUserId();

syncExpiredReservations($pdo);

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

if ((int) $reservation['user_id'] !== (int) $userId) {
    http_response_code(403);
    die('You are not allowed to view this reservation.');
}

$history = getReservationStatusHistory($pdo, (int) $reservationId);

$pageTitle = 'Reservation Detail';
$pageCss = 'reservation.css';

function formatReservationDetailDateTime(?string $value): string
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

function reservationDetailBadgeClass(string $status): string
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

function canEditReservationFromDetail(array $reservation): bool
{
    if ($reservation['status'] !== 'active') {
        return false;
    }

    return strtotime($reservation['start_time']) > time();
}

$canEdit = canEditReservationFromDetail($reservation);

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- BACK + ACTIONS -->
        <div
            style="
                margin-bottom:24px;
                display:flex;
                gap:12px;
                flex-wrap:wrap;
                align-items:center;
                justify-content:space-between;
            "
        >
            <a href="my-reservations.php" class="btn btn-outline">
                ← Back to My Reservations
            </a>

            <?php if ($canEdit): ?>
                <a
                    href="reservation-edit.php?id=<?= (int) $reservation['reservation_id'] ?>"
                    class="btn btn-primary"
                >
                    Edit Reservation
                </a>
            <?php else: ?>
                <button
                    type="button"
                    class="btn btn-secondary"
                    disabled
                    title="Only future active reservations can be edited."
                >
                    Edit Locked
                </button>
            <?php endif; ?>
        </div>

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">
            <div class="grid grid-2" style="align-items:start;">

                <div>
                    <p style="color:var(--color-muted); margin-bottom:8px;">
                        Reservation #<?= (int) $reservation['reservation_id'] ?>
                    </p>

                    <h1 class="section-title" style="margin-bottom:12px;">
                        <?= htmlspecialchars($reservation['lab_code'] . ' - ' . $reservation['station_code']) ?>
                    </h1>

                    <p class="section-subtitle">
                        <?= htmlspecialchars($reservation['purpose'] ?? 'No purpose provided.') ?>
                    </p>
                </div>

                <div class="card" style="background:var(--color-surface-soft);">
                    <p>
                        <strong>Status:</strong>

                        <span class="badge <?= reservationDetailBadgeClass($reservation['status']) ?>">
                            <?= htmlspecialchars(ucfirst($reservation['status'])) ?>
                        </span>
                    </p>

                    <p>
                        <strong>Created At:</strong>
                        <?= htmlspecialchars(formatReservationDetailDateTime($reservation['created_at'] ?? null)) ?>
                    </p>

                    <p>
                        <strong>Updated At:</strong>
                        <?= htmlspecialchars(formatReservationDetailDateTime($reservation['updated_at'] ?? null)) ?>
                    </p>

                    <?php if ($canEdit): ?>
                        <p style="margin-top:16px;">
                            <a
                                href="reservation-edit.php?id=<?= (int) $reservation['reservation_id'] ?>"
                                class="btn btn-primary"
                                style="width:100%;"
                            >
                                Edit This Reservation
                            </a>
                        </p>
                    <?php else: ?>
                        <div class="alert alert-error" style="margin-top:16px;">
                            Only future active reservations can be edited.
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- RESERVATION INFO -->
        <div class="card" style="margin-bottom:32px;">
            <h2 style="margin-top:0;">Reservation Information</h2>

            <div class="grid grid-2">
                <div>
                    <p>
                        <strong>Laboratory:</strong>
                        <?= htmlspecialchars($reservation['lab_name']) ?>
                    </p>

                    <p>
                        <strong>Lab Code:</strong>
                        <?= htmlspecialchars($reservation['lab_code']) ?>
                    </p>

                    <p>
                        <strong>Lab Type:</strong>
                        <?= htmlspecialchars($reservation['lab_type']) ?>
                    </p>

                    <p>
                        <strong>Location:</strong>
                        <?= htmlspecialchars($reservation['location'] ?? '-') ?>
                    </p>
                </div>

                <div>
                    <p>
                        <strong>Station:</strong>
                        <?= htmlspecialchars($reservation['station_name']) ?>
                    </p>

                    <p>
                        <strong>Station Code:</strong>
                        <?= htmlspecialchars($reservation['station_code']) ?>
                    </p>

                    <p>
                        <strong>Station Capacity:</strong>
                        <?= (int) $reservation['capacity'] ?>
                    </p>

                    <p>
                        <strong>Station Status:</strong>
                        <?= htmlspecialchars($reservation['station_status']) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- TIME INFO -->
        <div class="card" style="margin-bottom:32px;">
            <h2 style="margin-top:0;">Time Information</h2>

            <div class="grid grid-2">
                <div>
                    <p>
                        <strong>Start Time:</strong>
                        <?= htmlspecialchars(formatReservationDetailDateTime($reservation['start_time'])) ?>
                    </p>
                </div>

                <div>
                    <p>
                        <strong>End Time:</strong>
                        <?= htmlspecialchars(formatReservationDetailDateTime($reservation['end_time'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- USER INFO -->
        <div class="card" style="margin-bottom:32px;">
            <h2 style="margin-top:0;">User Information</h2>

            <div class="grid grid-2">
                <div>
                    <p>
                        <strong>Name:</strong>
                        <?= htmlspecialchars($reservation['user_full_name']) ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?= htmlspecialchars($reservation['user_email'] ?? '-') ?>
                    </p>
                </div>

                <div>
                    <p>
                        <strong>Phone:</strong>
                        <?= htmlspecialchars($reservation['user_phone'] ?? '-') ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- HISTORY -->
        <div class="card">
            <h2 style="margin-top:0;">Status History</h2>

            <?php if (count($history) > 0): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Old Status</th>
                                <th>New Status</th>
                                <th>Changed By</th>
                                <th>Note</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($history as $item): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars(formatReservationDetailDateTime($item['changed_at'])) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($item['old_status'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($item['new_status']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($item['changed_by_name'] ?? 'System') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($item['note'] ?? '-') ?>
                                    </td>
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