<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/validation_helper.php';

$pageTitle = 'Profile';
$pageCss = 'profile.css';

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

<section class="page-section">
    <div class="container">

        <!-- HERO -->
        <div class="card" style="margin-bottom:32px;">

            <h1 class="section-title" style="margin-bottom:8px;">
                My Profile
            </h1>

            <p class="section-subtitle" style="margin-bottom:0;">
                Manage your academic identity, account information and profile settings.
            </p>

        </div>

        <!-- MESSAGE -->
        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageStatus ? 'alert-success' : 'alert-error' ?>" style="margin-bottom:24px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ACCOUNT -->
        <div class="card" style="margin-bottom:32px;">

            <h2 style="margin-top:0;">Account Information</h2>

            <div class="grid grid-2">

                <div>
                    <p><strong>User ID:</strong> <?= (int) $profile['user_id'] ?></p>
                    <p><strong>Role:</strong> <?= htmlspecialchars($profile['role_name']) ?></p>
                    <p><strong>Full Name:</strong> <?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($profile['email']) ?></p>
                </div>

                <div>
                    <p>
                        <strong>Account Active:</strong>

                        <?php if ((int) $profile['is_active'] === 1): ?>
                            <span class="badge badge-success">Yes</span>
                        <?php else: ?>
                            <span class="badge badge-warning">No</span>
                        <?php endif; ?>
                    </p>

                    <p><strong>Created At:</strong> <?= htmlspecialchars($profile['created_at']) ?></p>
                    <p><strong>Updated At:</strong> <?= htmlspecialchars($profile['updated_at']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($profile['phone'] ?? '-') ?></p>
                </div>

            </div>

        </div>

        <!-- STUDENT -->
        <?php if ($profile['role_name'] === 'student'): ?>

            <div class="card" style="margin-bottom:32px;">

                <h2 style="margin-top:0;">Student Information</h2>

                <div class="grid grid-2">

                    <div>
                        <p><strong>Student Number:</strong> <?= htmlspecialchars($profile['student_no'] ?? '-') ?></p>
                        <p><strong>Faculty:</strong> <?= htmlspecialchars($profile['faculty_name'] ?? '-') ?></p>
                        <p><strong>Department:</strong> <?= htmlspecialchars($profile['department_name'] ?? '-') ?></p>
                    </div>

                    <div>
                        <p><strong>Class Year:</strong> <?= $profile['class_year'] !== null ? (int) $profile['class_year'] : '-' ?></p>
                        <p><strong>Program Type:</strong> <?= htmlspecialchars($profile['program_type'] ?? '-') ?></p>
                    </div>

                </div>

            </div>

        <?php endif; ?>

        <!-- EDIT -->
        <div class="card">

            <h2 style="margin-top:0;">Edit Profile</h2>

            <form method="POST" action="">

                <div class="grid grid-2">

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone</label>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars($phone) ?>"
                            placeholder="Example: 0555 111 2233"
                        >
                    </div>

                    <?php if ($profile['role_name'] === 'student'): ?>

                        <div class="form-group">
                            <label for="program_type" class="form-label">Program Type</label>

                            <input
                                type="text"
                                id="program_type"
                                name="program_type"
                                class="form-control"
                                value="<?= htmlspecialchars($programType) ?>"
                                placeholder="Example: 100% Turkish"
                            >
                        </div>

                    <?php endif; ?>

                </div>

                <button type="submit" class="btn btn-primary">
                    Update Profile
                </button>

            </form>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>