<?php

require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/reservation_helper.php';
require_once __DIR__ . '/../../helpers/lab_helper.php';

$pageTitle = 'Admin Reservations';

$adminUserId = getCurrentUserId();

$message = '';
$messageStatus = null;

$statusOptions = getReservationStatusOptions();

$labs = getAllLabs($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['new_status'] ?? '';

    if ($action === 'update_status') {
        if (!$reservationId) {
            $message = 'Valid reservation ID is required.';
            $messageStatus = false;
        } elseif (!in_array($newStatus, $statusOptions, true)) {
            $message = 'Invalid reservation status.';
            $messageStatus = false;
        } else {
            $reservation = getReservationDetail($pdo, (int) $reservationId);

            if (!$reservation) {
                $message = 'Reservation not found.';
                $messageStatus = false;
            } elseif ($reservation['status'] === $newStatus) {
                $message = 'Reservation already has this status.';
                $messageStatus = false;
            } elseif ($reservation['status'] !== 'active') {
                $message = 'Only active reservations can be updated by admin.';
                $messageStatus = false;
            } else {
                try {
                    $pdo->beginTransaction();

                    $oldStatus = $reservation['status'];

                    updateReservationStatus($pdo, (int) $reservationId, $newStatus);

                    addReservationStatusHistory(
                        $pdo,
                        (int) $reservationId,
                        $oldStatus,
                        $newStatus,
                        (int) $adminUserId,
                        'Reservation status updated by admin.'
                    );

                    $pdo->commit();

                    $message = 'Reservation status updated successfully.';
                    $messageStatus = true;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $message = DEBUG_MODE
                        ? 'Reservation status update failed: ' . $e->getMessage()
                        : 'Reservation status update failed.';

                    $messageStatus = false;
                }
            }
        }
    }
}

$filters = [
    'status' => $_GET['status'] ?? '',
    'lab_id' => $_GET['lab_id'] ?? '',
    'q' => trim($_GET['q'] ?? ''),
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

if ($filters['status'] !== '' && !in_array($filters['status'], $statusOptions, true)) {
    $filters['status'] = '';
}

if ($filters['lab_id'] !== '' && !filter_var($filters['lab_id'], FILTER_VALIDATE_INT)) {
    $filters['lab_id'] = '';
}

$reservations = getAdminReservations($pdo, $filters);

function selectedAdminOption($currentValue, $expectedValue): string
{
    return (string) $currentValue === (string) $expectedValue ? 'selected' : '';
}

function canAdminUpdateReservation(array $reservation): bool
{
    return $reservation['status'] === 'active';
}

require_once __DIR__ . '/../../includes/header.php';

?>

<h1>Admin Reservations</h1>

<?php if ($message !== ''): ?>
    <p style="color: <?= $messageStatus ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($message) ?>
    </p>
<?php endif; ?>

<h2>Filters</h2>

<form method="GET" action="">
    <div>
        <label for="q">Search</label><br>
        <input
            type="text"
            id="q"
            name="q"
            value="<?= htmlspecialchars($filters['q']) ?>"
            placeholder="Search user, email, laboratory, station or purpose"
        >
    </div>

    <br>

    <div>
        <label for="status">Status</label><br>
        <select id="status" name="status">
            <option value="">All statuses</option>

            <?php foreach ($statusOptions as $status): ?>
                <option
                    value="<?= htmlspecialchars($status) ?>"
                    <?= selectedAdminOption($filters['status'], $status) ?>
                >
                    <?= htmlspecialchars($status) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <br>

    <div>
        <label for="lab_id">Laboratory</label><br>
        <select id="lab_id" name="lab_id">
            <option value="">All laboratories</option>

            <?php foreach ($labs as $lab): ?>
                <option
                    value="<?= (int) $lab['lab_id'] ?>"
                    <?= selectedAdminOption($filters['lab_id'], $lab['lab_id']) ?>
                >
                    <?= htmlspecialchars($lab['lab_code'] . ' - ' . $lab['lab_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <br>

    <div>
        <label for="date_from">Date From</label><br>
        <input
            type="date"
            id="date_from"
            name="date_from"
            value="<?= htmlspecialchars($filters['date_from']) ?>"
        >
    </div>

    <br>

    <div>
        <label for="date_to">Date To</label><br>
        <input
            type="date"
            id="date_to"
            name="date_to"
            value="<?= htmlspecialchars($filters['date_to']) ?>"
        >
    </div>

    <br>

    <button type="submit">Apply Filters</button>
    <a href="reservations.php">Clear Filters</a>
</form>

<hr>

<h2>Reservation List</h2>

<p>
    Total reservations shown: <?= count($reservations) ?>
</p>

<?php if (count($reservations) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Email</th>
                <th>Laboratory</th>
                <th>Station</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Purpose</th>
                <th>Detail</th>
                <th>Admin Action</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td><?= (int) $reservation['reservation_id'] ?></td>

                    <td><?= htmlspecialchars($reservation['user_full_name']) ?></td>

                    <td><?= htmlspecialchars($reservation['user_email']) ?></td>

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
                        <a href="../reservation-detail.php?id=<?= (int) $reservation['reservation_id'] ?>">
                            View Detail
                        </a>
                    </td>

                    <td>
                        <?php if (canAdminUpdateReservation($reservation)): ?>
                            <form
                                method="POST"
                                action=""
                                onsubmit="return confirm('Are you sure you want to update this reservation status?');"
                            >
                                <input
                                    type="hidden"
                                    name="reservation_id"
                                    value="<?= (int) $reservation['reservation_id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="update_status"
                                >

                                <select name="new_status" required>
                                    <option value="">Select status</option>
                                    <option value="completed">completed</option>
                                    <option value="cancelled">cancelled</option>
                                </select>

                                <button type="submit">Update</button>
                            </form>
                        <?php else: ?>
                            No action available
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No reservation found.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>