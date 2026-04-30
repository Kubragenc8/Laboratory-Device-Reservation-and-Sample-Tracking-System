<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';
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
    die('You are not allowed to edit this reservation.');
}

function isEditableReservation(array $reservation): bool
{
    if ($reservation['status'] !== 'active') {
        return false;
    }

    return strtotime($reservation['start_time']) > time();
}

function datetimeLocalEditValue(?string $value): string
{
    $value = trim($value ?? '');

    if ($value === '') {
        return '';
    }

    try {
        $value = str_replace('T', ' ', $value);
        return (new DateTime($value))->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        return '';
    }
}

function formatReservationEditDateTime(?string $value): string
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

$message = '';
$messageStatus = false;
$conflicts = [];

$startTimeValue = datetimeLocalEditValue($reservation['start_time']);
$endTimeValue = datetimeLocalEditValue($reservation['end_time']);
$purposeValue = $reservation['purpose'] ?? '';

$canEdit = isEditableReservation($reservation);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canEdit) {
        $messageStatus = false;
        $message = 'This reservation cannot be edited.';
    } else {
        $startTimeValue = trim($_POST['start_time'] ?? '');
        $endTimeValue = trim($_POST['end_time'] ?? '');
        $purposeValue = trim($_POST['purpose'] ?? '');

        $startTime = normalizeDateTimeForDatabase($startTimeValue);
        $endTime = normalizeDateTimeForDatabase($endTimeValue);

        $stationContext = getReservationStationContext(
            $pdo,
            (int) $reservation['station_id']
        );

        if (!$stationContext) {
            $messageStatus = false;
            $message = 'Reservation station was not found.';
        } elseif ((int) $stationContext['lab_id'] !== (int) $reservation['lab_id']) {
            $messageStatus = false;
            $message = 'Reservation station and laboratory connection is invalid.';
        } elseif ((int) $stationContext['lab_is_active'] !== 1) {
            $messageStatus = false;
            $message = 'This laboratory is not active.';
        } elseif ($stationContext['station_status'] !== 'active') {
            $messageStatus = false;
            $message = 'This station is not active for reservation.';
        } elseif (!isValidReservationInterval($startTime, $endTime)) {
            $messageStatus = false;
            $message = 'End time must be later than start time.';
        } elseif (!isReservationStartInFuture($startTime)) {
            $messageStatus = false;
            $message = 'Reservation start time must be in the future.';
        } else {
            $isAvailable = checkAvailability(
                $pdo,
                (int) $reservation['station_id'],
                $startTime,
                $endTime,
                (int) $reservation['reservation_id']
            );

            if (!$isAvailable) {
                $messageStatus = false;
                $message = 'This station is not available for the selected time interval.';

                $conflicts = getConflictingReservations(
                    $pdo,
                    (int) $reservation['station_id'],
                    $startTime,
                    $endTime,
                    (int) $reservation['reservation_id']
                );
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE reservations
                        SET
                            start_time = :start_time,
                            end_time = :end_time,
                            purpose = :purpose
                        WHERE reservation_id = :reservation_id
                        AND user_id = :user_id
                        AND status = 'active'
                    ");

                    $stmt->execute([
                        ':start_time' => $startTime,
                        ':end_time' => $endTime,
                        ':purpose' => $purposeValue !== '' ? mb_substr($purposeValue, 0, 255) : null,
                        ':reservation_id' => (int) $reservation['reservation_id'],
                        ':user_id' => (int) $userId,
                    ]);

                    $messageStatus = true;
                    $message = 'Reservation updated successfully.';

                    $reservation = getReservationDetail($pdo, (int) $reservationId);

                    $startTimeValue = datetimeLocalEditValue($reservation['start_time']);
                    $endTimeValue = datetimeLocalEditValue($reservation['end_time']);
                    $purposeValue = $reservation['purpose'] ?? '';
                    $canEdit = isEditableReservation($reservation);
                } catch (Exception $e) {
                    $messageStatus = false;
                    $message = DEBUG_MODE
                        ? 'Reservation update failed: ' . $e->getMessage()
                        : 'Reservation update failed.';
                }
            }
        }
    }
}

