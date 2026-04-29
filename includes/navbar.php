<?php

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

function navLinkActive(string $pathPart): string
{
    $currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return strpos($currentPath, $pathPart) !== false ? 'active' : '';
}

$brandUrl = isLoggedIn() && isAdmin()
    ? BASE_URL . 'admin/index.php'
    : BASE_URL . 'dashboard.php';

if (!isLoggedIn()) {
    $brandUrl = BASE_URL . 'login.php';
}

?>
<header class="top-navbar">
    <div class="navbar-left">
        <a class="navbar-brand" href="<?= $brandUrl ?>">
            Laboratory Reservation System
        </a>
    </div>

    <nav class="navbar-links">
        <?php if (isLoggedIn()): ?>

            <?php if (isAdmin()): ?>
                <a class="<?= navLinkActive('/public/admin/index.php') ?>" href="<?= BASE_URL ?>admin/index.php">
                    Admin Dashboard
                </a>

                <a class="<?= navLinkActive('/public/admin/reservations.php') ?>" href="<?= BASE_URL ?>admin/reservations.php">
                    Reservations
                </a>

                <a class="<?= navLinkActive('/public/admin/labs.php') ?>" href="<?= BASE_URL ?>admin/labs.php">
                    Labs
                </a>

                <a class="<?= navLinkActive('/public/admin/stations.php') ?>" href="<?= BASE_URL ?>admin/stations.php">
                    Stations
                </a>

                <a class="<?= navLinkActive('/public/admin/equipment.php') ?>" href="<?= BASE_URL ?>admin/equipment.php">
                    Equipment
                </a>

                <a class="<?= navLinkActive('/public/admin/users.php') ?>" href="<?= BASE_URL ?>admin/users.php">
                    Users
                </a>

                <span>|</span>

                <a class="<?= navLinkActive('/public/dashboard.php') ?>" href="<?= BASE_URL ?>dashboard.php">
                    User Dashboard
                </a>

                <a class="<?= navLinkActive('/public/profile.php') ?>" href="<?= BASE_URL ?>profile.php">
                    Profile
                </a>
            <?php else: ?>
                <a class="<?= navLinkActive('/public/dashboard.php') ?>" href="<?= BASE_URL ?>dashboard.php">
                    Dashboard
                </a>

                <a class="<?= navLinkActive('/public/labs.php') ?>" href="<?= BASE_URL ?>labs.php">
                    Laboratories
                </a>

                <a class="<?= navLinkActive('/public/reserve.php') ?>" href="<?= BASE_URL ?>reserve.php">
                    Create Reservation
                </a>

                <a class="<?= navLinkActive('/public/my-reservations.php') ?>" href="<?= BASE_URL ?>my-reservations.php">
                    My Reservations
                </a>

                <a class="<?= navLinkActive('/public/profile.php') ?>" href="<?= BASE_URL ?>profile.php">
                    Profile
                </a>
            <?php endif; ?>

            <span>|</span>

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