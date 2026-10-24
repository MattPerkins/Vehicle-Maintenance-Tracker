<?php
// Week 3 user registration page.
require 'includes/auth.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        $error = 'The form expired. Please try again.';
    } elseif ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (!password_meets_requirements($password)) {
        $error = 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        // Prepared statements keep user input separate from SQL commands.
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'An account already exists with that email address.';
        } else {
            // Store a one-way password hash rather than the plain-text password.
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)');
            $stmt->execute([$firstName, $lastName, $email, $passwordHash]);
            $success = 'Registration complete. You can now log in with your new account.';
            $_POST = [];
        }
    }
}

$pageTitle = 'Register | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<section class="card form-card">
    <h1>Create Account</h1>
    <p class="muted">Register to keep vehicle and maintenance records connected to your own account.</p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?> <a href="login.php">Log in</a>.</div><?php endif; ?>

    <form method="post" action="register.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <label for="first_name">First Name</label>
        <input id="first_name" name="first_name" maxlength="50" value="<?= e($_POST['first_name'] ?? '') ?>" required>

        <label for="last_name">Last Name</label>
        <input id="last_name" name="last_name" maxlength="50" value="<?= e($_POST['last_name'] ?? '') ?>" required>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="120" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" required>
        <p class="help-text">Use at least 8 characters with uppercase, lowercase, a number, and a special character.</p>

        <label for="confirm_password">Confirm Password</label>
        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>

        <p><button class="btn" type="submit">Create Account</button></p>
    </form>
    <p class="muted">Already registered? <a href="login.php">Log in here</a>.</p>
</section>
<?php require 'includes/footer.php'; ?>
