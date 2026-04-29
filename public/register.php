<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/validation_helper.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$errors = [];
$successMessage = '';

$firstName = '';
$lastName = '';
$email = '';
$phone = '';
$studentNo = '';
$facultyId = '';
$departmentId = '';
$classYear = '';
$programType = '';

$faculties = $pdo->query("
    SELECT faculty_id, faculty_name
    FROM faculties
    WHERE is_active = 1
    ORDER BY faculty_name ASC
")->fetchAll();

$departments = $pdo->query("
    SELECT department_id, faculty_id, department_name
    FROM departments
    WHERE is_active = 1
    ORDER BY department_name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = cleanInput($_POST['first_name'] ?? '');
    $lastName = cleanInput($_POST['last_name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $studentNo = cleanInput($_POST['student_no'] ?? '');
    $facultyId = $_POST['faculty_id'] ?? '';
    $departmentId = $_POST['department_id'] ?? '';
    $classYear = $_POST['class_year'] ?? '';
    $programType = cleanInput($_POST['program_type'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (isEmptyValue($firstName)) {
        $errors[] = 'First name is required.';
    }

    if (isEmptyValue($lastName)) {
        $errors[] = 'Last name is required.';
    }

    if (isEmptyValue($email)) {
        $errors[] = 'Email is required.';
    } elseif (!isValidEmailAddress($email)) {
        $errors[] = 'Email format is invalid.';
    }

    if (isEmptyValue($studentNo)) {
        $errors[] = 'Student number is required.';
    } elseif (!isValidStudentNumber($studentNo)) {
        $errors[] = 'Student number format is invalid.';
    }

    if (!isPositiveInteger($facultyId)) {
        $errors[] = 'Faculty selection is required.';
    }

    if (!isPositiveInteger($departmentId)) {
        $errors[] = 'Department selection is required.';
    }

    if (!isValidClassYear($classYear)) {
        $errors[] = 'Class year must be between 1 and 6.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (!isValidPasswordLength($password, 6)) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Password confirmation is required.';
    } elseif (!doPasswordsMatch($password, $confirmPassword)) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM users
            WHERE email = :email
        ");

        $stmt->execute([
            ':email' => $email
        ]);

        if ((int) $stmt->fetch()['total'] > 0) {
            $errors[] = 'This email is already registered.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM student_profiles
            WHERE student_no = :student_no
        ");

        $stmt->execute([
            ':student_no' => $studentNo
        ]);

        if ((int) $stmt->fetch()['total'] > 0) {
            $errors[] = 'This student number is already registered.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM departments
            WHERE department_id = :department_id
              AND faculty_id = :faculty_id
              AND is_active = 1
        ");

        $stmt->execute([
            ':department_id' => (int) $departmentId,
            ':faculty_id' => (int) $facultyId
        ]);

        if ((int) $stmt->fetch()['total'] === 0) {
            $errors[] = 'Selected department does not belong to the selected faculty.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT role_id
            FROM roles
            WHERE role_name = 'student'
            LIMIT 1
        ");

        $stmt->execute();
        $studentRole = $stmt->fetch();

        if (!$studentRole) {
            $errors[] = 'Student role was not found.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $salt = generateSalt();
            $passwordHash = hashPasswordWithSalt($password, $salt);

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    role_id,
                    first_name,
                    last_name,
                    email,
                    password_hash,
                    password_salt,
                    phone,
                    is_active
                ) VALUES (
                    :role_id,
                    :first_name,
                    :last_name,
                    :email,
                    :password_hash,
                    :password_salt,
                    :phone,
                    1
                )
            ");

            $stmt->execute([
                ':role_id' => (int) $studentRole['role_id'],
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':password_salt' => $salt,
                ':phone' => $phone !== '' ? $phone : null
            ]);

            $newUserId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO student_profiles (
                    user_id,
                    student_no,
                    faculty_id,
                    department_id,
                    class_year,
                    program_type
                ) VALUES (
                    :user_id,
                    :student_no,
                    :faculty_id,
                    :department_id,
                    :class_year,
                    :program_type
                )
            ");

            $stmt->execute([
                ':user_id' => $newUserId,
                ':student_no' => $studentNo,
                ':faculty_id' => (int) $facultyId,
                ':department_id' => (int) $departmentId,
                ':class_year' => (int) $classYear,
                ':program_type' => $programType !== '' ? $programType : null
            ]);

            $pdo->commit();

            header('Location: login.php?registered=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();

            if (DEBUG_MODE) {
                $errors[] = 'Registration failed: ' . $e->getMessage();
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Laboratory Reservation System</title>
</head>
<body>

<h1>Register</h1>

<?php if (!empty($errors)): ?>
    <div style="color: red;">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div>
        <label for="first_name">First Name</label><br>
        <input
            type="text"
            id="first_name"
            name="first_name"
            value="<?= htmlspecialchars($firstName) ?>"
            required
        >
    </div>

    <br>

    <div>
        <label for="last_name">Last Name</label><br>
        <input
            type="text"
            id="last_name"
            name="last_name"
            value="<?= htmlspecialchars($lastName) ?>"
            required
        >
    </div>

    <br>

    <div>
        <label for="email">Email</label><br>
        <input
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($email) ?>"
            required
        >
    </div>

    <br>

    <div>
        <label for="phone">Phone</label><br>
        <input
            type="text"
            id="phone"
            name="phone"
            value="<?= htmlspecialchars($phone) ?>"
        >
    </div>

    <br>

    <div>
        <label for="student_no">Student Number</label><br>
        <input
            type="text"
            id="student_no"
            name="student_no"
            value="<?= htmlspecialchars($studentNo) ?>"
            required
        >
    </div>

    <br>

    <div>
        <label for="faculty_id">Faculty</label><br>
        <select id="faculty_id" name="faculty_id" required>
            <option value="">Select faculty</option>
            <?php foreach ($faculties as $faculty): ?>
                <option
                    value="<?= (int) $faculty['faculty_id'] ?>"
                    <?= (string) $facultyId === (string) $faculty['faculty_id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($faculty['faculty_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <br>

    <div>
        <label for="department_id">Department</label><br>
        <select id="department_id" name="department_id" required>
            <option value="">Select department</option>
            <?php foreach ($departments as $department): ?>
                <option
                    value="<?= (int) $department['department_id'] ?>"
                    <?= (string) $departmentId === (string) $department['department_id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($department['department_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <br>

    <div>
        <label for="class_year">Class Year</label><br>
        <select id="class_year" name="class_year" required>
            <option value="">Select class year</option>
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <option
                    value="<?= $i ?>"
                    <?= (string) $classYear === (string) $i ? 'selected' : '' ?>
                >
                    <?= $i ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

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

    <div>
        <label for="confirm_password">Confirm Password</label><br>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            required
        >
    </div>

    <br>

    <button type="submit">Create Account</button>
</form>

<p>
    Already have an account?
    <a href="login.php">Login</a>
</p>

</body>
</html>