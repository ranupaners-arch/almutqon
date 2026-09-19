<?php
// config/schema-jurnal.php — migrasi sekali jalan: tabel materi kursus + jurnal peserta
// Jalankan: php config/schema-jurnal.php
require_once __DIR__ . '/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS materi_kursus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program ENUM('offline','online','dauroh') NOT NULL,
    judul VARCHAR(255) NOT NULL,
    path_file VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_materi_program (program)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS jurnal_peserta (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(190) NOT NULL,
    pendaftar_id INT UNSIGNED NOT NULL,
    judul_jurnal VARCHAR(255) NOT NULL,
    path_file VARCHAR(255) NOT NULL,
    status_review ENUM('pending','acc','revisi') NOT NULL DEFAULT 'pending',
    diunggah_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_jurnal_email (user_email),
    INDEX idx_jurnal_pendaftar (pendaftar_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "OK: tabel `materi_kursus` & `jurnal_peserta` siap.\n";
