<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';

$pageTitle = 'Laboratories';
$pageCss = 'labs.css';

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

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- HEADER -->
        <div class="card" style="margin-bottom:32px;">
            <h1 class="section-title" style="margin-bottom:8px;">
                Laboratories
            </h1>

            <p class="section-subtitle" style="margin-bottom:0;">
                Explore academic laboratories, compare departments,
                review station availability and access reservation-ready environments.
            </p>
        </div>

        <!-- FILTER -->
        <div class="card" style="margin-bottom:32px;">

            <h2 style="margin-top:0;">Search & Filter</h2>

            <form method="GET" action="">

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
                        >
                    </div>

                    <div class="form-group">
                        <label for="lab_type" class="form-label">Laboratory Type</label>
                        <select id="lab_type" name="lab_type" class="form-control">
                            <option value="">All types</option>

                            <?php foreach ($labTypes as $type): ?>
                                <option
                                    value="<?= htmlspecialchars($type['lab_type']) ?>"
                                    <?= $filters['lab_type'] === $type['lab_type'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($type['lab_type']) ?>
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
                                    <?= (string) $filters['faculty_id'] === (string) $faculty['faculty_id'] ? 'selected' : '' ?>
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
                                    <?= (string) $filters['department_id'] === (string) $department['department_id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($department['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="flex" style="gap:16px; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        Apply Filters
                    </button>

                    <a href="labs.php" class="btn btn-outline">
                        Clear Filters
                    </a>
                </div>

            </form>

        </div>

        <!-- LAB GRID -->
        <div class="grid grid-3">

            <?php if (count($labs) > 0): ?>

                <?php foreach ($labs as $lab): ?>

                    <div class="card card-hover">

                        <div style="display:flex; justify-content:space-between; align-items:start; gap:12px;">

                            <div>
                                <h3 style="margin-top:0; margin-bottom:8px;">
                                    <?= htmlspecialchars($lab['lab_name']) ?>
                                </h3>

                                <p style="margin:0; color:var(--color-muted); font-size:14px;">
                                    <?= htmlspecialchars($lab['lab_code']) ?>
                                </p>
                            </div>

                            <span class="badge badge-success">
                                Available
                            </span>

                        </div>

                        <div style="margin-top:20px; display:flex; flex-direction:column; gap:8px;">

                            <p><strong>Faculty:</strong> <?= htmlspecialchars($lab['faculty_name']) ?></p>

                            <p><strong>Department:</strong> <?= htmlspecialchars($lab['department_name']) ?></p>

                            <p><strong>Type:</strong> <?= htmlspecialchars($lab['lab_type']) ?></p>

                            <p><strong>Location:</strong> <?= htmlspecialchars($lab['location'] ?? '-') ?></p>

                            <p>
                                <strong>Stations:</strong>
                                <?= (int) $lab['active_station_count'] ?> Active /
                                <?= (int) $lab['total_station_count'] ?> Total
                            </p>

                        </div>

                        <div style="margin-top:24px;">
                            <a href="lab-detail.php?id=<?= (int) $lab['lab_id'] ?>" class="btn btn-primary" style="width:100%;">
                                View Details
                            </a>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="card" style="grid-column:1/-1; text-align:center;">
                    <h3>No laboratory found</h3>
                    <p class="section-subtitle">
                        Try changing your filters or search terms.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>