<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';
require_once __DIR__ . '/../helpers/validation_helper.php';

$userId = getCurrentUserId();

$labs = getAllLabs($pdo);
$stations = [];
$selectedStation = null;

$message = '';
$messageStatus = false;

$createdReservationId = null;
$conflicts = [];

$labId = null;
$stationId = null;

$startTimeValue = $_POST['start_time'] ?? '';
$endTimeValue = $_POST['end_time'] ?? '';
$purposeValue = trim($_POST['purpose'] ?? '');

/**
 * Select option helper.
 */
function selectedOption($currentValue, $expectedValue): string
{
    return (string) $currentValue === (string) $expectedValue ? 'selected' : '';
}

/**
 * datetime-local input için değeri güvenli formata çevirir.
 * HTML input şu formatı ister: 2026-04-30T14:30
 */
function datetimeLocalValue(?string $value): string
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

/**
 * GET isteği: kullanıcı lab/station seçerken çalışır.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $labIdInput = filter_input(INPUT_GET, 'lab_id', FILTER_VALIDATE_INT);
    $stationIdInput = filter_input(INPUT_GET, 'station_id', FILTER_VALIDATE_INT);

    $labId = $labIdInput ? (int) $labIdInput : null;
    $stationId = $stationIdInput ? (int) $stationIdInput : null;

    if ($stationId !== null) {
        $selectedStation = getReservationStationContext($pdo, $stationId);

        if (!$selectedStation) {
            $messageStatus = false;
            $message = 'Selected station was not found.';
            $stationId = null;
        } elseif ($labId !== null && (int) $selectedStation['lab_id'] !== (int) $labId) {
            $messageStatus = false;
            $message = 'Selected station does not belong to the selected laboratory.';
            $selectedStation = null;
            $stationId = null;
        } else {
            $labId = (int) $selectedStation['lab_id'];
            $stationId = (int) $selectedStation['station_id'];
        }
    }

    if ($labId !== null) {
        $stations = getStationsByLab($pdo, $labId);
    }
}

/**
 * POST isteği: availability check veya reservation create sırasında çalışır.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $postedLabId = filter_input(INPUT_POST, 'lab_id', FILTER_VALIDATE_INT);
    $postedStationId = filter_input(INPUT_POST, 'station_id', FILTER_VALIDATE_INT);

    $labId = $postedLabId ? (int) $postedLabId : null;
    $stationId = $postedStationId ? (int) $postedStationId : null;

    $startTimeValue = trim($_POST['start_time'] ?? '');
    $endTimeValue = trim($_POST['end_time'] ?? '');
    $purposeValue = trim($_POST['purpose'] ?? '');

    $startTime = normalizeDateTimeForDatabase($startTimeValue);
    $endTime = normalizeDateTimeForDatabase($endTimeValue);

    if (!$stationId) {
        $messageStatus = false;
        $message = 'Valid station is required.';
    } else {
        $selectedStation = getReservationStationContext($pdo, $stationId);

        if (!$selectedStation) {
            $messageStatus = false;
            $message = 'Selected station was not found.';
            $stationId = null;
        } else {
            $realLabId = (int) $selectedStation['lab_id'];
            $realStationId = (int) $selectedStation['station_id'];

            /**
             * Güvenlik kontrolü:
             * Formdan gelen lab_id ile station'ın gerçek lab_id değeri uyuşmalı.
             */
            if ($labId !== null && $labId !== $realLabId) {
                $messageStatus = false;
                $message = 'Selected station does not belong to the selected laboratory.';
                $selectedStation = null;
                $stationId = null;
            } else {
                $labId = $realLabId;
                $stationId = $realStationId;

                if ((int) $selectedStation['lab_is_active'] !== 1) {
                    $messageStatus = false;
                    $message = 'This laboratory is not active.';
                } elseif ($selectedStation['station_status'] !== 'active') {
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
                        $stationId,
                        $startTime,
                        $endTime
                    );

                    if (!$isAvailable) {
                        $messageStatus = false;
                        $message = 'This station is not available for the selected time interval.';

                        $conflicts = getConflictingReservations(
                            $pdo,
                            $stationId,
                            $startTime,
                            $endTime
                        );
                    } elseif ($action === 'create') {
                        try {
                            $pdo->beginTransaction();

                            $createdReservationId = createReservation(
                                $pdo,
                                (int) $userId,
                                $labId,
                                $stationId,
                                $startTime,
                                $endTime,
                                $purposeValue !== '' ? mb_substr($purposeValue, 0, 255) : null
                            );

                            addReservationStatusHistory(
                                $pdo,
                                (int) $createdReservationId,
                                null,
                                'active',
                                (int) $userId,
                                'Reservation created.'
                            );

                            $pdo->commit();

                            $messageStatus = true;
                            $message = 'Reservation created successfully.';
                        } catch (Exception $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }

                            $messageStatus = false;
                            $message = DEBUG_MODE
                                ? 'Reservation creation failed: ' . $e->getMessage()
                                : 'Reservation creation failed.';
                        }
                    } else {
                        $messageStatus = true;
                        $message = 'This station is available for the selected time interval.';
                    }
                }
            }
        }
    }

    if ($labId !== null) {
        $stations = getStationsByLab($pdo, $labId);
    }
}

