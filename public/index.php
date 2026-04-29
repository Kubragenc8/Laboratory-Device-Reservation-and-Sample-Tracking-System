<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

$pageTitle = 'Home';

require_once __DIR__ . '/../includes/header.php';

?>

<h1>Laboratory Reservation System</h1>

<p>
    Welcome to the Laboratory Reservation System.
</p>

<p>
    This system allows students to view laboratories, check station availability, create reservations, and manage their own reservations.
</p>

<?php if (isLoggedIn()): ?>
    <h2>Quick Access</h2>

    <ul>
        <li>
            <a href="dashboard.php">Go to Dashboard</a>
        </li>

        <li>
            <a href="labs.php">View Laboratories</a>
        </li>

        <li>
            <a href="reserve.php">Create Reservation</a>
        </li>

        <li>
            <a href="my-reservations.php">My Reservations</a>
        </li>

        <li>
            <a href="profile.php">Profile</a>
        </li>

        <?php if (isAdmin()): ?>
            <li>
                <a href="admin/index.php">Admin Dashboard</a>
            </li>

            <li>
                <a href="admin/reservations.php">Admin Reservations</a>
            </li>

            <li>
                <a href="admin/users.php">Admin Users</a>
            </li>
        <?php endif; ?>
    </ul>
<?php else: ?>
    <h2>Get Started</h2>

    <p>
        Please log in or create a student account to use the system.
    </p>

    <ul>
        <li>
            <a href="login.php">Login</a>
        </li>

        <li>
            <a href="register.php">Register</a>
        </li>
    </ul>
<?php endif; ?>

<hr>

<h2>Main Features</h2>

<ul>
    <li>View active laboratories and workstations.</li>
    <li>Check station availability by date and time.</li>
    <li>Create a reservation for an available station.</li>
    <li>View and cancel your own reservations.</li>
    <li>Admin users can monitor reservations, laboratories, stations, equipment, and users.</li>
</ul>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>