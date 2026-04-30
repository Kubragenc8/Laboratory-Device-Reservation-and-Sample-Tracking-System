<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';

$pageTitle = 'Laboratories';
$pageCss = 'labs.css';
$pageJs = 'labs.js';

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'faculty_id' => $_GET['faculty_id'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'lab_type' => trim($_GET['lab_type'] ?? '')
];

$faculties = getActiveFaculties($pdo);
$departments = getActiveDepartments($pdo);
$labTypes = getLabTypes($pdo);
$labs = getAllLabs($pdo, $filters);

function isSelectedOption($currentValue, $expectedValue): string
{
    return (string) $currentValue === (string) $expectedValue ? 'selected' : '';
}

function formatLabTypeLabel(string $type): string
{
    return ucwords(str_replace('_', ' ', $type));
}

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section" data-labs-page="true">
    <div class="container">

        <!-- HEADER -->
        <div class="card labs-hero-card" style="margin-bottom:32px;">
            <div class="labs-hero-content">

                <div>
                    <span class="badge badge-info">
                        Laboratory Directory
                    </span>

                    <h1 class="section-title" style="margin-bottom:8px; margin-top:16px;">
                        Laboratories
                    </h1>

                    <p class="section-subtitle" style="margin-bottom:0;">
                        Explore academic laboratories, compare departments,
                        review station availability and access reservation-ready environments.
                    </p>
                </div>

                <div class="labs-hero-actions">
                    <a href="reserve.php" class="btn btn-primary">
                        New Reservation
                    </a>

                    <a href="my-reservations.php" class="btn btn-outline">
                        My Reservations
                    </a>
                </div>

            </div>
        </div>

        <!-- QUICK STATS -->
        <div class="labs-stats-grid" style="margin-bottom:32px;">

            <div class="card labs-stat-card">
                <span class="labs-stat-label">Shown Laboratories</span>
                <strong id="visibleLabsCount"><?= count($labs) ?></strong>
            </div>

            <div class="card labs-stat-card">
                <span class="labs-stat-label">Faculties</span>
                <strong><?= count($faculties) ?></strong>
            </div>

            <div class="card labs-stat-card">
                <span class="labs-stat-label">Departments</span>
                <strong><?= count($departments) ?></strong>
            </div>

        </div>

        <!-- FILTER -->
        <div class="card labs-filter-card" style="margin-bottom:32px;">

            <div class="labs-filter-header">
                <div>
                    <h2 style="margin-top:0; margin-bottom:8px;">Search & Filter</h2>

                    <p class="section-subtitle" style="margin-bottom:0;">
                        Use filters to quickly find the right laboratory for your reservation.
                    </p>
                </div>

                <span class="badge badge-info" id="labsFilterModeBadge">
                    Dynamic Filter Ready
                </span>
            </div>

            <form method="GET" action="" id="labsFilterForm">

                <div class="grid grid-2">

                    <div class="form-group">
                        <label for="q" class="form-label">Search</label>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="form-control"
                            value="<?= htmlspecialchars($filters['q']) ?>"
                            placeholder="Search by laboratory, code, faculty or department"
                            autocomplete="off"
                        >

                        <small class="field-feedback">
                            You can search by lab name, lab code, faculty, department or location.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="lab_type" class="form-label">Laboratory Type</label>

                        <select id="lab_type" name="lab_type" class="form-control">
                            <option value="">All types</option>

                            <?php foreach ($labTypes as $type): ?>
                                <option
                                    value="<?= htmlspecialchars($type['lab_type']) ?>"
                                    <?= isSelectedOption($filters['lab_type'], $type['lab_type']) ?>
                                >
                                    <?= htmlspecialchars(formatLabTypeLabel($type['lab_type'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="grid grid-2">

                    <div class="form-group">
                        <label for="faculty_id" class="form-label">Faculty</label>

                        <select id="faculty_id" name="faculty_id" class="form-control">
                            <option value="">All faculties</option>

                            <?php foreach ($faculties as $faculty): ?>
                                <option
                                    value="<?= (int) $faculty['faculty_id'] ?>"
                                    <?= isSelectedOption($filters['faculty_id'], $faculty['faculty_id']) ?>
                                >
                                    <?= htmlspecialchars($faculty['faculty_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="department_id" class="form-label">Department</label>

                        <select id="department_id" name="department_id" class="form-control">
                            <option value="">All departments</option>

                            <?php foreach ($departments as $department): ?>
                                <option
                                    value="<?= (int) $department['department_id'] ?>"
                                    data-faculty-id="<?= (int) $department['faculty_id'] ?>"
                                    <?= isSelectedOption($filters['department_id'], $department['department_id']) ?>
                                >
                                    <?= htmlspecialchars($department['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <small class="field-feedback">
                            Department options can be filtered by selected faculty.
                        </small>
                    </div>

                </div>

                <div class="labs-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Apply Filters
                    </button>

                    <button type="button" class="btn btn-secondary" id="labsClientFilterButton">
                        Filter on Page
                    </button>

                    <a href="labs.php" class="btn btn-outline">
                        Clear Filters
                    </a>
                </div>

            </form>

        </div>

        <!-- LAB GRID -->
        <div
            class="grid grid-3 labs-grid"
            id="labsGrid"
            data-total-labs="<?= count($labs) ?>"
        >

            <?php if (count($labs) > 0): ?>

                <?php foreach ($labs as $lab): ?>
                    <?php
                    $activeStationCount = (int) ($lab['active_station_count'] ?? 0);
                    $totalStationCount = (int) ($lab['total_station_count'] ?? 0);
                    $isAvailable = $activeStationCount > 0;

                    $searchText = strtolower(
                        ($lab['lab_name'] ?? '') . ' '
                        . ($lab['lab_code'] ?? '') . ' '
                        . ($lab['faculty_name'] ?? '') . ' '
                        . ($lab['department_name'] ?? '') . ' '
                        . ($lab['lab_type'] ?? '') . ' '
                        . ($lab['location'] ?? '')
                    );
                    ?>

                    <article
                        class="card card-hover lab-card"
                        data-lab-card="true"
                        data-search="<?= htmlspecialchars($searchText) ?>"
                        data-faculty-id="<?= (int) $lab['faculty_id'] ?>"
                        data-department-id="<?= (int) $lab['department_id'] ?>"
                        data-lab-type="<?= htmlspecialchars($lab['lab_type']) ?>"
                        data-active-stations="<?= $activeStationCount ?>"
                    >

                        <div class="lab-card-header">

                            <div>
                                <span class="lab-code">
                                    <?= htmlspecialchars($lab['lab_code']) ?>
                                </span>

                                <h3 class="lab-card-title">
                                    <?= htmlspecialchars($lab['lab_name']) ?>
                                </h3>
                            </div>

                            <span class="badge <?= $isAvailable ? 'badge-success' : 'badge-warning' ?>">
                                <?= $isAvailable ? 'Available' : 'No Active Station' ?>
                            </span>

                        </div>

                        <div class="lab-card-meta">

                            <div class="lab-meta-row">
                                <span>Faculty</span>
                                <strong><?= htmlspecialchars($lab['faculty_name']) ?></strong>
                            </div>

                            <div class="lab-meta-row">
                                <span>Department</span>
                                <strong><?= htmlspecialchars($lab['department_name']) ?></strong>
                            </div>

                            <div class="lab-meta-row">
                                <span>Type</span>
                                <strong><?= htmlspecialchars(formatLabTypeLabel($lab['lab_type'])) ?></strong>
                            </div>

                            <div class="lab-meta-row">
                                <span>Location</span>
                                <strong><?= htmlspecialchars($lab['location'] ?? '-') ?></strong>
                            </div>

                        </div>

                        <div class="lab-station-summary">
                            <div>
                                <span class="lab-station-number">
                                    <?= $activeStationCount ?>
                                </span>

                                <span class="lab-station-label">
                                    active stations
                                </span>
                            </div>

                            <div>
                                <span class="lab-station-number muted">
                                    <?= $totalStationCount ?>
                                </span>

                                <span class="lab-station-label">
                                    total stations
                                </span>
                            </div>
                        </div>

                        <div class="lab-card-actions">
                            <a
                                href="lab-detail.php?id=<?= (int) $lab['lab_id'] ?>"
                                class="btn btn-primary"
                            >
                                View Details
                            </a>

                            <a
                                href="reserve.php?lab_id=<?= (int) $lab['lab_id'] ?>"
                                class="btn btn-outline"
                            >
                                Reserve
                            </a>
                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <!-- EMPTY STATE -->
        <div
            class="card labs-empty-state"
            id="labsEmptyState"
            style="<?= count($labs) > 0 ? 'display:none;' : '' ?>"
        >
            <span class="badge badge-warning">
                No Result
            </span>

            <h3>No laboratory found</h3>

            <p class="section-subtitle">
                Try changing your filters or search terms.
            </p>

            <a href="labs.php" class="btn btn-primary">
                Clear Filters
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>