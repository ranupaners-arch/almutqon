<?php
// config/schema-donasi.php — migrasi sekali jalan: buat tabel `donasi`
// Jalankan: php config/schema-donasi.php
require_once __DIR__ . '/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS donasi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(190) NOT NULL DEFAULT '',
    nama VARCHAR(120) NOT NULL,
    whatsapp VARCHAR(30) NOT NULL,
    jumlah INT UNSIGNED NOT NULL,
    metode ENUM('bank','qris','ewallet') NOT NULL,
    status ENUM('pending','diterima') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "OK: tabel `donasi` siap.\n";