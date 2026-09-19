<?php
// config/csrf.php — helper token CSRF
// Token dibuat sekali per sesi & dipakai ulang (stateless di session).
// Panggil require_csrf() di awal blok POST, dan csrf_field() di dalam <form>.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if (!csrf_check()) {
        http_response_code(403);
        exit('Token CSRF tidak valid. Silakan muat ulang halaman dan coba lagi.');
    }
}