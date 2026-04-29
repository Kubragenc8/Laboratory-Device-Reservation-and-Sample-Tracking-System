<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth_helper.php';
require_once __DIR__ . '/../../helpers/validation_helper.php';
require_once __DIR__ . '/../../helpers/response_helper.php';
require_once __DIR__ . '/../../helpers/reservation_helper.php';

if (!isLoggedIn()) {
    jsonError('Authentication is required.', 401);
}

$userId = getCurrentUserId();

$stationId = $_POST['station_id'] ?? $_GET['station_id'] ?? '';
$startTimeInput = $_POST['start_time'] ?? $_GET['start_time'] ?? '';
$endTimeInput = $_POST['end_time'] ?? $_GET['end_time'] ?? '';
$purpose = trim($_POST['purpose'] ?? $_GET['purpose'] ?? '');

if (!isPositiveInteger($stationId)) {
    jsonError('Valid station ID is required.', 400);
}

$startTime = normalizeDateTimeForDatabase($startTimeInput);
$endTime = normalizeDateTimeForDatabase($endTimeInput);

if (!isValidReservationInterval($startTime, $endTime)) {
    jsonError('End time must be later than start time.', 400);
}

if (!isReservationStartInFuture($startTime)) {
    jsonError('Reservation start time must be in the future.', 400);
}

$station = getReservationStationContext($pdo, (int) $stationId);

if (!$station) {
    jsonError('Station not found.', 404);
}

if ((int) $station['lab_is_active'] !== 1) {
    jsonError('This laboratory is not active.', 400);
}

if ($station['station_status'] !== 'active') {
    jsonError('This station is not active for reservation.', 400);
}

$isAvailable = checkAvailability($pdo, (int) $stationId, $startTime, $endTime);

if (!$isAvailable) {
    $conflicts = getConflictingReservations($pdo, (int) $stationId, $startTime, $endTime);

    jsonError('This station is not available for the selected time interval.', 409, [
        'available' => false,
        'conflicts' => $conflicts
    ]);
}

try {
    $pdo->beginTransaction();

    $reservationId = createReservation(
        $pdo,
        (int) $userId,
        (int) $station['lab_id'],
        (int) $station['station_id'],
        $startTime,
        $endTime,
        $purpose !== '' ? mb_substr($purpose, 0, 255) : null
    );

    addReservationStatusHistory(
        $pdo,
        $reservationId,
        null,
        'active',
        (int) $userId,
        'Reservation created.'
    );

    $pdo->commit();

    jsonSuccess('Reservation created successfully.', [
        'reservation_id' => $reservationId,
        'available' => true,
        'reservation' => [
            'reservation_id' => $reservationId,
            'lab_id' => (int) $station['lab_id'],
            'station_id' => (int) $station['station_id'],
            'lab_name' => $station['lab_name'],
            'station_code' => $station['station_code'],
            'station_name' => $station['station_name'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'active'
        ]
    ]);
} catch (Exception $e) {
    $pdo->rollBack();

    if (DEBUG_MODE) {
        jsonError('Reservation creation failed: ' . $e->getMessage(), 500);
    }

    jsonError('Reservation creation failed.', 500);
}