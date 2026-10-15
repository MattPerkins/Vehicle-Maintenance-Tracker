<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require 'config/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Enter both your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            header('Location: dashboard.php');
            exit;
        }
        $error = 'The email or password was not recognized.';
    }
}

$pageTitle = 'Log In | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<section class="card form-card">
    <h1>Log In</h1>
    <p class="muted">Use the sample account after importing the database.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" action="login.php" novalidate>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <p><button class="btn" type="submit">Log In</button></p>
    </form>
    <p class="muted"><strong>Week 2 test account:</strong> student@example.com / Student123!</p>
</section>
<?php require 'includes/footer.php'; ?>
