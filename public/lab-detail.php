<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';
require_once __DIR__ . '/../helpers/reservation_helper.php';

syncExpiredReservations($pdo);

$labId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$labId) {
    http_response_code(400);
    die('Invalid laboratory ID.');
}

$lab = getLabById($pdo, (int) $labId);

if (!$lab) {
    http_response_code(404);
    die('Laboratory not found.');
}

$stations = getStationsByLab($pdo, (int) $labId);
$equipmentSummary = getLabEquipmentSummary($pdo, (int) $labId);

$pageTitle = 'Laboratory Detail';
$pageCss = 'labs.css';

function formatLabDetailType(?string $type): string
{
    $type = trim((string) $type);

    if ($type === '') {
        return '-';
    }

    return ucwords(str_replace('_', ' ', $type));
}

function labDetailAvailabilityBadgeClass(string $statusLabel): string
{
    if ($statusLabel === 'Available') {
        return 'badge-success';
    }

    if ($statusLabel === 'Reserved Now') {
        return 'badge-warning';
    }

    if ($statusLabel === 'Maintenance' || $statusLabel === 'Passive') {
        return 'badge-error';
    }

    return 'badge-warning';
}

function labDetailStationStatusBadgeClass(string $status): string
{
    if ($status === 'active') {
        return 'badge-success';
    }

    if ($status === 'maintenance') {
        return 'badge-warning';
    }

    if ($status === 'passive') {
        return 'badge-error';
    }

    return 'badge-info';
}

$stationCards = [];
$activeStationCount = 0;
$availableNowCount = 0;
$totalEquipmentCount = 0;
$availableEquipmentCount = 0;
$maintenanceEquipmentCount = 0;
$passiveEquipmentCount = 0;

