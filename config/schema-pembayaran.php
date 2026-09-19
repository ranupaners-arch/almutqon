<?php
// config/schema-pembayaran.php — migrasi idempoten: tambah kolom pembayaran kursus di tabel `pendaftar`
// Jalankan: php config/schema-pembayaran.php
require_once __DIR__ . '/database.php';

function add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    $stmt->execute([$table, $column]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "  + kolom `{$column}` ditambahkan\n";
    } else {
        echo "  = kolom `{$column}` sudah ada\n";
    }
}

echo "Migrasi pembayaran kursus...\n";
add_column_if_missing($pdo, 'pendaftar', 'no_invoice',
    "VARCHAR(40) NOT NULL DEFAULT '' AFTER id");
add_column_if_missing($pdo, 'pendaftar', 'biaya',
    "INT UNSIGNED NOT NULL DEFAULT 0 AFTER jumlah_peserta");
add_column_if_missing($pdo, 'pendaftar', 'metode_pembayaran',
    "VARCHAR(20) NOT NULL DEFAULT '' AFTER biaya");
add_column_if_missing($pdo, 'pendaftar', 'status_pembayaran',
    "ENUM('belum','pending','lunas') NOT NULL DEFAULT 'belum' AFTER metode_pembayaran");
add_column_if_missing($pdo, 'pendaftar', 'tanggal_bayar',
    "DATETIME NULL DEFAULT NULL AFTER status_pembayaran");
add_column_if_missing($pdo, 'pendaftar', 'batas_bayar',
    "DATETIME NULL DEFAULT NULL AFTER tanggal_bayar");

echo "OK: tabel `pendaftar` siap untuk pembayaran kursus.\n";