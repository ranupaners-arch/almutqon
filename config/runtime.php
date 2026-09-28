<?php
declare(strict_types=1);

// Bootstrap runtime bersama: environment, timezone, header dasar, dan cookie sesi.
if (!defined('ALMUTQON_RUNTIME_READY')) {
    define('ALMUTQON_RUNTIME_READY', true);

    $envFile = dirname(__DIR__) . '/.env';
    if (is_file($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
                $key = trim($key);
                $value = trim($value);
                if ($key === '' || !preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || getenv($key) !== false) {
                    continue;
                }
                if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
                    $value = substr($value, 1, -1);
                }
                putenv($key . '=' . $value);
            }
        }
    }

    date_default_timezone_set('Asia/Jakarta');

    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }

    if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https || getenv('APP_ENV') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function app_env(): string
{
    $value = getenv('APP_ENV');
    return $value !== false && $value !== '' ? strtolower($value) : 'local';
}

function app_is_production(): bool
{
    return app_env() === 'production';
}

function require_production_environment(array $keys): void
{
    if (!app_is_production()) {
        return;
    }
    $missing = [];
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value === false || trim((string) $value) === '') {
            $missing[] = $key;
        }
    }
    if ($missing !== []) {
        error_log('[config] Environment production belum lengkap: ' . implode(', ', $missing));
        http_response_code(500);
        exit('Konfigurasi aplikasi belum lengkap. Hubungi administrator.');
    }
}
