<?php

require_once __DIR__ . '/../config/database.php';

$checks = [
    'roles' => 'SELECT COUNT(*) AS total FROM roles',
    'users' => 'SELECT COUNT(*) AS total FROM users',
    'laboratories' => 'SELECT COUNT(*) AS total FROM laboratories',
    'workstations' => 'SELECT COUNT(*) AS total FROM workstations',
    'reservations' => 'SELECT COUNT(*) AS total FROM reservations'
];

$results = [];

foreach ($checks as $name => $sql) {
    $stmt = $pdo->query($sql);
    $results[$name] = $stmt->fetch()['total'];
}

echo '<h1>Backend Database Test</h1>';
echo '<p>Database connection successful.</p>';

echo '<pre>';
print_r($results);
echo '</pre>';