<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';

$userId = getCurrentUserId();

$labId = filter_input(INPUT_GET, 'lab_id', FILTER_VALIDATE_INT);
$stationId = filter_input(INPUT_GET, 'station_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $labId = filter_input(INPUT_POST, 'lab_id', FILTER_VALIDATE_INT);
    $stationId = filter_input(INPUT_POST, 'station_id', FILTER_VALIDATE_INT);
}

$labs = getAllLabs($pdo);
$stations = [];

if ($labId) {
    $stations = getStationsByLab($pdo, (int) $labId);
}

$selectedStation = null;
$message = '';
$messageStatus = null;
$conflicts = [];
$createdReservationId = null;

$startTimeValue = '';
$endTimeValue = '';
$purposeValue = '';

if ($stationId) {
    $selectedStation = getReservationStationContext($pdo, (int) $stationId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'check';

    $startTimeValue = trim($_POST['start_time'] ?? '');
    $endTimeValue = trim($_POST['end_time'] ?? '');
    $purposeValue = trim($_POST['purpose'] ?? '');

    if (!$stationId) {
        $messageStatus = false;
        $message = 'Valid station selection is required.';
    } else {
        $startTime = normalizeDateTimeForDatabase($startTimeValue);
        $endTime = normalizeDateTimeForDatabase($endTimeValue);

        if (!$selectedStation) {
            $messageStatus = false;
            $message = 'Station not found.';
        } elseif ((int) $selectedStation['lab_is_active'] !== 1) {
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
            $isAvailable = checkAvailability($pdo, (int) $stationId, $startTime, $endTime);

            if (!$isAvailable) {
                $messageStatus = false;
                $message = 'This station is not available for the selected time interval.';
                $conflicts = getConflictingReservations($pdo, (int) $stationId, $startTime, $endTime);
            } elseif ($action === 'create') {
                try {
                    $pdo->beginTransaction();

                    $createdReservationId = createReservation(
                        $pdo,
                        (int) $userId,
                        (int) $selectedStation['lab_id'],
                        (int) $selectedStation['station_id'],
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

function selectedOption($currentValue, $expectedValue): string
{
    return (string) $currentValue === (string) $expectedValue ? 'selected' : '';
}

$pageTitle = 'Reserve Station';

require_once __DIR__ . '/../includes/header.php';

?>

<h1>Reserve Station</h1>

<h2>Select Laboratory and Station</h2>

<form method="GET" action="">
    <div>
        <label for="lab_id">Laboratory</label><br>
        <select id="lab_id" name="lab_id" required>
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

    <br>

    <button type="submit">Load Stations</button>
</form>

<?php if ($labId): ?>
    <hr>

    <form method="GET" action="">
        <input type="hidden" name="lab_id" value="<?= (int) $labId ?>">

        <div>
            <label for="station_id">Station</label><br>
            <select id="station_id" name="station_id" required>
                <option value="">Select station</option>

                <?php foreach ($stations as $station): ?>
                    <option
                        value="<?= (int) $station['station_id'] ?>"
                        <?= selectedOption($stationId, $station['station_id']) ?>
                    >
                        <?= htmlspecialchars($station['station_code'] . ' - ' . $station['station_name'] . ' (' . $station['status'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <br>

        <button type="submit">Select Station</button>
    </form>
<?php endif; ?>

<?php if ($selectedStation): ?>
    <hr>

    <h2>Selected Station</h2>

    <ul>
        <li>Laboratory: <?= htmlspecialchars($selectedStation['lab_name']) ?></li>
        <li>Station: <?= htmlspecialchars($selectedStation['station_code'] . ' - ' . $selectedStation['station_name']) ?></li>
        <li>Station Type: <?= htmlspecialchars($selectedStation['type_name']) ?></li>
        <li>Capacity: <?= (int) $selectedStation['capacity'] ?></li>
        <li>Status: <?= htmlspecialchars($selectedStation['station_status']) ?></li>
        <li>Location: <?= htmlspecialchars($selectedStation['location'] ?? '-') ?></li>
    </ul>

    <hr>

    <h2>Reservation Form</h2>

    <?php if ($message !== ''): ?>
        <p style="color: <?= $messageStatus ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($createdReservationId): ?>
        <p>
            Created Reservation ID:
            <strong><?= (int) $createdReservationId ?></strong>
        </p>

        <p>
            <a href="my-reservations.php">Go to My Reservations</a>
        </p>
    <?php endif; ?>

    <?php if (!empty($conflicts)): ?>
        <h3>Conflicting Reservations</h3>

        <table border="1" cellpadding="8" cellspacing="0">
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
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="lab_id" value="<?= (int) $selectedStation['lab_id'] ?>">
        <input type="hidden" name="station_id" value="<?= (int) $selectedStation['station_id'] ?>">

        <div>
            <label for="start_time">Start Time</label><br>
            <input
                type="datetime-local"
                id="start_time"
                name="start_time"
                value="<?= htmlspecialchars($startTimeValue) ?>"
                required
            >
        </div>

        <br>

        <div>
            <label for="end_time">End Time</label><br>
            <input
                type="datetime-local"
                id="end_time"
                name="end_time"
                value="<?= htmlspecialchars($endTimeValue) ?>"
                required
            >
        </div>

        <br>

        <div>
            <label for="purpose">Purpose</label><br>
            <textarea
                id="purpose"
                name="purpose"
                rows="3"
                cols="50"
                placeholder="Example: Database project study"
            ><?= htmlspecialchars($purposeValue) ?></textarea>
        </div>

        <br>

        <button type="submit" name="action" value="check">Check Availability</button>
        <button type="submit" name="action" value="create">Create Reservation</button>
    </form>
<?php elseif ($stationId): ?>
    <p style="color: red;">Station not found.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>