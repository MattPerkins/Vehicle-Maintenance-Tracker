<?php
// Week 3 account page includes secure password-changing functionality.
require 'includes/auth.php';
require_login();
require 'config/db.php';

$error = '';
$success = '';
$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        $error = 'The form expired. Please try again.';
    } elseif ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Complete all password fields.';
    } elseif (!password_meets_requirements($newPassword)) {
        $error = 'New password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'The new passwords do not match.';
    } else {
        // Confirm the existing password before allowing the account password to change.
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $passwordHash = $stmt->fetchColumn();

        if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
            $error = 'The current password is incorrect.';
        } else {
            // Hash the replacement password before writing it to the database.
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$newHash, $userId]);
            $success = 'Your password was changed successfully.';
        }
    }
}

$stmt = $pdo->prepare('SELECT first_name, last_name, email, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$pageTitle = 'Account | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<div class="grid account-grid">
    <section class="card">
        <h1>Account</h1>
        <p><strong>Name:</strong> <?= e($user['first_name'] . ' ' . $user['last_name']) ?></p>
        <p><strong>Email:</strong> <?= e($user['email']) ?></p>
        <p><strong>Account Created:</strong> <?= e($user['created_at']) ?></p>
    </section>

    <section class="card">
        <h2>Change Password</h2>
        <p class="muted">Enter your current password before choosing a new one.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <form method="post" action="account.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="current_password">Current Password</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>

            <label for="new_password">New Password</label>
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" required>
            <p class="help-text">At least 8 characters with uppercase, lowercase, a number, and a special character.</p>

            <label for="confirm_password">Confirm New Password</label>
            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>

            <p><button class="btn" type="submit">Change Password</button></p>
        </form>
    </section>
</div>
<?php require 'includes/footer.php'; ?>
