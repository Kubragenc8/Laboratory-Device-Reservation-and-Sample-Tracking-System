<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';

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

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- BACK -->
        <div style="margin-bottom:24px;">
            <a href="labs.php" class="btn btn-outline">
                ← Back to Laboratories
            </a>
        </div>

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">

            <div class="grid grid-2" style="align-items:start;">

                <div>
                    <p style="color:var(--color-muted); margin-bottom:8px;">
                        <?= htmlspecialchars($lab['lab_code']) ?>
                    </p>

                    <h1 class="section-title" style="margin-bottom:12px;">
                        <?= htmlspecialchars($lab['lab_name']) ?>
                    </h1>

                    <p class="section-subtitle">
                        <?= nl2br(htmlspecialchars($lab['description'] ?? 'No description available.')) ?>
                    </p>
                </div>

                <div class="card" style="background:var(--color-surface-soft);">
                    <p><strong>Type:</strong> <?= htmlspecialchars($lab['lab_type']) ?></p>
                    <p><strong>Faculty:</strong> <?= htmlspecialchars($lab['faculty_name']) ?></p>
                    <p><strong>Department:</strong> <?= htmlspecialchars($lab['department_name']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($lab['location'] ?? '-') ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($lab['phone'] ?? '-') ?></p>
                </div>

            </div>

        </div>

        <!-- EQUIPMENT -->
        <div class="card" style="margin-bottom:32px;">

            <h2 style="margin-top:0;">Equipment Summary</h2>

            <?php if (count($equipmentSummary) > 0): ?>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Category</th>
                                <th>Total Count</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($equipmentSummary as $equipment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($equipment['equipment_name']) ?></td>
                                    <td><?= htmlspecialchars($equipment['category']) ?></td>
                                    <td><?= (int) $equipment['total_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="alert alert-success">
                    No equipment found for this laboratory.
                </div>

            <?php endif; ?>

        </div>

        <!-- STATIONS -->
        <div>

            <h2 class="section-title">Stations</h2>

            <?php if (count($stations) > 0): ?>

                <div class="grid grid-3">

                    <?php foreach ($stations as $station): ?>

                        <div class="card card-hover lab-card">

                            <div class="lab-card-top">

                                <div>
                                    <p class="lab-code">
                                        <?= htmlspecialchars($station['station_code']) ?>
                                    </p>

                                    <h3 style="margin:0;">
                                        <?= htmlspecialchars($station['station_name']) ?>
                                    </h3>
                                </div>

                                <?php if ($station['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= htmlspecialchars($station['status']) ?></span>
                                <?php endif; ?>

                            </div>

                            <div class="lab-meta">

                                <p><strong>Type:</strong> <?= htmlspecialchars($station['type_name']) ?></p>

                                <p><strong>Capacity:</strong> <?= (int) $station['capacity'] ?></p>

                                <p><strong>Equipment:</strong> <?= (int) $station['equipment_count'] ?></p>

                            </div>

                            <div class="lab-footer">

                                <a href="station-detail.php?id=<?= (int) $station['station_id'] ?>" class="btn btn-outline" style="width:100%; margin-bottom:12px;">
                                    View Station
                                </a>

                                <?php if ($station['status'] === 'active'): ?>
                                    <a
                                        href="reserve.php?lab_id=<?= (int) $lab['lab_id'] ?>&station_id=<?= (int) $station['station_id'] ?>"
                                        class="btn btn-primary"
                                        style="width:100%;"
                                    >
                                        Reserve
                                    </a>
                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="card labs-empty">
                    <h3>No station found for this laboratory.</h3>
                    <p class="section-subtitle">
                        This laboratory currently has no active station listings.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>