<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/validation_helper.php';
require_once __DIR__ . '/../../helpers/response_helper.php';
require_once __DIR__ . '/../../helpers/lab_helper.php';

$stationId = $_GET['station_id'] ?? $_POST['station_id'] ?? '';

if (!isPositiveInteger($stationId)) {
    jsonError('Valid station ID is required.', 400);
}

$station = getStationById($pdo, (int) $stationId);

if (!$station) {
    jsonError('Station not found.', 404);
}

$equipmentList = getStationEquipment($pdo, (int) $stationId);

jsonSuccess('Station equipment loaded successfully.', [
    'station' => $station,
    'equipment' => $equipmentList
]);