foreach ($stations as $station) {
    $availability = getStationComputedAvailability(
        $pdo,
        (int) $station['station_id']
    );

    if ($station['status'] === 'active') {
        $activeStationCount++;
    }

    if ($availability['status_label'] === 'Available') {
        $availableNowCount++;
    }

    $totalEquipmentCount += (int) ($station['total_equipment_count'] ?? 0);
    $availableEquipmentCount += (int) ($station['available_equipment_count'] ?? 0);
    $maintenanceEquipmentCount += (int) ($station['maintenance_equipment_count'] ?? 0);
    $passiveEquipmentCount += (int) ($station['passive_equipment_count'] ?? 0);

    $stationCards[] = [
        'station' => $station,
        'availability' => $availability
    ];
}

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section lab-detail-page">
    <div class="container">

        <!-- TOP ACTIONS -->
        <div class="lab-detail-topbar">
            <a href="labs.php" class="btn btn-outline">
                ← Back to Laboratories
            </a>

            <a
                href="reserve.php?lab_id=<?= (int) $lab['lab_id'] ?>"
                class="btn btn-primary"
            >
                Reserve from This Lab
            </a>
        </div>

        <!-- HERO -->
        <div class="card lab-detail-hero-card" style="margin-bottom:32px;">
            <div class="lab-detail-hero-grid">

                <div>
                    <span class="badge badge-info">
                        <?= htmlspecialchars($lab['lab_code']) ?>
                    </span>

                    <h1 class="section-title" style="margin-bottom:12px; margin-top:16px;">
                        <?= htmlspecialchars($lab['lab_name']) ?>
                    </h1>

                    <p class="section-subtitle" style="margin-bottom:0;">
                        <?= nl2br(htmlspecialchars($lab['description'] ?? 'No description available.')) ?>
                    </p>
                </div>

                <div class="lab-detail-info-card">

                    <div class="lab-detail-info-row">
                        <span>Type</span>
                        <strong><?= htmlspecialchars(formatLabDetailType($lab['lab_type'] ?? '')) ?></strong>
                    </div>

                    <div class="lab-detail-info-row">
                        <span>Faculty</span>
                        <strong><?= htmlspecialchars($lab['faculty_name']) ?></strong>
                    </div>

                    <div class="lab-detail-info-row">
                        <span>Department</span>
                        <strong><?= htmlspecialchars($lab['department_name']) ?></strong>
                    </div>

                    <div class="lab-detail-info-row">
                        <span>Location</span>
                        <strong><?= htmlspecialchars($lab['location'] ?? '-') ?></strong>
                    </div>

                    <div class="lab-detail-info-row">
                        <span>Phone</span>
                        <strong><?= htmlspecialchars($lab['phone'] ?? '-') ?></strong>
                    </div>

                </div>

            </div>
        </div>

        <!-- KPI -->
        <div class="lab-detail-kpi-grid" style="margin-bottom:32px;">

            <div class="card card-hover lab-detail-kpi-card">
                <span>Total Stations</span>
                <strong><?= count($stations) ?></strong>
                <p>All stations connected to this laboratory.</p>
            </div>

            <div class="card card-hover lab-detail-kpi-card is-active">
                <span>Active Stations</span>
                <strong><?= (int) $activeStationCount ?></strong>
                <p>Stations open for reservation workflow.</p>
            </div>

            <div class="card card-hover lab-detail-kpi-card is-available">
                <span>Available Now</span>
                <strong><?= (int) $availableNowCount ?></strong>
                <p>Stations not currently reserved.</p>
            </div>

            <div class="card card-hover lab-detail-kpi-card is-equipment">
                <span>Total Equipment</span>
                <strong><?= (int) $totalEquipmentCount ?></strong>
                <p>Equipment assigned to stations in this lab.</p>
            </div>

        </div>

        <!-- EQUIPMENT SUMMARY -->
        <div class="card lab-detail-section-card" style="margin-bottom:32px;">
            <div class="lab-detail-section-header">
                <div>
                    <h2 style="margin-top:0; margin-bottom:8px;">
                        Equipment Summary
                    </h2>

                    <p class="section-subtitle" style="margin-bottom:0;">
                        Equipment types and status counts connected to this laboratory.
                    </p>
                </div>

                <span class="badge badge-info">
                    <?= count($equipmentSummary) ?> Type<?= count($equipmentSummary) === 1 ? '' : 's' ?>
                </span>
            </div>

            <div class="lab-detail-equipment-overview">

                <div>
                    <span>Total</span>
                    <strong><?= (int) $totalEquipmentCount ?></strong>
                </div>

                <div>
                    <span>Available</span>
                    <strong><?= (int) $availableEquipmentCount ?></strong>
                </div>

                <div>
                    <span>Maintenance</span>
                    <strong><?= (int) $maintenanceEquipmentCount ?></strong>
                </div>

                <div>
                    <span>Passive</span>
                    <strong><?= (int) $passiveEquipmentCount ?></strong>
                </div>

            </div>

            <?php if (count($equipmentSummary) > 0): ?>
                <div class="table-wrapper lab-detail-table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Category</th>
                                <th>Total</th>
                                <th>Available</th>
                                <th>Maintenance</th>
                                <th>Passive</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($equipmentSummary as $equipment): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($equipment['equipment_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($equipment['category']) ?>
                                    </td>

                                    <td>
                                        <?= (int) $equipment['total_count'] ?>
                                    </td>

                                    <td>
                                        <span class="badge badge-success">
                                            <?= (int) $equipment['available_count'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge badge-warning">
                                            <?= (int) $equipment['maintenance_count'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge badge-error">
                                            <?= (int) $equipment['passive_count'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-success" style="margin-bottom:0;">
                    No equipment found for this laboratory.
                </div>
            <?php endif; ?>
        </div>

        <!-- STATIONS -->
        <div class="lab-detail-section-header" style="margin-bottom:24px;">
            <div>
                <h2 class="section-title" style="margin-bottom:8px;">
                    Stations
                </h2>

                <p class="section-subtitle" style="margin-bottom:0;">
                    Select a station to inspect equipment or start a reservation.
                </p>
            </div>

            <span class="badge badge-info">
                <?= count($stations) ?> Station<?= count($stations) === 1 ? '' : 's' ?>
            </span>
        </div>

        <?php if (count($stationCards) > 0): ?>
            <div class="lab-detail-station-grid">

                <?php foreach ($stationCards as $item): ?>
                    <?php
                    $station = $item['station'];
                    $availability = $item['availability'];
                    $availabilityLabel = $availability['status_label'] ?? 'Unknown';
                    ?>

                    <article class="card card-hover lab-detail-station-card">

                        <div class="lab-detail-station-header">
                            <div>
                                <span class="lab-code">
                                    <?= htmlspecialchars($station['station_code']) ?>
                                </span>

                                <h3>
                                    <?= htmlspecialchars($station['station_name']) ?>
                                </h3>
                            </div>

                            <span class="badge <?= labDetailAvailabilityBadgeClass($availabilityLabel) ?>">
                                <?= htmlspecialchars($availabilityLabel) ?>
                            </span>
                        </div>

                        <div class="lab-detail-station-meta">

                            <div class="lab-detail-station-meta-row">
                                <span>Type</span>
                                <strong><?= htmlspecialchars($station['type_name']) ?></strong>
                            </div>

                            <div class="lab-detail-station-meta-row">
                                <span>Capacity</span>
                                <strong><?= (int) $station['capacity'] ?></strong>
                            </div>

                            <div class="lab-detail-station-meta-row">
                                <span>Status</span>
                                <strong>
                                    <span class="badge <?= labDetailStationStatusBadgeClass($station['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($station['status'])) ?>
                                    </span>
                                </strong>
                            </div>

                        </div>

                        <div class="lab-detail-station-equipment-grid">

                            <div>
                                <span>Total Equipment</span>
                                <strong><?= (int) $station['total_equipment_count'] ?></strong>
                            </div>

                            <div>
                                <span>Available</span>
                                <strong><?= (int) $station['available_equipment_count'] ?></strong>
                            </div>

                            <div>
                                <span>Maintenance</span>
                                <strong><?= (int) $station['maintenance_equipment_count'] ?></strong>
                            </div>

                            <div>
                                <span>Passive</span>
                                <strong><?= (int) $station['passive_equipment_count'] ?></strong>
                            </div>

                        </div>

                        <div class="lab-detail-station-actions">
                            <a
                                href="station-detail.php?id=<?= (int) $station['station_id'] ?>"
                                class="btn btn-outline"
                            >
                                View Station
                            </a>

                            <?php if ($station['status'] === 'active'): ?>
                                <a
                                    href="reserve.php?lab_id=<?= (int) $lab['lab_id'] ?>&station_id=<?= (int) $station['station_id'] ?>"
                                    class="btn btn-primary"
                                >
                                    Reserve
                                </a>
                            <?php else: ?>
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    disabled
                                >
                                    Reservation Closed
                                </button>
                            <?php endif; ?>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <div class="card labs-empty">
                <span class="badge badge-warning">
                    No Station
                </span>

                <h3>No station found for this laboratory.</h3>

                <p class="section-subtitle">
                    This laboratory currently has no active station listings.
                </p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>