$pageTitle = 'Edit Reservation';
$pageCss = 'reservation.css';

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- BACK -->
        <div style="margin-bottom:24px;">
            <a href="reservation-detail.php?id=<?= (int) $reservation['reservation_id'] ?>" class="btn btn-outline">
                ← Back to Reservation Detail
            </a>
        </div>

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">
            <h1 class="section-title" style="margin-bottom:8px;">
                Edit Reservation
            </h1>

            <p class="section-subtitle" style="margin-bottom:0;">
                Reservation #<?= (int) $reservation['reservation_id'] ?>
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

        <!-- NOT EDITABLE -->
        <?php if (!$canEdit): ?>
            <div class="card" style="margin-bottom:32px;">
                <h2 style="margin-top:0;">Reservation Cannot Be Edited</h2>

                <div class="alert alert-error">
                    Only future active reservations can be edited.
                </div>

                <p>
                    <strong>Current Status:</strong>
                    <?= htmlspecialchars(ucfirst($reservation['status'])) ?>
                </p>

                <p>
                    <strong>Start Time:</strong>
                    <?= htmlspecialchars(formatReservationEditDateTime($reservation['start_time'])) ?>
                </p>

                <p>
                    <strong>End Time:</strong>
                    <?= htmlspecialchars(formatReservationEditDateTime($reservation['end_time'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- SUMMARY -->
        <div class="card" style="margin-bottom:32px;">
            <h2 style="margin-top:0;">Reservation Summary</h2>

            <div class="grid grid-2">
                <div>
                    <p>
                        <strong>Laboratory:</strong>
                        <?= htmlspecialchars($reservation['lab_code'] . ' - ' . $reservation['lab_name']) ?>
                    </p>

                    <p>
                        <strong>Station:</strong>
                        <?= htmlspecialchars($reservation['station_code'] . ' - ' . $reservation['station_name']) ?>
                    </p>

                    <p>
                        <strong>Location:</strong>
                        <?= htmlspecialchars($reservation['location'] ?? '-') ?>
                    </p>
                </div>

                <div>
                    <p>
                        <strong>Status:</strong>
                        <?= htmlspecialchars(ucfirst($reservation['status'])) ?>
                    </p>

                    <p>
                        <strong>Current Start:</strong>
                        <?= htmlspecialchars(formatReservationEditDateTime($reservation['start_time'])) ?>
                    </p>

                    <p>
                        <strong>Current End:</strong>
                        <?= htmlspecialchars(formatReservationEditDateTime($reservation['end_time'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- CONFLICTS -->
        <?php if (!empty($conflicts)): ?>
            <div class="card" style="margin-bottom:32px;">
                <h2 style="margin-top:0;">Conflicting Reservations</h2>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($conflicts as $conflict): ?>
                                <tr>
                                    <td><?= (int) $conflict['reservation_id'] ?></td>
                                    <td><?= htmlspecialchars($conflict['user_full_name']) ?></td>
                                    <td><?= htmlspecialchars(formatReservationEditDateTime($conflict['start_time'])) ?></td>
                                    <td><?= htmlspecialchars(formatReservationEditDateTime($conflict['end_time'])) ?></td>
                                    <td><?= htmlspecialchars($conflict['status']) ?></td>
                                    <td><?= htmlspecialchars($conflict['purpose'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <?php if ($canEdit): ?>
            <div class="card">
                <h2 style="margin-top:0;">Update Reservation</h2>

                <form method="POST" action="">
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="start_time" class="form-label">Start Time</label>

                            <input
                                type="datetime-local"
                                id="start_time"
                                name="start_time"
                                class="form-control"
                                value="<?= htmlspecialchars(datetimeLocalEditValue($startTimeValue)) ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="end_time" class="form-label">End Time</label>

                            <input
                                type="datetime-local"
                                id="end_time"
                                name="end_time"
                                class="form-control"
                                value="<?= htmlspecialchars(datetimeLocalEditValue($endTimeValue)) ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="purpose" class="form-label">Purpose</label>

                        <textarea
                            id="purpose"
                            name="purpose"
                            class="form-control"
                            rows="4"
                        ><?= htmlspecialchars($purposeValue) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>