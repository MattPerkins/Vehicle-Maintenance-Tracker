<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$pageTitle = 'Home | Vehicle Maintenance Tracker';
require 'includes/header.php';
?>
<section class="hero">
    <span class="badge">SDC480 Capstone Project - Phase 3</span>
    <h1>Keep vehicle maintenance records organized in one place.</h1>
    <p class="muted">The Vehicle Maintenance Tracker stores vehicle information, maintenance history, and user accounts in a simple PHP/MySQL application.</p>
    <p class="button-row"><a class="btn" href="login.php">Log In</a> <a class="btn btn-secondary" href="register.php">Create Account</a></p>
</section>
<section class="grid">
    <div class="card"><h3>Vehicle Records</h3><p>Keep basic year, make, model, VIN, and mileage information connected to your account.</p></div>
    <div class="card"><h3>Maintenance Search</h3><p>Search maintenance history using partial text or wildcard searches.</p></div>
    <div class="card"><h3>Account Security</h3><p>Passwords are hashed, database queries use prepared statements, and protected pages require a valid session.</p></div>
</section>
<?php require 'includes/footer.php'; ?>
