<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}



$error = '';
$successMessage = '';

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $successMessage = 'Registration completed successfully. You can now log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("
            SELECT
                u.user_id,
                u.role_id,
                u.first_name,
                u.last_name,
                u.email,
                u.password_hash,
                u.password_salt,
                u.is_active,
                r.role_name
            FROM users u
            INNER JOIN roles r
                ON u.role_id = r.role_id
            WHERE u.email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Invalid email or password.';
        } elseif ((int) $user['is_active'] !== 1) {
            $error = 'This account is not active.';
        } elseif (!verifyPassword($password, $user['password_salt'], $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['role_id'] = (int) $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email'] = $user['email'];

            if ($user['role_name'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Laboratory Reservation System</title>
</head>
<body>

<h1>Login</h1>

<?php if ($successMessage !== ''): ?>
    <p style="color: green;">
        <?= htmlspecialchars($successMessage) ?>
    </p>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <p style="color: red;">
        <?= htmlspecialchars($error) ?>
    </p>
<?php endif; ?>

<form method="POST" action="">
    <div>
        <label for="email">Email</label><br>
        <input
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
        >
    </div>

    <br>

    <div>
        <label for="password">Password</label><br>
        <input
            type="password"
            id="password"
            name="password"
            required
        >
    </div>

    <br>

    <button type="submit">Login</button>
</form>

<p>
    Do not have an account?
    <a href="register.php">Create an account</a>
</p>

<p>
    Test admin account: admin@lab.local / 123456
</p>

<p>
    Test student account: onur.demo@ogrenci.karabuk.edu.tr / 123456
</p>

</body>
</html>