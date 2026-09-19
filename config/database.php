<?php
// config/database.php
// Kredensial DB dibaca dari variabel env (file .env di root) — bukan hardcoded.
// Catatan: getenv() mengembalikan FALSE bila tidak diset; wajib cek !== false.

// Selaraskan TZ PHP dengan MySQL (WIB) agar perhitungan deadline/countdown konsisten.
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

// Parser .env ringan (tanpa library eksternal). Tidak menimpa env yang sudah ada.
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : '127.0.0.1';
$dbname = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'db_almutqon-website';
$username = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}