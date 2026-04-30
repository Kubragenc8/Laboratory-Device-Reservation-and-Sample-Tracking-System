<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Dashboard';
$pageCss = 'dashboard.css';

$userId = getCurrentUserId();

/* ACTIVE */
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS active_reservation_count
    FROM reservations
    WHERE user_id = :user_id
      AND status = 'active'
");
$stmt->execute([
    ':user_id' => $userId
]);
$activeReservationCount = (int) $stmt->fetch()['active_reservation_count'];

/* UPCOMING */
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS upcoming_count
    FROM reservations
    WHERE user_id = :user_id
      AND status = 'active'
      AND start_time >= NOW()
");
$stmt->execute([
    ':user_id' => $userId
]);
$upcomingReservationCount = (int) $stmt->fetch()['upcoming_count'];

/* PAST */
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS past_count
    FROM reservations
    WHERE user_id = :user_id
      AND end_time < NOW()
");
$stmt->execute([
    ':user_id' => $userId
]);
$pastReservationCount = (int) $stmt->fetch()['past_count'];

/* NEXT RESERVATION */
$stmt = $pdo->prepare("
    SELECT
        r.reservation_id,
        r.start_time,
        r.end_time,
        r.status,
        l.lab_name,
        w.station_code,
        w.station_name
    FROM reservations r
    INNER JOIN laboratories l
        ON r.lab_id = l.lab_id
    INNER JOIN workstations w
        ON r.station_id = w.station_id
    WHERE r.user_id = :user_id
      AND r.status = 'active'
      AND r.start_time >= NOW()
    ORDER BY r.start_time ASC
    LIMIT 1
");
$stmt->execute([
    ':user_id' => $userId
]);
$nextReservation = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- HEADER -->
        <div class="card" style="margin-bottom:32px;">
            <h1 class="section-title" style="margin-bottom:8px;">
                Welcome, <?= htmlspecialchars(getCurrentUserName()) ?>
            </h1>

            <p class="section-subtitle" style="margin-bottom:0;">
                Manage your laboratory workflow, reservations and academic station access.
            </p>
        </div>

        <!-- KPI -->
        <div class="grid grid-3" style="margin-bottom:32px;">

            <div class="card card-hover">
                <h3>Active Reservations</h3>
                <p style="font-size:36px; font-weight:700; color:var(--color-primary); margin:0;">
                    <?= $activeReservationCount ?>
                </p>
            </div>

            <div class="card card-hover">
                <h3>Upcoming</h3>
                <p style="font-size:36px; font-weight:700; color:var(--color-info); margin:0;">
                    <?= $upcomingReservationCount ?>
                </p>
            </div>

            <div class="card card-hover">
                <h3>Past Reservations</h3>
                <p style="font-size:36px; font-weight:700; color:var(--color-muted); margin:0;">
                    <?= $pastReservationCount ?>
                </p>
            </div>

        </div>

        <!-- QUICK ACTIONS -->
        <div class="card" style="margin-bottom:32px;">
            <h2 style="margin-top:0;">Quick Actions</h2>

            <div class="flex" style="gap:16px; flex-wrap:wrap;">

                <a href="labs.php" class="btn btn-primary">
                    Browse Laboratories
                </a>

                <a href="reserve.php" class="btn btn-secondary">
                    New Reservation
                </a>

                <a href="my-reservations.php" class="btn btn-outline">
                    My Reservations
                </a>

                <a href="profile.php" class="btn btn-outline">
                    Profile
                </a>

            </div>
        </div>

        <!-- NEXT RESERVATION -->
        <div class="card">

            <h2 style="margin-top:0;">Upcoming Reservation</h2>

            <?php if ($nextReservation): ?>

                <div class="grid grid-2">

                    <div>
                        <p><strong>Reservation ID:</strong> <?= (int) $nextReservation['reservation_id'] ?></p>
                        <p><strong>Laboratory:</strong> <?= htmlspecialchars($nextReservation['lab_name']) ?></p>
                        <p><strong>Station:</strong> <?= htmlspecialchars($nextReservation['station_code'] . ' - ' . $nextReservation['station_name']) ?></p>
                    </div>

                    <div>
                        <p><strong>Start Time:</strong> <?= htmlspecialchars($nextReservation['start_time']) ?></p>
                        <p><strong>End Time:</strong> <?= htmlspecialchars($nextReservation['end_time']) ?></p>
                        <p>
                            <strong>Status:</strong>
                            <span class="badge badge-success">
                                <?= htmlspecialchars($nextReservation['status']) ?>
                            </span>
                        </p>
                    </div>

                </div>

            <?php else: ?>

                <div class="alert alert-success">
                    No upcoming active reservation found.
                </div>

                <a href="labs.php" class="btn btn-primary">
                    Explore Laboratories
                </a>

            <?php endif; ?>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>