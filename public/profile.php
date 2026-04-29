<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/validation_helper.php';

$pageTitle = 'Profile';

$userId = getCurrentUserId();

$message = '';
$messageStatus = null;

function getCurrentUserProfile(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            u.user_id,
            u.role_id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            u.is_active,
            u.created_at,
            u.updated_at,
            r.role_name,
            sp.student_no,
            sp.class_year,
            sp.program_type,
            f.faculty_name,
            d.department_name
        FROM users u
        INNER JOIN roles r
            ON u.role_id = r.role_id
        LEFT JOIN student_profiles sp
            ON u.user_id = sp.user_id
        LEFT JOIN faculties f
            ON sp.faculty_id = f.faculty_id
        LEFT JOIN departments d
            ON sp.department_id = d.department_id
        WHERE u.user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $userId
    ]);

    $profile = $stmt->fetch();

    return $profile ?: null;
}

$profile = getCurrentUserProfile($pdo, (int) $userId);

if (!$profile) {
    http_response_code(404);
    die('Profile not found.');
}

$phone = $profile['phone'] ?? '';
$programType = $profile['program_type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = cleanInput($_POST['phone'] ?? '');
    $programType = cleanInput($_POST['program_type'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE users
            SET phone = :phone
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            ':phone' => $phone !== '' ? $phone : null,
            ':user_id' => (int) $userId
        ]);

        if ($profile['role_name'] === 'student') {
            $stmt = $pdo->prepare("
                UPDATE student_profiles
                SET program_type = :program_type
                WHERE user_id = :user_id
            ");

            $stmt->execute([
                ':program_type' => $programType !== '' ? $programType : null,
                ':user_id' => (int) $userId
            ]);
        }

        $pdo->commit();

        $message = 'Profile updated successfully.';
        $messageStatus = true;

        $profile = getCurrentUserProfile($pdo, (int) $userId);
        $phone = $profile['phone'] ?? '';
        $programType = $profile['program_type'] ?? '';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = DEBUG_MODE
            ? 'Profile update failed: ' . $e->getMessage()
            : 'Profile update failed.';

        $messageStatus = false;
    }
}

require_once __DIR__ . '/../includes/header.php';

?>

<h1>Profile</h1>

<?php if ($message !== ''): ?>
    <p style="color: <?= $messageStatus ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($message) ?>
    </p>
<?php endif; ?>

<h2>Account Information</h2>

<ul>
    <li>User ID: <?= (int) $profile['user_id'] ?></li>
    <li>Role: <?= htmlspecialchars($profile['role_name']) ?></li>
    <li>Full Name: <?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></li>
    <li>Email: <?= htmlspecialchars($profile['email']) ?></li>
    <li>Account Active: <?= (int) $profile['is_active'] === 1 ? 'Yes' : 'No' ?></li>
    <li>Created At: <?= htmlspecialchars($profile['created_at']) ?></li>
    <li>Updated At: <?= htmlspecialchars($profile['updated_at']) ?></li>
</ul>

<?php if ($profile['role_name'] === 'student'): ?>
    <h2>Student Information</h2>

    <ul>
        <li>Student Number: <?= htmlspecialchars($profile['student_no'] ?? '-') ?></li>
        <li>Faculty: <?= htmlspecialchars($profile['faculty_name'] ?? '-') ?></li>
        <li>Department: <?= htmlspecialchars($profile['department_name'] ?? '-') ?></li>
        <li>Class Year: <?= $profile['class_year'] !== null ? (int) $profile['class_year'] : '-' ?></li>
        <li>Program Type: <?= htmlspecialchars($profile['program_type'] ?? '-') ?></li>
    </ul>
<?php endif; ?>

<hr>

<h2>Edit Profile</h2>

<form method="POST" action="">
    <div>
        <label for="phone">Phone</label><br>
        <input
            type="text"
            id="phone"
            name="phone"
            value="<?= htmlspecialchars($phone) ?>"
            placeholder="Example: 0555 111 2233"
        >
    </div>

    <?php if ($profile['role_name'] === 'student'): ?>
        <br>

        <div>
            <label for="program_type">Program Type</label><br>
            <input
                type="text"
                id="program_type"
                name="program_type"
                value="<?= htmlspecialchars($programType) ?>"
                placeholder="Example: 100% Turkish"
            >
        </div>
    <?php endif; ?>

    <br>

    <button type="submit">Update Profile</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>