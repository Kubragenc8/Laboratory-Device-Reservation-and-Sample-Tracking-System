<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

$pageTitle = $pageTitle ?? APP_NAME;

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminArea = strpos($currentPath, '/public/admin/') !== false;

$bodyClass = $isAdminArea ? 'admin-area' : 'user-area';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(APP_NAME) ?></title>

    <!-- Material 3 Global -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/theme.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/layout.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/components.css">

    <!-- Legacy / Base -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css">

    <!-- Admin -->
    <?php if ($isAdminArea): ?>
        <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
    <?php endif; ?>

    <!-- Optional Page CSS -->
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= ASSETS_URL ?>css/<?= htmlspecialchars($pageCss) ?>">
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">

<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="main-content">