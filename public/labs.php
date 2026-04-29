<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/lab_helper.php';

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laboratories - Laboratory Reservation System</title>
</head>
<body>

<h1>Laboratories</h1>

<p>
    Welcome, <?= htmlspecialchars(getCurrentUserName()) ?>.
</p>

<p>
    <a href="dashboard.php">Dashboard</a> |
    <a href="my-reservations.php">My Reservations</a> |
    <a href="logout.php">Logout</a>
</p>

<hr>

<h2>Search and Filter</h2>

<form method="GET" action="">
    <div>
        <label for="q">Search</label><br>
        <input
            type="text"
            id="q"
            name="q"
            value="<?= htmlspecialchars($filters['q']) ?>"
            placeholder="Search by laboratory, code, department or faculty"
        >
    </div>

    <br>

    <div>
        <label for="faculty_id">Faculty</label><br>
        <select id="faculty_id" name="faculty_id">
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

    <br>

    <div>
        <label for="department_id">Department</label><br>
        <select id="department_id" name="department_id">
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

    <br>

    <div>
        <label for="lab_type">Laboratory Type</label><br>
        <select id="lab_type" name="lab_type">
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

    <br>

    <button type="submit">Apply Filters</button>
    <a href="labs.php">Clear Filters</a>
</form>

<hr>

<h2>Laboratory List</h2>

<?php if (count($labs) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Code</th>
                <th>Laboratory</th>
                <th>Faculty</th>
                <th>Department</th>
                <th>Type</th>
                <th>Location</th>
                <th>Active Stations</th>
                <th>Total Stations</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($labs as $lab): ?>
                <tr>
                    <td><?= htmlspecialchars($lab['lab_code']) ?></td>
                    <td><?= htmlspecialchars($lab['lab_name']) ?></td>
                    <td><?= htmlspecialchars($lab['faculty_name']) ?></td>
                    <td><?= htmlspecialchars($lab['department_name']) ?></td>
                    <td><?= htmlspecialchars($lab['lab_type']) ?></td>
                    <td><?= htmlspecialchars($lab['location'] ?? '-') ?></td>
                    <td><?= (int) $lab['active_station_count'] ?></td>
                    <td><?= (int) $lab['total_station_count'] ?></td>
                    <td>
                        <a href="lab-detail.php?id=<?= (int) $lab['lab_id'] ?>">View Details</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No laboratory found.</p>
<?php endif; ?>

</body>
</html>