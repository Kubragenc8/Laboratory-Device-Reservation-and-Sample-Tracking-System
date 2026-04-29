<?php

function getActiveFaculties(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT faculty_id, faculty_name
        FROM faculties
        WHERE is_active = 1
        ORDER BY faculty_name ASC
    ");

    return $stmt->fetchAll();
}

function getActiveDepartments(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT department_id, faculty_id, department_name
        FROM departments
        WHERE is_active = 1
        ORDER BY department_name ASC
    ");

    return $stmt->fetchAll();
}

function getLabTypes(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT DISTINCT lab_type
        FROM laboratories
        WHERE is_active = 1
        ORDER BY lab_type ASC
    ");

    return $stmt->fetchAll();
}

function getAllLabs(PDO $pdo, array $filters = []): array
{
    $sql = "
        SELECT
            l.lab_id,
            l.department_id,
            l.lab_name,
            l.lab_code,
            l.lab_type,
            l.location,
            l.phone,
            l.description,
            l.is_active,
            d.department_name,
            f.faculty_id,
            f.faculty_name,
            COUNT(w.station_id) AS total_station_count,
            COALESCE(SUM(CASE WHEN w.status = 'active' THEN 1 ELSE 0 END), 0) AS active_station_count
        FROM laboratories l
        INNER JOIN departments d
            ON l.department_id = d.department_id
        INNER JOIN faculties f
            ON d.faculty_id = f.faculty_id
        LEFT JOIN workstations w
            ON l.lab_id = w.lab_id
        WHERE l.is_active = 1
    ";

    $params = [];

    if (!empty($filters['q'])) {
        $sql .= "
            AND (
                l.lab_name LIKE :search
                OR l.lab_code LIKE :search
                OR l.lab_type LIKE :search
                OR d.department_name LIKE :search
                OR f.faculty_name LIKE :search
            )
        ";

        $params[':search'] = '%' . $filters['q'] . '%';
    }

    if (!empty($filters['faculty_id'])) {
        $sql .= " AND f.faculty_id = :faculty_id";
        $params[':faculty_id'] = (int) $filters['faculty_id'];
    }

    if (!empty($filters['department_id'])) {
        $sql .= " AND d.department_id = :department_id";
        $params[':department_id'] = (int) $filters['department_id'];
    }

    if (!empty($filters['lab_type'])) {
        $sql .= " AND l.lab_type = :lab_type";
        $params[':lab_type'] = $filters['lab_type'];
    }

    $sql .= "
        GROUP BY
            l.lab_id,
            l.department_id,
            l.lab_name,
            l.lab_code,
            l.lab_type,
            l.location,
            l.phone,
            l.description,
            l.is_active,
            d.department_name,
            f.faculty_id,
            f.faculty_name
        ORDER BY l.lab_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getLabById(PDO $pdo, int $labId): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            l.lab_id,
            l.department_id,
            l.lab_name,
            l.lab_code,
            l.lab_type,
            l.location,
            l.phone,
            l.description,
            l.is_active,
            l.created_at,
            d.department_name,
            f.faculty_id,
            f.faculty_name
        FROM laboratories l
        INNER JOIN departments d
            ON l.department_id = d.department_id
        INNER JOIN faculties f
            ON d.faculty_id = f.faculty_id
        WHERE l.lab_id = :lab_id
          AND l.is_active = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':lab_id' => $labId
    ]);

    $lab = $stmt->fetch();

    return $lab ?: null;
}

function getStationsByLab(PDO $pdo, int $labId): array
{
    $stmt = $pdo->prepare("
        SELECT
            w.station_id,
            w.lab_id,
            w.station_type_id,
            w.station_code,
            w.station_name,
            w.capacity,
            w.status,
            w.notes,
            st.type_name,
            COUNT(ei.equipment_id) AS equipment_count
        FROM workstations w
        INNER JOIN station_types st
            ON w.station_type_id = st.station_type_id
        LEFT JOIN equipment_instances ei
            ON w.station_id = ei.station_id
        WHERE w.lab_id = :lab_id
        GROUP BY
            w.station_id,
            w.lab_id,
            w.station_type_id,
            w.station_code,
            w.station_name,
            w.capacity,
            w.status,
            w.notes,
            st.type_name
        ORDER BY w.station_code ASC
    ");

    $stmt->execute([
        ':lab_id' => $labId
    ]);

    return $stmt->fetchAll();
}

function getLabEquipmentSummary(PDO $pdo, int $labId): array
{
    $stmt = $pdo->prepare("
        SELECT
            et.equipment_name,
            et.category,
            COUNT(ei.equipment_id) AS total_count
        FROM equipment_instances ei
        INNER JOIN equipment_types et
            ON ei.equipment_type_id = et.equipment_type_id
        WHERE ei.lab_id = :lab_id
        GROUP BY
            et.equipment_name,
            et.category
        ORDER BY et.category ASC, et.equipment_name ASC
    ");

    $stmt->execute([
        ':lab_id' => $labId
    ]);

    return $stmt->fetchAll();
}

function getStationById(PDO $pdo, int $stationId): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            w.station_id,
            w.lab_id,
            w.station_type_id,
            w.station_code,
            w.station_name,
            w.capacity,
            w.status,
            w.notes,
            st.type_name,
            l.lab_name,
            l.lab_code,
            l.lab_type,
            l.location,
            d.department_name,
            f.faculty_name
        FROM workstations w
        INNER JOIN station_types st
            ON w.station_type_id = st.station_type_id
        INNER JOIN laboratories l
            ON w.lab_id = l.lab_id
        INNER JOIN departments d
            ON l.department_id = d.department_id
        INNER JOIN faculties f
            ON d.faculty_id = f.faculty_id
        WHERE w.station_id = :station_id
        LIMIT 1
    ");

    $stmt->execute([
        ':station_id' => $stationId
    ]);

    $station = $stmt->fetch();

    return $station ?: null;
}

function getStationEquipment(PDO $pdo, int $stationId): array
{
    $stmt = $pdo->prepare("
        SELECT
            ei.equipment_id,
            ei.asset_code,
            ei.brand,
            ei.model,
            ei.status,
            ei.notes,
            et.equipment_name,
            et.category
        FROM equipment_instances ei
        INNER JOIN equipment_types et
            ON ei.equipment_type_id = et.equipment_type_id
        WHERE ei.station_id = :station_id
        ORDER BY et.category ASC, et.equipment_name ASC, ei.asset_code ASC
    ");

    $stmt->execute([
        ':station_id' => $stationId
    ]);

    return $stmt->fetchAll();
}

function getUpcomingReservationsByStation(PDO $pdo, int $stationId, int $limit = 5): array
{
    $stmt = $pdo->prepare("
        SELECT
            r.reservation_id,
            r.start_time,
            r.end_time,
            r.status,
            r.purpose,
            CONCAT(u.first_name, ' ', u.last_name) AS user_full_name
        FROM reservations r
        INNER JOIN users u
            ON r.user_id = u.user_id
        WHERE r.station_id = :station_id
          AND r.status = 'active'
          AND r.end_time >= NOW()
        ORDER BY r.start_time ASC
        LIMIT {$limit}
    ");

    $stmt->execute([
        ':station_id' => $stationId
    ]);

    return $stmt->fetchAll();
}