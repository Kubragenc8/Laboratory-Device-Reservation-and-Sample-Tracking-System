<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

$pageTitle = 'Home';
$pageCss = 'index.css';

require_once __DIR__ . '/../includes/header.php';

?>

<section class="page-section index-page">

    <div class="container">

        <!-- HERO -->
        <div class="index-hero-card card">

            <div class="index-hero-grid">

                <div class="index-hero-content">

                    <span class="badge badge-info">
                        Early Phase Academic System
                    </span>

                    <h1 class="index-hero-title">
                        Laboratory Device Reservation & Station Management System
                    </h1>

                    <p class="index-hero-description">
                        Reserve laboratories, choose workstations, review equipment
                        and manage academic reservations through a clean,
                        structured and minimal web system.
                    </p>

                    <div class="index-hero-actions">

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

                            <a href="reserve.php" class="btn btn-secondary">
                                New Reservation
                            </a>
                        <?php endif; ?>

                        <a href="labs.php" class="btn btn-outline">
                            Explore Laboratories
                        </a>

                    </div>

                    <div class="index-hero-tags">

                        <span>PHP</span>
                        <span>MySQL</span>
                        <span>AJAX</span>
                        <span>Material 3 UI</span>

                    </div>

                </div>

                <div class="index-workflow-card">

                    <div class="index-workflow-header">
                        <span class="badge badge-success">
                            Demo Flow
                        </span>

                        <h2>
                            System Workflow
                        </h2>

                        <p>
                            A simple academic reservation journey from account access
                            to reservation management.
                        </p>
                    </div>

                    <div class="index-workflow-list">

                        <div class="index-workflow-item">
                            <span>1</span>
                            <div>
                                <strong>Register / Login</strong>
                                <p>Create an account or access your dashboard.</p>
                            </div>
                        </div>

                        <div class="index-workflow-item">
                            <span>2</span>
                            <div>
                                <strong>Choose Laboratory</strong>
                                <p>Browse laboratories by department and type.</p>
                            </div>
                        </div>

                        <div class="index-workflow-item">
                            <span>3</span>
                            <div>
                                <strong>Select Station</strong>
                                <p>Pick an active workstation or experiment station.</p>
                            </div>
                        </div>

                        <div class="index-workflow-item">
                            <span>4</span>
                            <div>
                                <strong>Check Availability</strong>
                                <p>Validate date and time conflicts dynamically.</p>
                            </div>
                        </div>

                        <div class="index-workflow-item">
                            <span>5</span>
                            <div>
                                <strong>Create Reservation</strong>
                                <p>Save and manage your reservation from your profile.</p>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<section class="page-section-sm">
    <div class="container">

        <!-- FEATURED LABS -->
        <div class="index-section-header">

            <span class="badge badge-info">
                Supported Environments
            </span>

            <h2 class="section-title">
                Featured Laboratory Categories
            </h2>

            <p class="section-subtitle">
                Early Phase academic environments supported by the reservation system.
            </p>

        </div>

        <div class="index-category-grid">

            <article class="card card-hover index-category-card">
                <div class="index-category-icon">
                    PC
                </div>

                <h3>
                    Computer Labs
                </h3>

                <p>
                    PC desks, software development workstations and academic
                    project stations for student use.
                </p>

                <a href="labs.php?lab_type=computer" class="btn btn-outline">
                    View Computer Labs
                </a>
            </article>

            <article class="card card-hover index-category-card">
                <div class="index-category-icon">
                    NET
                </div>

                <h3>
                    Network Labs
                </h3>

                <p>
                    Router, switch, infrastructure and network practice
                    stations for applied learning.
                </p>

                <a href="labs.php?lab_type=network" class="btn btn-outline">
                    View Network Labs
                </a>
            </article>

            <article class="card card-hover index-category-card">
                <div class="index-category-icon">
                    ENG
                </div>

                <h3>
                    Electronics / Machine Labs
                </h3>

                <p>
                    Electronics benches, CNC systems and engineering-focused
                    technical workstations.
                </p>

                <a href="labs.php" class="btn btn-outline">
                    Explore All Labs
                </a>
            </article>

        </div>

    </div>
</section>

<section class="page-section-sm">
    <div class="container">

        <!-- MAIN FEATURES -->
        <div class="index-section-header">

            <span class="badge badge-info">
                Project Capabilities
            </span>

            <h2 class="section-title">
                Main Features
            </h2>

            <p class="section-subtitle">
                Designed for a clear demo flow and IBP/DBS project requirements.
            </p>

        </div>

        <div class="index-feature-grid">

            <div class="card index-feature-card">
                <span class="badge badge-success">
                    User Flow
                </span>

                <h3>
                    Reservation Management
                </h3>

                <ul>
                    <li>View active laboratories and stations</li>
                    <li>Check date and time availability</li>
                    <li>Create reservations</li>
                    <li>View, edit and cancel your reservations</li>
                </ul>
            </div>

            <div class="card index-feature-card">
                <span class="badge badge-info">
                    Frontend
                </span>

                <h3>
                    Modern Academic Interface
                </h3>

                <ul>
                    <li>Material 3 inspired minimalist design</li>
                    <li>Responsive card-based layouts</li>
                    <li>JavaScript validation</li>
                    <li>AJAX-powered dynamic interactions</li>
                </ul>
            </div>

            <div class="card index-feature-card">
                <span class="badge badge-warning">
                    Database
                </span>

                <h3>
                    Structured Data Model
                </h3>

                <ul>
                    <li>Laboratories, departments and faculties</li>
                    <li>Workstations and equipment instances</li>
                    <li>Reservation status history</li>
                    <li>Conflict-safe reservation logic</li>
                </ul>
            </div>

            <div class="card index-feature-card">
                <span class="badge badge-success">
                    Demo Ready
                </span>

                <h3>
                    Early Phase Scope
                </h3>

                <ul>
                    <li>Login and register flow</li>
                    <li>Laboratory browsing</li>
                    <li>Station selection</li>
                    <li>Reservation create/edit/cancel flow</li>
                </ul>
            </div>

        </div>

    </div>
</section>

<section class="page-section-sm">
    <div class="container">

        <!-- CTA -->
        <div class="card index-cta-card">

            <span class="badge badge-info">
                Start Now
            </span>

            <h2 class="section-title">
                Start Your Reservation Journey
            </h2>

            <p class="section-subtitle">
                Professional, academic, simple and scalable.
            </p>

            <div class="index-cta-actions">

                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-primary">
                        Create Account
                    </a>

                    <a href="login.php" class="btn btn-outline">
                        Login
                    </a>
                <?php else: ?>
                    <a href="reserve.php" class="btn btn-primary">
                        Create Reservation
                    </a>

                    <a href="my-reservations.php" class="btn btn-outline">
                        My Reservations
                    </a>
                <?php endif; ?>

            </div>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>