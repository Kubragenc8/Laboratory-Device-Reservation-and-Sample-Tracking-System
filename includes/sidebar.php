<?php

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminArea = strpos($currentPath, '/public/admin/') !== false;

function sidebarLinkActive(string $pathPart): string
{
    $currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return strpos($currentPath, $pathPart) !== false ? 'active' : '';
}

?>
<aside class="sidebar">
    <?php if (isAdmin() && $isAdminArea): ?>
        <div class="sidebar-title">Admin Menu</div>

        <a class="<?= sidebarLinkActive('/public/admin/index.php') ?>" href="<?= BASE_URL ?>admin/index.php">
            Admin Dashboard
        </a>

        <a class="<?= sidebarLinkActive('/public/admin/reservations.php') ?>" href="<?= BASE_URL ?>admin/reservations.php">
            Reservations
        </a>

        <a class="<?= sidebarLinkActive('/public/admin/labs.php') ?>" href="<?= BASE_URL ?>admin/labs.php">
            Laboratories
        </a>

        <a class="<?= sidebarLinkActive('/public/admin/stations.php') ?>" href="<?= BASE_URL ?>admin/stations.php">
            Stations
        </a>

        <a class="<?= sidebarLinkActive('/public/admin/equipment.php') ?>" href="<?= BASE_URL ?>admin/equipment.php">
            Equipment
        </a>

        <a class="<?= sidebarLinkActive('/public/admin/users.php') ?>" href="<?= BASE_URL ?>admin/users.php">
            Users
        </a>

        <hr>

        <a href="<?= BASE_URL ?>dashboard.php">
            User Dashboard
        </a>
    <?php else: ?>
        <div class="sidebar-title">User Menu</div>

        <a class="<?= sidebarLinkActive('/public/dashboard.php') ?>" href="<?= BASE_URL ?>dashboard.php">
            Dashboard
        </a>

        <a class="<?= sidebarLinkActive('/public/labs.php') ?>" href="<?= BASE_URL ?>labs.php">
            Laboratories
        </a>

        <a class="<?= sidebarLinkActive('/public/reserve.php') ?>" href="<?= BASE_URL ?>reserve.php">
            Create Reservation
        </a>

        <a class="<?= sidebarLinkActive('/public/my-reservations.php') ?>" href="<?= BASE_URL ?>my-reservations.php">
            My Reservations
        </a>

        <a class="<?= sidebarLinkActive('/public/profile.php') ?>" href="<?= BASE_URL ?>profile.php">
            Profile
        </a>

        <?php if (isAdmin()): ?>
            <hr>

            <a href="<?= BASE_URL ?>admin/index.php">
                Admin Panel
            </a>
        <?php endif; ?>
    <?php endif; ?>
</aside>