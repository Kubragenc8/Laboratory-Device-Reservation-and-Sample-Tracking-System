<?php

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

function navLinkActive(string $pathPart): string
{
    $currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return strpos($currentPath, $pathPart) !== false ? 'active' : '';
}

?>
<header class="top-navbar">
    <div class="navbar-left">
        <a class="navbar-brand" href="<?= BASE_URL ?>dashboard.php">
            Laboratory Reservation System
        </a>
    </div>

    <nav class="navbar-links">
        <?php if (isLoggedIn()): ?>
            <a class="<?= navLinkActive('/public/dashboard.php') ?>" href="<?= BASE_URL ?>dashboard.php">
                Dashboard
            </a>

            <a class="<?= navLinkActive('/public/labs.php') ?>" href="<?= BASE_URL ?>labs.php">
                Laboratories
            </a>

            <a class="<?= navLinkActive('/public/my-reservations.php') ?>" href="<?= BASE_URL ?>my-reservations.php">
                My Reservations
            </a>

            <a class="<?= navLinkActive('/public/reserve.php') ?>" href="<?= BASE_URL ?>reserve.php">
                Create Reservation
            </a>

            <?php if (isAdmin()): ?>
                <a class="<?= navLinkActive('/public/admin/') ?>" href="<?= BASE_URL ?>admin/index.php">
                    Admin Panel
                </a>
            <?php endif; ?>

            <span class="navbar-user">
                <?= htmlspecialchars(getCurrentUserName()) ?>
                (<?= htmlspecialchars($_SESSION['role_name'] ?? 'user') ?>)
            </span>

            <a href="<?= BASE_URL ?>logout.php">
                Logout
            </a>
        <?php else: ?>
            <a class="<?= navLinkActive('/public/login.php') ?>" href="<?= BASE_URL ?>login.php">
                Login
            </a>

            <a class="<?= navLinkActive('/public/register.php') ?>" href="<?= BASE_URL ?>register.php">
                Register
            </a>
        <?php endif; ?>
    </nav>
</header>