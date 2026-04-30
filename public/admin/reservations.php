<?php

require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/reservation_helper.php';
require_once __DIR__ . '/../../helpers/lab_helper.php';

$pageTitle = 'Admin Reservations';
$pageCss = 'admin-reservations.css';

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

                    updateReservationStatus(
                        $pdo,
                        (int) $reservationId,
                        $newStatus
                    );

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

if (
    $filters['status'] !== ''
    && !in_array($filters['status'], $statusOptions, true)
) {
    $filters['status'] = '';
}

if (
    $filters['lab_id'] !== ''
    && !filter_var($filters['lab_id'], FILTER_VALIDATE_INT)
) {
    $filters['lab_id'] = '';
}

$reservations = getAdminReservations($pdo, $filters);

function selectedAdminOption($currentValue, $expectedValue): string
{
    return (string) $currentValue === (string) $expectedValue
        ? 'selected'
        : '';
}

function canAdminUpdateReservation(array $reservation): bool
{
    return $reservation['status'] === 'active';
}

require_once __DIR__ . '/../../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">

            <h1 class="section-title" style="margin-bottom:8px;">
                Reservation Governance Center
            </h1>

            <p class="section-subtitle">
                Monitor all reservations, manage lifecycle states,
                and control operational reservation activity system-wide.
            </p>

        </div>

        <!-- ALERT -->
        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageStatus ? 'alert-success' : 'alert-error' ?>" style="margin-bottom:24px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- FILTERS -->
        <div class="card" style="margin-bottom:32px;">

            <h2 style="margin-top:0;">Filters</h2>

            <form method="GET" action="">

                <div class="grid grid-3">

                    <div class="form-group">
                        <label for="q" class="form-label">Search</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="form-control"
                            value="<?= htmlspecialchars($filters['q']) ?>"
                            placeholder="User, email, lab, station or purpose"
                        >
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>

                        <select
                            id="status"
                            name="status"
                            class="form-control"
                        >
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

                    <div class="form-group">
                        <label for="lab_id" class="form-label">
                            Laboratory
                        </label>

                        <select
                            id="lab_id"
                            name="lab_id"
                            class="form-control"
                        >
                            <option value="">All laboratories</option>

                            <?php foreach ($labs as $lab): ?>
                                <option
                                    value="<?= (int) $lab['lab_id'] ?>"
                                    <?= selectedAdminOption($filters['lab_id'], $lab['lab_id']) ?>
                                >
                                    <?= htmlspecialchars(
                                        $lab['lab_code'] . ' - ' . $lab['lab_name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>

                <div class="grid grid-2">

                    <div class="form-group">
                        <label for="date_from" class="form-label">
                            Date From
                        </label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="form-control"
                            value="<?= htmlspecialchars($filters['date_from']) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_to" class="form-label">
                            Date To
                        </label>

                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            class="form-control"
                            value="<?= htmlspecialchars($filters['date_to']) ?>"
                        >
                    </div>

                </div>

                <div class="flex" style="gap:12px; flex-wrap:wrap;">

                    <button type="submit" class="btn btn-primary">
                        Apply Filters
                    </button>

                    <a href="reservations.php" class="btn btn-outline">
                        Clear Filters
                    </a>

                </div>

            </form>

        </div>

        <!-- SUMMARY -->
        <div class="card" style="margin-bottom:32px;">

            <h2 style="margin-top:0;">Results Summary</h2>

            <p style="margin-bottom:0;">
                Total reservations shown:
                <strong><?= count($reservations) ?></strong>
            </p>

        </div>

        <!-- TABLE -->
        <div class="card">

            <h2 style="margin-top:0;">Reservation List</h2>

            <?php if (count($reservations) > 0): ?>

                <div class="table-wrapper">

                    <table class="table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
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

                                <tr>

                                    <td>
                                        <?= (int) $reservation['reservation_id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['user_full_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['email']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $reservation['lab_code'] . ' - ' . $reservation['lab_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $reservation['station_code'] . ' - ' . $reservation['station_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['start_time']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['end_time']) ?>
                                    </td>

                                    <td>

                                        <?php if ($reservation['status'] === 'active'): ?>
                                            <span class="badge badge-success">
                                                Active
                                            </span>

                                        <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                            <span class="badge badge-warning">
                                                Cancelled
                                            </span>

                                        <?php else: ?>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars(
                                                    ucfirst($reservation['status'])
                                                ) ?>
                                            </span>
                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $reservation['purpose'] ?? '-'
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if (canAdminUpdateReservation($reservation)): ?>

                                            <form
                                                method="POST"
                                                action=""
                                                class="admin-status-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="update_status"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="reservation_id"
                                                    value="<?= (int) $reservation['reservation_id'] ?>"
                                                >

                                                <select
                                                    name="new_status"
                                                    class="form-control"
                                                    required
                                                >
                                                    <option value="">
                                                        Change
                                                    </option>

                                                    <?php foreach ($statusOptions as $status): ?>

                                                        <?php if ($status !== $reservation['status']): ?>
                                                            <option
                                                                value="<?= htmlspecialchars($status) ?>"
                                                            >
                                                                <?= htmlspecialchars($status) ?>
                                                            </option>
                                                        <?php endif; ?>

                                                    <?php endforeach; ?>

                                                </select>

                                                <button
                                                    type="submit"
                                                    class="btn btn-secondary"
                                                    style="margin-top:8px;"
                                                >
                                                    Update
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="badge badge-info">
                                                Locked
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="alert alert-success">
                    No reservation found.
                </div>

            <?php endif; ?>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>