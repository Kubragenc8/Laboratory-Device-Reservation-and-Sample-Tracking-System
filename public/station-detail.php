<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';

$stationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$stationId) {
    http_response_code(400);
    die('Invalid station ID.');
}

$station = getStationById($pdo, (int) $stationId);

if (!$station) {
    http_response_code(404);
    die('Station not found.');
}

$equipmentList = getStationEquipment($pdo, (int) $stationId);
$upcomingReservations = getUpcomingReservationsByStation($pdo, (int) $stationId);

$pageTitle = 'Station Detail';
$pageCss = 'labs.css';

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- BACK -->
        <div style="margin-bottom:24px;">
            <a href="lab-detail.php?id=<?= (int) $station['lab_id'] ?>" class="btn btn-outline">
                ← Back to Laboratory
            </a>
        </div>

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">

            <div class="grid grid-2" style="align-items:start;">

                <div>
                    <p style="color:var(--color-muted); margin-bottom:8px;">
                        <?= htmlspecialchars($station['station_code']) ?>
                    </p>

                    <h1 class="section-title" style="margin-bottom:12px;">
                        <?= htmlspecialchars($station['station_name']) ?>
                    </h1>

                    <p class="section-subtitle">
                        <?= nl2br(htmlspecialchars($station['notes'] ?? 'No notes available.')) ?>
                    </p>

                    <?php if ($station['status'] === 'active'): ?>
                        <a
                            href="reserve.php?lab_id=<?= (int) $station['lab_id'] ?>&station_id=<?= (int) $station['station_id'] ?>"
                            class="btn btn-primary"
                        >
                            Reserve This Station
                        </a>
                    <?php else: ?>
                        <div class="alert alert-error">
                            This station is currently not available for reservation.
                        </div>
                    <?php endif; ?>

                </div>

                <div class="card" style="background:var(--color-surface-soft);">

                    <p><strong>Laboratory:</strong> <?= htmlspecialchars($station['lab_name']) ?></p>
                    <p><strong>Lab Code:</strong> <?= htmlspecialchars($station['lab_code']) ?></p>
                    <p><strong>Lab Type:</strong> <?= htmlspecialchars($station['lab_type']) ?></p>
                    <p><strong>Faculty:</strong> <?= htmlspecialchars($station['faculty_name']) ?></p>
                    <p><strong>Department:</strong> <?= htmlspecialchars($station['department_name']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($station['location'] ?? '-') ?></p>
                    <p><strong>Station Type:</strong> <?= htmlspecialchars($station['type_name']) ?></p>
                    <p><strong>Capacity:</strong> <?= (int) $station['capacity'] ?></p>

                    <p>
                        <strong>Status:</strong>
                        <?php if ($station['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning">
                                <?= htmlspecialchars($station['status']) ?>
                            </span>
                        <?php endif; ?>
                    </p>

                </div>

            </div>

        </div>

        <!-- EQUIPMENT -->
        <div class="card" style="margin-bottom:32px;">

            <h2 style="margin-top:0;">Equipment in This Station</h2>

            <?php if (count($equipmentList) > 0): ?>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Equipment</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($equipmentList as $equipment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($equipment['asset_code']) ?></td>
                                    <td><?= htmlspecialchars($equipment['equipment_name']) ?></td>
                                    <td><?= htmlspecialchars($equipment['category']) ?></td>
                                    <td><?= htmlspecialchars($equipment['brand'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($equipment['model'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($equipment['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="alert alert-success">
                    No equipment found for this station.
                </div>

            <?php endif; ?>

        </div>

        <!-- UPCOMING -->
        <div class="card">

            <h2 style="margin-top:0;">Upcoming Active Reservations</h2>

            <?php if (count($upcomingReservations) > 0): ?>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>User</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($upcomingReservations as $reservation): ?>
                                <tr>
                                    <td><?= (int) $reservation['reservation_id'] ?></td>
                                    <td><?= htmlspecialchars($reservation['user_full_name']) ?></td>
                                    <td><?= htmlspecialchars($reservation['start_time']) ?></td>
                                    <td><?= htmlspecialchars($reservation['end_time']) ?></td>
                                    <td>
                                        <span class="badge badge-success">
                                            <?= htmlspecialchars($reservation['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($reservation['purpose'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="alert alert-success">
                    No upcoming active reservation found for this station.
                </div>

            <?php endif; ?>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>