$pageTitle = 'Reserve Station';
$pageCss = 'reservation.css';

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">
            <h1 class="section-title" style="margin-bottom:8px;">
                Reserve Station
            </h1>

            <p class="section-subtitle" style="margin-bottom:0;">
                Complete your laboratory reservation through a structured,
                conflict-safe academic workflow.
            </p>
        </div>

        <!-- STEP 1 -->
        <div class="card" style="margin-bottom:24px;">
            <h2 style="margin-top:0;">Step 1 — Select Laboratory</h2>

            <form method="GET" action="">
                <div class="form-group">
                    <label for="lab_id" class="form-label">Laboratory</label>

                    <select id="lab_id" name="lab_id" class="form-control" required>
                        <option value="">Select laboratory</option>

                        <?php foreach ($labs as $lab): ?>
                            <option
                                value="<?= (int) $lab['lab_id'] ?>"
                                <?= selectedOption($labId, $lab['lab_id']) ?>
                            >
                                <?= htmlspecialchars($lab['lab_code'] . ' - ' . $lab['lab_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Load Stations
                </button>
            </form>
        </div>

        <!-- STEP 2 -->
        <?php if ($labId): ?>
            <div class="card" style="margin-bottom:24px;">
                <h2 style="margin-top:0;">Step 2 — Select Station</h2>

                <form method="GET" action="">
                    <input type="hidden" name="lab_id" value="<?= (int) $labId ?>">

                    <div class="form-group">
                        <label for="station_id" class="form-label">Station</label>

                        <select id="station_id" name="station_id" class="form-control" required>
                            <option value="">Select station</option>

                            <?php foreach ($stations as $station): ?>
                                <?php
                                $isActiveStation = ($station['status'] === 'active');
                                ?>

                                <option
                                    value="<?= (int) $station['station_id'] ?>"
                                    <?= selectedOption($stationId, $station['station_id']) ?>
                                    <?= $isActiveStation ? '' : 'disabled' ?>
                                >
                                    <?= htmlspecialchars(
                                        $station['station_code']
                                        . ' - '
                                        . $station['station_name']
                                        . ' ('
                                        . $station['status']
                                        . ')'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Select Station
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- STEP 3 -->
        <?php if ($selectedStation): ?>
            <div class="card" style="margin-bottom:24px;">
                <h2 style="margin-top:0;">Step 3 — Selected Station Summary</h2>

                <div class="grid grid-2">
                    <div>
                        <p>
                            <strong>Laboratory:</strong>
                            <?= htmlspecialchars($selectedStation['lab_name']) ?>
                        </p>

                        <p>
                            <strong>Station:</strong>
                            <?= htmlspecialchars($selectedStation['station_code'] . ' - ' . $selectedStation['station_name']) ?>
                        </p>

                        <p>
                            <strong>Type:</strong>
                            <?= htmlspecialchars($selectedStation['type_name']) ?>
                        </p>
                    </div>

                    <div>
                        <p>
                            <strong>Capacity:</strong>
                            <?= (int) $selectedStation['capacity'] ?>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <?= htmlspecialchars($selectedStation['station_status']) ?>
                        </p>

                        <p>
                            <strong>Location:</strong>
                            <?= htmlspecialchars($selectedStation['location'] ?? '-') ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- MESSAGE -->
        <?php if ($message !== ''): ?>
            <div
                class="alert <?= $messageStatus ? 'alert-success' : 'alert-error' ?>"
                style="margin-bottom:24px;"
            >
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- CREATED -->
        <?php if ($createdReservationId): ?>
            <div class="card" style="margin-bottom:24px; text-align:center;">
                <h3>Reservation Created Successfully</h3>

                <p>
                    Reservation ID:
                    <strong><?= (int) $createdReservationId ?></strong>
                </p>

                <a href="my-reservations.php" class="btn btn-primary">
                    Go to My Reservations
                </a>
            </div>
        <?php endif; ?>

        <!-- CONFLICT -->
        <?php if (!empty($conflicts)): ?>
            <div class="card" style="margin-bottom:24px;">
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
                                    <td><?= htmlspecialchars($conflict['start_time']) ?></td>
                                    <td><?= htmlspecialchars($conflict['end_time']) ?></td>
                                    <td><?= htmlspecialchars($conflict['status']) ?></td>
                                    <td><?= htmlspecialchars($conflict['purpose'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- STEP 4 -->
        <?php if ($selectedStation): ?>
            <div class="card">
                <h2 style="margin-top:0;">Step 4 — Reservation Form</h2>

                <form method="POST" action="">
                    <input
                        type="hidden"
                        name="lab_id"
                        value="<?= (int) $selectedStation['lab_id'] ?>"
                    >

                    <input
                        type="hidden"
                        name="station_id"
                        value="<?= (int) $selectedStation['station_id'] ?>"
                    >

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="start_time" class="form-label">Start Time</label>

                            <input
                                type="datetime-local"
                                id="start_time"
                                name="start_time"
                                class="form-control"
                                value="<?= htmlspecialchars(datetimeLocalValue($startTimeValue)) ?>"
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
                                value="<?= htmlspecialchars(datetimeLocalValue($endTimeValue)) ?>"
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
                            placeholder="Example: Database project study"
                        ><?= htmlspecialchars($purposeValue) ?></textarea>
                    </div>

                    <div class="flex" style="gap:16px; flex-wrap:wrap;">
                        <button
                            type="submit"
                            name="action"
                            value="check"
                            class="btn btn-secondary"
                        >
                            Check Availability
                        </button>

                        <button
                            type="submit"
                            name="action"
                            value="create"
                            class="btn btn-primary"
                        >
                            Create Reservation
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>