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
    <span class="badge">SDC480 Capstone Project</span>
    <h1>Keep vehicle maintenance records organized in one place.</h1>
    <p class="muted">The Vehicle Maintenance Tracker is a simple web application for storing vehicle information and viewing service history.</p>
    <p><a class="btn" href="login.php">Log In</a></p>
</section>
<section class="grid">
    <div class="card"><h3>Vehicle Records</h3><p>Keep basic year, make, model, VIN, and mileage information connected to your account.</p></div>
    <div class="card"><h3>Maintenance History</h3><p>View service dates, maintenance types, mileage, costs, locations, and notes.</p></div>
    <div class="card"><h3>Secure Login</h3><p>Passwords are verified with PHP password hashing and database queries use prepared statements.</p></div>
</section>
<?php require 'includes/footer.php'; ?>
