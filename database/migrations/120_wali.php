<?php
// Migrasi perwalian, jadwal kesepakatan, dan RPP.
// - perwalian: pengajar menjadi wali privat (per peserta), wali kelompok (per program),
//   atau wali dauroh (gabungan beberapa pengajar = beberapa baris).
// - jadwal_sepakatan: hari belajar yang disepakati murid & guru (CSV nama hari Indonesia).
//   pendaftar_id terisi = privat per peserta; kosong = kelompok per program.
// - rpp: Rencana Pelaksanaan Pembelajaran milik pengajar (semua pengajar bisa akses).
require_once dirname(__DIR__, 2) . '/tools/cli-only.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS perwalian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengajar_id INT UNSIGNED NOT NULL,
    pendaftar_id INT UNSIGNED NULL DEFAULT NULL,
    program VARCHAR(20) NULL DEFAULT NULL,
    tipe ENUM('privat','kelompok','dauroh') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wali_pengajar (pengajar_id),
    INDEX idx_wali_pendaftar (pendaftar_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS jadwal_sepakatan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftar_id INT UNSIGNED NULL DEFAULT NULL,
    program VARCHAR(20) NULL DEFAULT NULL,
    hari VARCHAR(50) NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jadwal_pendaftar (pendaftar_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS rpp (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengajar_id INT UNSIGNED NOT NULL,
    materi_id INT UNSIGNED NULL DEFAULT NULL,
    program VARCHAR(20) NOT NULL DEFAULT '',
    judul VARCHAR(255) NOT NULL,
    tujuan TEXT NULL,
    kegiatan TEXT NULL,
    durasi INT UNSIGNED NULL DEFAULT NULL,
    tanggal DATE NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rpp_pengajar (pengajar_id),
    INDEX idx_rpp_program (program)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "OK: tabel `perwalian`, `jadwal_sepakatan` & `rpp` siap.\n";
