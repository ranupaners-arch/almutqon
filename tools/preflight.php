<?php
declare(strict_types=1);

require_once __DIR__ . '/cli-only.php';
require_once dirname(__DIR__) . '/config/runtime.php';

$root = dirname(__DIR__);
$production = in_array('--production', $argv, true);
$withDatabase = in_array('--database', $argv, true);
$failed = false;

function preflight_result(bool $ok, string $label, string $detail = ''): void
{
    global $failed;
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $label . ($detail !== '' ? ' (' . $detail . ')' : '') . PHP_EOL;
    if (!$ok) {
        $failed = true;
    }
}

preflight_result(version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP minimal 8.1', PHP_VERSION);
foreach (['curl', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql'] as $extension) {
    preflight_result(extension_loaded($extension), 'Extension ' . $extension);
}

$requiredEnv = ['APP_ENV', 'APP_URL', 'DB_HOST', 'DB_NAME', 'DB_USER'];
if ($production) {
    $requiredEnv[] = 'DB_PASS';
}
foreach ($requiredEnv as $key) {
    $value = getenv($key);
    preflight_result($value !== false && trim((string) $value) !== '', 'Environment ' . $key);
}

if ($production) {
    preflight_result(app_env() === 'production', 'APP_ENV production', app_env());
    $appUrl = (string) (getenv('APP_URL') ?: '');
    preflight_result(str_starts_with($appUrl, 'https://'), 'APP_URL memakai HTTPS', $appUrl);
    preflight_result(getenv('APP_DEBUG') !== 'true', 'APP_DEBUG nonaktif');
}

foreach (['storage/jurnal', 'storage/materi', 'storage/backups'] as $directory) {
    $path = $root . '/' . $directory;
    preflight_result(is_dir($path) && is_writable($path), $directory . ' dapat ditulis');
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    $path = $file->getPathname();
    if ($file->getExtension() !== 'php' || str_contains($path, '/release/')) {
        continue;
    }
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' >/dev/null 2>&1';
    exec($command, $unused, $exitCode);
    if ($exitCode !== 0) {
        preflight_result(false, 'PHP lint', substr($path, strlen($root) + 1));
    }
}
if (!$failed) {
    preflight_result(true, 'Seluruh file PHP lolos lint');
}

if ($withDatabase) {
    require dirname(__DIR__) . '/config/database.php';
    preflight_result((bool) $pdo->query('SELECT 1')->fetchColumn(), 'Koneksi database');
    $requiredTables = ['users', 'articles', 'pendaftar', 'schema_migrations'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        preflight_result((int) $stmt->fetchColumn() === 1, 'Tabel ' . $table . ' tersedia');
    }
}

exit($failed ? 1 : 0);
