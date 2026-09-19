<?php
// config/schema-absensi.php — migrasi sekali jalan: tabel kehadiran peserta per sesi.
// Satu peserta satu baris per tanggal (UNIQUE pendaftar_id + tanggal).
// Jalankan: php config/schema-absensi.php
require_once __DIR__ . '/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS absensi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftar_id INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('hadir','izin','sakit','alpa') NOT NULL DEFAULT 'hadir',
    dicatat_oleh VARCHAR(100) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_absensi_pendaftar (pendaftar_id),
    INDEX idx_absensi_tanggal (tanggal),
    UNIQUE KEY uq_absensi_harian (pendaftar_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "OK: tabel `absensi` siap.\n";
