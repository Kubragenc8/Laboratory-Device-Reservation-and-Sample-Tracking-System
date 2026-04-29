<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/validation_helper.php';
require_once __DIR__ . '/../../helpers/response_helper.php';
require_once __DIR__ . '/../../helpers/lab_helper.php';

$labId = $_GET['lab_id'] ?? $_POST['lab_id'] ?? '';

if (!isPositiveInteger($labId)) {
    jsonError('Valid laboratory ID is required.', 400);
}

$lab = getLabById($pdo, (int) $labId);

if (!$lab) {
    jsonError('Laboratory not found.', 404);
}

$stations = getStationsByLab($pdo, (int) $labId);

jsonSuccess('Stations loaded successfully.', [
    'stations' => $stations
]);