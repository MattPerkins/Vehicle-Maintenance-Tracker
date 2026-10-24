<?php
// Shared authentication and security helpers used throughout the application.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_login(): void
{
    // Protected pages redirect visitors who are not signed in.
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function e(?string $value): string
{
    // Escape output before displaying database or user-entered content in HTML.
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function password_meets_requirements(string $password): bool
{
    // Week 3 password-complexity rule: 8+ characters with upper/lowercase,
    // at least one number, and at least one special character.
    return strlen($password) >= 8
        && preg_match('/[a-z]/', $password)
        && preg_match('/[A-Z]/', $password)
        && preg_match('/\d/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

function csrf_token(): string
{
    // CSRF tokens help prevent another site from submitting protected forms for the user.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool
{
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
