<?php
// config/schema-pendaftar.php — migrasi sekali jalan: buat tabel `pendaftar`
// Jalankan: php config/schema-pendaftar.php
require_once __DIR__ . '/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS pendaftar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program ENUM('offline','online','dauroh') NOT NULL,
    nama VARCHAR(120) NOT NULL,
    whatsapp VARCHAR(30) NOT NULL,
    metode VARCHAR(50) NOT NULL DEFAULT '',
    alamat VARCHAR(255) NOT NULL DEFAULT '',
    instansi VARCHAR(120) NOT NULL DEFAULT '',
    jumlah_peserta INT UNSIGNED NOT NULL DEFAULT 0,
    catatan TEXT NULL,
    user_email VARCHAR(190) NOT NULL DEFAULT '',
    status ENUM('baru','dihubungi') NOT NULL DEFAULT 'baru',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "OK: tabel `pendaftar` siap.\n";