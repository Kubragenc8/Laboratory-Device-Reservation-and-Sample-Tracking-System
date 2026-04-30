<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

$pageTitle = 'Home';

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section">
    <div class="container">

        <!-- HERO -->
        <div class="grid grid-2" style="align-items:center; gap:48px;">

            <div>
                <h1 class="section-title" style="font-size:42px; line-height:1.2;">
                    Laboratory Device Reservation & Station Management System
                </h1>

                <p class="section-subtitle" style="font-size:18px;">
                    Reserve laboratories, choose workstations, check equipment,
                    and manage your academic reservations through a modern,
                    structured, minimalist system.
                </p>

                <div class="flex" style="gap:16px; margin-top:24px; flex-wrap:wrap;">

                    <?php if (!isLoggedIn()): ?>
                        <a href="login.php" class="btn btn-primary">
                            Login
                        </a>

                        <a href="register.php" class="btn btn-secondary">
                            Register
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary">
                            Dashboard
                        </a>
                    <?php endif; ?>

                    <a href="labs.php" class="btn btn-outline">
                        Explore Laboratories
                    </a>

                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">System Workflow</h3>

                <div style="display:flex; flex-direction:column; gap:16px; margin-top:24px;">

                    <div class="badge badge-info">1. Register / Login</div>
                    <div class="badge badge-info">2. Choose Laboratory</div>
                    <div class="badge badge-info">3. Select Station</div>
                    <div class="badge badge-info">4. Check Availability</div>
                    <div class="badge badge-info">5. Create Reservation</div>

                </div>
            </div>

        </div>

    </div>
</section>

<section class="page-section-sm">
    <div class="container">

        <!-- FEATURED LABS -->
        <h2 class="section-title">Featured Laboratory Categories</h2>

        <p class="section-subtitle">
            Early Phase academic environments supported by the system
        </p>

        <div class="grid grid-3">

            <div class="card card-hover">
                <h3>Computer Labs</h3>
                <p>
                    PC desks, software development workstations,
                    academic project stations.
                </p>
            </div>

            <div class="card card-hover">
                <h3>Network Labs</h3>
                <p>
                    Router, switch, infrastructure
                    and network practice stations.
                </p>
            </div>

            <div class="card card-hover">
                <h3>Electronics / Machine Labs</h3>
                <p>
                    Electronics benches, CNC systems,
                    engineering and technical workstations.
                </p>
            </div>

        </div>

    </div>
</section>

<section class="page-section-sm">
    <div class="container">

        <!-- MAIN FEATURES -->
        <h2 class="section-title">Main Features</h2>

        <div class="grid grid-2">

            <div class="card">
                <ul>
                    <li>View active laboratories and stations</li>
                    <li>Check date/time availability</li>
                    <li>Create reservations</li>
                    <li>Manage your reservations</li>
                </ul>
            </div>

            <div class="card">
                <ul>
                    <li>Modern academic UI</li>
                    <li>Material 3 minimalist design</li>
                    <li>Backend-integrated reservation logic</li>
                    <li>Early Phase DBS + IBP aligned structure</li>
                </ul>
            </div>

        </div>

    </div>
</section>

<section class="page-section-sm">
    <div class="container">

        <!-- CTA -->
        <div class="card" style="text-align:center; padding:48px;">

            <h2 class="section-title">
                Start Your Reservation Journey
            </h2>

            <p class="section-subtitle">
                Professional, academic, simple and scalable.
            </p>

            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary">
                    Create Account
                </a>
            <?php else: ?>
                <a href="reserve.php" class="btn btn-primary">
                    Create Reservation
                </a>
            <?php endif; ?>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>