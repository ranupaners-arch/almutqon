<?php
// config/database.php
// Kredensial DB dibaca dari variabel env (file .env di root) — bukan hardcoded.
// Catatan: getenv() mengembalikan FALSE bila tidak diset; wajib cek !== false.

require_once __DIR__ . '/runtime.php';
require_production_environment(['APP_URL', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : '127.0.0.1';
$port = getenv('DB_PORT') !== false ? (int) getenv('DB_PORT') : 3306;
$dbname = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'db_almutqon-website';
$username = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi gangguan koneksi database. Silakan hubungi administrator.');
}
