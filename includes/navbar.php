<?php

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

function navLinkActive(string $pathPart): string
{
    $currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return strpos($currentPath, $pathPart) !== false ? 'active' : '';
}

$isLoggedIn = isLoggedIn();
$isAdmin = $isLoggedIn && isAdmin();

$brandUrl = BASE_URL . 'index.php';

if ($isLoggedIn && !$isAdmin) {
    $brandUrl = BASE_URL . 'dashboard.php';
}

if ($isLoggedIn && $isAdmin) {
    $brandUrl = BASE_URL . 'admin/index.php';
}

?>
<header class="topbar">
    <div class="container">

        <div class="topbar-inner">

            <!-- BRAND -->
            <div class="topbar-left">

                <a class="brand" href="<?= $brandUrl ?>">
                    <span class="brand-mark">LAB</span>
                    <span class="brand-text">
                        <?= htmlspecialchars(APP_NAME) ?>
                    </span>
                </a>

            </div>

            <!-- NAVIGATION -->
            <nav class="nav-links" data-mobile-menu>

                <!-- PUBLIC -->
                <a class="<?= navLinkActive('/public/index.php') ?>" href="<?= BASE_URL ?>index.php">
                    Home
                </a>

                <a class="<?= navLinkActive('/public/labs.php') ?>" href="<?= BASE_URL ?>labs.php">
                    Laboratories
                </a>

                <?php if (!$isLoggedIn): ?>

                    <a class="<?= navLinkActive('/public/login.php') ?>" href="<?= BASE_URL ?>login.php">
                        Login
                    </a>

                    <a class="btn btn-primary nav-cta" href="<?= BASE_URL ?>register.php">
                        Register
                    </a>

                <?php else: ?>

                    <!-- USER -->
                    <a class="<?= navLinkActive('/public/dashboard.php') ?>" href="<?= BASE_URL ?>dashboard.php">
                        Dashboard
                    </a>

                    <a class="<?= navLinkActive('/public/reserve.php') ?>" href="<?= BASE_URL ?>reserve.php">
                        Reserve
                    </a>

                    <a class="<?= navLinkActive('/public/my-reservations.php') ?>" href="<?= BASE_URL ?>my-reservations.php">
                        My Reservations
                    </a>

                    <a class="<?= navLinkActive('/public/profile.php') ?>" href="<?= BASE_URL ?>profile.php">
                        Profile
                    </a>

                    <!-- ADMIN -->
                    <?php if ($isAdmin): ?>
                        <a class="<?= navLinkActive('/public/admin/') ?>" href="<?= BASE_URL ?>admin/index.php">
                            Admin Panel
                        </a>
                    <?php endif; ?>

                    <!-- USER CHIP -->
                    <div class="nav-user-chip">
                        <span class="nav-user-name">
                            <?= htmlspecialchars(getCurrentUserName()) ?>
                        </span>

                        <span class="nav-user-role">
                            <?= htmlspecialchars($_SESSION['role_name'] ?? 'user') ?>
                        </span>
                    </div>

                    <a class="btn btn-outline" href="<?= BASE_URL ?>logout.php">
                        Logout
                    </a>

                <?php endif; ?>

            </nav>

            <!-- MOBILE BUTTON -->
            <button
                class="btn btn-secondary mobile-menu-btn"
                type="button"
                data-mobile-toggle
                aria-label="Toggle navigation"
            >
                Menu
            </button>

        </div>

    </div>
</header>