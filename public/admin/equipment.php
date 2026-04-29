<?php

require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/lab_helper.php';

$pageTitle = 'Admin Equipment';

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'lab_id' => $_GET['lab_id'] ?? '',
    'station_id' => $_GET['station_id'] ?? '',
    'category' => trim($_GET['category'] ?? ''),
    'status' => $_GET['status'] ?? ''
];

$labs = getAllLabs($pdo);

$stations = $pdo->query("
    SELECT
        w.station_id,
        w.station_code,
        w.station_name,
        l.lab_code,
        l.lab_name
    FROM workstations w
    INNER JOIN laboratories l
        ON w.lab_id = l.lab_id
    ORDER BY l.lab_code ASC, w.station_code ASC
")->fetchAll();

$categories = $pdo->query("
    SELECT DISTINCT category
    FROM equipment_types
    ORDER BY category ASC
")->fetchAll();

$allowedStatuses = ['available', 'in_use', 'maintenance', 'retired'];

if ($filters['lab_id'] !== '' && !filter_var($filters['lab_id'], FILTER_VALIDATE_INT)) {
    $filters['lab_id'] = '';
}

if ($filters['station_id'] !== '' && !filter_var($filters['station_id'], FILTER_VALIDATE_INT)) {
    $filters['station_id'] = '';
}

if ($filters['status'] !== '' && !in_array($filters['status'], $allowedStatuses, true)) {
    $filters['status'] = '';
}

$sql = "
    SELECT
        ei.equipment_id,
        ei.equipment_type_id,
        ei.lab_id,
        ei.station_id,
        ei.asset_code,
        ei.brand,
        ei.model,
        ei.status,
        ei.notes,
        et.equipment_name,
        et.category,
        l.lab_code,
        l.lab_name,
        w.station_code,
        w.station_name
    FROM equipment_instances ei
    INNER JOIN equipment_types et
        ON ei.equipment_type_id = et.equipment_type_id
    INNER JOIN laboratories l
        ON ei.lab_id = l.lab_id
    LEFT JOIN workstations w
        ON ei.station_id = w.station_id
    WHERE 1 = 1
";

$params = [];

if ($filters['q'] !== '') {
    $sql .= "
        AND (
            ei.asset_code LIKE :search_asset_code
            OR ei.brand LIKE :search_brand
            OR ei.model LIKE :search_model
            OR et.equipment_name LIKE :search_equipment_name
            OR et.category LIKE :search_category
            OR l.lab_code LIKE :search_lab_code
            OR l.lab_name LIKE :search_lab_name
            OR w.station_code LIKE :search_station_code
            OR w.station_name LIKE :search_station_name
        )
    ";

    $searchValue = '%' . $filters['q'] . '%';

    $params[':search_asset_code'] = $searchValue;
    $params[':search_brand'] = $searchValue;
    $params[':search_model'] = $searchValue;
    $params[':search_equipment_name'] = $searchValue;
    $params[':search_category'] = $searchValue;
    $params[':search_lab_code'] = $searchValue;
    $params[':search_lab_name'] = $searchValue;
    $params[':search_station_code'] = $searchValue;
    $params[':search_station_name'] = $searchValue;
}

if ($filters['lab_id'] !== '') {
    $sql .= " AND ei.lab_id = :lab_id";
    $params[':lab_id'] = (int) $filters['lab_id'];
}

if ($filters['station_id'] !== '') {
    $sql .= " AND ei.station_id = :station_id";
    $params[':station_id'] = (int) $filters['station_id'];
}

if ($filters['category'] !== '') {
    $sql .= " AND et.category = :category";
    $params[':category'] = $filters['category'];
}

if ($filters['status'] !== '') {
    $sql .= " AND ei.status = :status";
    $params[':status'] = $filters['status'];
}

$sql .= "
    ORDER BY
        l.lab_code ASC,
        w.station_code ASC,
        et.category ASC,
        et.equipment_name ASC,
        ei.asset_code ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipmentList = $stmt->fetchAll();

function selectedEquipmentAdminOption($currentValue, $expectedValue): string
{
    return (string) $currentValue === (string) $expectedValue ? 'selected' : '';
}

require_once __DIR__ . '/../../includes/header.php';

?>

<h1>Admin Equipment</h1>

<h2>Filters</h2>

<form method="GET" action="">
    <div>
        <label for="q">Search</label><br>
        <input
            type="text"
            id="q"
            name="q"
            value="<?= htmlspecialchars($filters['q']) ?>"
            placeholder="Search asset, equipment, brand, model, laboratory or station"
        >
    </div>

    <br>

    <div>
        <label for="lab_id">Laboratory</label><br>
        <select id="lab_id" name="lab_id">
            <option value="">All laboratories</option>

            <?php foreach ($labs as $lab): ?>
                <option
                    value="<?= (int) $lab['lab_id'] ?>"
                    <?= selectedEquipmentAdminOption($filters['lab_id'], $lab['lab_id']) ?>
                >
                    <?= htmlspecialchars($lab['lab_code'] . ' - ' . $lab['lab_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <br>

    <div>
        <label for="station_id">Station</label><br>
        <select id="station_id" name="station_id">
            <option value="">All stations</option>

            <?php foreach ($stations as $station): ?>
                <option
                    value="<?= (int) $station['station_id'] ?>"
                    <?= selectedEquipmentAdminOption($filters['station_id'], $station['station_id']) ?>
                >
                    <?= htmlspecialchars(
                        $station['lab_code'] . ' - ' .
                        $station['station_code'] . ' - ' .
                        $station['station_name']
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <br>

    <div>
        <label for="category">Category</label><br>
        <select id="category" name="category">
            <option value="">All categories</option>

            <?php foreach ($categories as $category): ?>
                <option
                    value="<?= htmlspecialchars($category['category']) ?>"
                    <?= selectedEquipmentAdminOption($filters['category'], $category['category']) ?>
                >
                    <?= htmlspecialchars($category['category']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <br>

    <div>
        <label for="status">Status</label><br>
        <select id="status" name="status">
            <option value="">All statuses</option>

            <?php foreach ($allowedStatuses as $status): ?>
                <option
                    value="<?= htmlspecialchars($status) ?>"
                    <?= selectedEquipmentAdminOption($filters['status'], $status) ?>
                >
                    <?= htmlspecialchars($status) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <br>

    <button type="submit">Apply Filters</button>
    <a href="equipment.php">Clear Filters</a>
</form>

<hr>

<h2>Equipment List</h2>

<p>
    Total equipment shown: <?= count($equipmentList) ?>
</p>

<?php if (count($equipmentList) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Asset Code</th>
                <th>Equipment</th>
                <th>Category</th>
                <th>Laboratory</th>
                <th>Station</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($equipmentList as $equipment): ?>
                <tr>
                    <td><?= (int) $equipment['equipment_id'] ?></td>

                    <td><?= htmlspecialchars($equipment['asset_code']) ?></td>

                    <td><?= htmlspecialchars($equipment['equipment_name']) ?></td>

                    <td><?= htmlspecialchars($equipment['category']) ?></td>

                    <td>
                        <?= htmlspecialchars($equipment['lab_code'] . ' - ' . $equipment['lab_name']) ?>
                    </td>

                    <td>
                        <?php if ($equipment['station_id']): ?>
                            <a href="../station-detail.php?id=<?= (int) $equipment['station_id'] ?>">
                                <?= htmlspecialchars($equipment['station_code'] . ' - ' . $equipment['station_name']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($equipment['brand'] ?? '-') ?></td>

                    <td><?= htmlspecialchars($equipment['model'] ?? '-') ?></td>

                    <td><?= htmlspecialchars($equipment['status']) ?></td>

                    <td><?= htmlspecialchars($equipment['notes'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No equipment found.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>