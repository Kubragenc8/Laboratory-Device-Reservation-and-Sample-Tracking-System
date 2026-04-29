<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/validation_helper.php';
require_once __DIR__ . '/../../helpers/response_helper.php';

$facultyId = $_GET['faculty_id'] ?? $_POST['faculty_id'] ?? '';

if (!isPositiveInteger($facultyId)) {
    jsonError('Valid faculty ID is required.', 400);
}

$stmt = $pdo->prepare("
    SELECT
        department_id,
        department_name
    FROM departments
    WHERE faculty_id = :faculty_id
      AND is_active = 1
    ORDER BY department_name ASC
");

$stmt->execute([
    ':faculty_id' => (int) $facultyId
]);

$departments = $stmt->fetchAll();

jsonSuccess('Departments loaded successfully.', [
    'departments' => $departments
]);