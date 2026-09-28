<?php
// Migrasi agenda kegiatan (kalender admin dan user).
// Cakupan: 'global' (semua user) atau per program (hanya peserta LUNAS program tsb).
require_once dirname(__DIR__, 2) . '/tools/cli-only.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NULL DEFAULT NULL,
    jam_selesai TIME NULL DEFAULT NULL,
    lokasi VARCHAR(255) NOT NULL DEFAULT '',
    cakupan ENUM('global','offline','online','dauroh') NOT NULL DEFAULT 'global',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agenda_tanggal (tanggal),
    INDEX idx_agenda_cakupan (cakupan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "OK: tabel `agenda` siap.\n";
