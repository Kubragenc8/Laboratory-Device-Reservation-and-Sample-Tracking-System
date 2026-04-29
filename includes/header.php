<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

$pageTitle = $pageTitle ?? APP_NAME;

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminArea = strpos($currentPath, '/public/admin/') !== false;

$bodyClass = $isAdminArea ? 'admin-area' : 'user-area';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(APP_NAME) ?></title>

    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css">

    <?php if ($isAdminArea): ?>
        <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">

<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="page-content">
