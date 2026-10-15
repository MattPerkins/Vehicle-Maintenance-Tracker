<?php
require 'includes/auth.php';
require_login();
require 'config/db.php';
$stmt = $pdo->prepare('SELECT first_name, last_name, email, created_at FROM users WHERE id = ?');
$stmt->execute([current_user_id()]);
$user = $stmt->fetch();
$pageTitle = 'Account | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<section class="card form-card">
    <h1>Account</h1>
    <p><strong>Name:</strong> <?= e($user['first_name'] . ' ' . $user['last_name']) ?></p>
    <p><strong>Email:</strong> <?= e($user['email']) ?></p>
    <p><strong>Account Created:</strong> <?= e($user['created_at']) ?></p>
    <p class="muted">Registration and password-changing functionality are planned for Week 3.</p>
</section>
<?php require 'includes/footer.php'; ?>
