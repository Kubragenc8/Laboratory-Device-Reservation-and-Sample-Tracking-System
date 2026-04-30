<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';

$pageTitle = 'My Reservations';
$pageCss = 'my-reservations.css';

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

/* KPI */
$activeCount = 0;
$cancelledCount = 0;
$completedCount = 0;

foreach ($reservations as $reservation) {
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

        <!-- KPI -->
        <div class="grid grid-3" style="margin-bottom:32px;">

            <div class="card card-hover">
                <h3>Active</h3>
                <p style="font-size:36px; font-weight:700; margin:0; color:var(--color-primary);">
                    <?= $activeCount ?>
                </p>
            </div>

            <div class="card card-hover">
                <h3>Cancelled</h3>
                <p style="font-size:36px; font-weight:700; margin:0;">
                    <?= $cancelledCount ?>
                </p>
            </div>

            <div class="card card-hover">
                <h3>Completed</h3>
                <p style="font-size:36px; font-weight:700; margin:0;">
                    <?= $completedCount ?>
                </p>
            </div>

        </div>

        <!-- FILTER -->
        <div class="card" style="margin-bottom:32px;">

            <h2 style="margin-top:0;">Filter Reservations</h2>

            <div class="flex" style="gap:12px; flex-wrap:wrap;">

                <a href="my-reservations.php?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>">
                    All
                </a>

                <a href="my-reservations.php?status=active" class="btn <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-outline' ?>">
                    Active
                </a>

                <a href="my-reservations.php?status=cancelled" class="btn <?= $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-outline' ?>">
                    Cancelled
                </a>

                <a href="my-reservations.php?status=completed" class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline' ?>">
                    Completed
                </a>

            </div>

        </div>

        <!-- LIST -->
        <?php if (count($reservations) > 0): ?>

            <div class="card">

                <h2 style="margin-top:0;">
                    Reservation List
                </h2>

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

                                <tr>

                                    <td>
                                        <?= (int) $reservation['reservation_id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['lab_code'] . ' - ' . $reservation['lab_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['station_code'] . ' - ' . $reservation['station_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['start_time']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['end_time']) ?>
                                    </td>

                                    <td>

                                        <?php if ($reservation['status'] === 'active'): ?>
                                            <span class="badge badge-success">Active</span>

                                        <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                            <span class="badge badge-warning">Cancelled</span>

                                        <?php else: ?>
                                            <span class="badge badge-info">Completed</span>
                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars($reservation['purpose'] ?? '-') ?>
                                    </td>

                                    <td>

                                        <a href="reservation-detail.php?id=<?= (int) $reservation['reservation_id'] ?>" class="btn btn-outline" style="padding:8px 14px;">
                                            View
                                        </a>

                                        <?php if ($reservation['status'] === 'active' && isFutureReservation($reservation['start_time'])): ?>

                                            <div style="margin-top:8px;">
                                                <span class="badge badge-warning">
                                                    Cancelable
                                                </span>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

            </div>

        <?php else: ?>

            <div class="card" style="text-align:center;">

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