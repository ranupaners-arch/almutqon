<?php
// Migrasi konfigurasi harga, durasi sesi, dan diskon program.
require_once __DIR__ . '/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS program_prices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program ENUM('offline','online','dauroh') NOT NULL,
    metode VARCHAR(30) NOT NULL,
    harga INT UNSIGNED NOT NULL DEFAULT 0,
    harga_per_jam INT UNSIGNED NULL DEFAULT NULL,
    durasi_menit SMALLINT UNSIGNED NULL DEFAULT NULL,
    durasi_label VARCHAR(120) NOT NULL DEFAULT '',
    min_peserta INT UNSIGNED NOT NULL DEFAULT 1,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_program_metode (program, metode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS program_discounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(40) NOT NULL,
    nama VARCHAR(120) NOT NULL,
    tipe ENUM('persen','nominal') NOT NULL,
    nilai INT UNSIGNED NOT NULL DEFAULT 0,
    program ENUM('semua','offline','online','dauroh') NOT NULL DEFAULT 'semua',
    metode VARCHAR(30) NOT NULL DEFAULT '',
    mulai DATETIME NULL DEFAULT NULL,
    berakhir DATETIME NULL DEFAULT NULL,
    batas_penggunaan INT UNSIGNED NULL DEFAULT NULL,
    terpakai INT UNSIGNED NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_discount_code (kode),
    INDEX idx_discount_active (aktif, mulai, berakhir)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function add_program_column(PDO $pdo, string $column, string $definition): void
{
    $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pendaftar' AND column_name = ?");
    $q->execute([$column]);
    if ((int) $q->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE pendaftar ADD COLUMN {$column} {$definition}");
    }
}

add_program_column($pdo, 'biaya_awal', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER biaya');
add_program_column($pdo, 'diskon_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER biaya_awal');
add_program_column($pdo, 'diskon_nominal', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER diskon_id');
add_program_column($pdo, 'kode_diskon', "VARCHAR(40) NOT NULL DEFAULT '' AFTER diskon_nominal");

$defaults = [
    ['offline', 'Privat', 636000, 318000, 120, '±2 jam / sesi', 1],
    ['offline', 'Non-Privat', 299000, null, 120, 'Per sesi', 1],
    ['online', 'Privat', 298500, 199000, 90, '±90 menit / sesi', 1],
    ['online', 'Non-Privat', 160000, null, 90, 'Per sesi', 1],
    ['dauroh', 'Offline', 99000, null, null, '1–3 hari intensif', 40],
    ['dauroh', 'Online', 70000, null, null, '1–3 hari intensif', 40],
];
$ins = $pdo->prepare("INSERT INTO program_prices (program, metode, harga, harga_per_jam, durasi_menit, durasi_label, min_peserta) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE program = VALUES(program)");
foreach ($defaults as $row) {
    $ins->execute($row);
}

echo "OK: konfigurasi program dan diskon siap.\n";
