<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/validation_helper.php';
require_once __DIR__ . '/../../helpers/response_helper.php';

$email = cleanInput($_GET['email'] ?? $_POST['email'] ?? '');

if ($email === '') {
    jsonError('Email is required.', 400);
}

if (!isValidEmailAddress($email)) {
    jsonError('Email format is invalid.', 400);
}

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM users
    WHERE email = :email
");

$stmt->execute([
    ':email' => $email
]);

$exists = (int) $stmt->fetch()['total'] > 0;

jsonSuccess('Email check completed.', [
    'exists' => $exists
]);