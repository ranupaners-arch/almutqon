<?php
// config/schema-verif.php — migrasi idempoten: rujukan & status sinkronisasi verifikasi di tabel `pendaftar`
// Kolom `verif_token`/`verif_code`/`verif_url` diisi saat admin konfirmasi LUNAS
// (didaftarkan ke API internal verif.almutqon v1). Kolom status `verif_*` dipakai
// rekonsiliasi: pending (belum/skipped), synced (terdaftar), failed (gagal + error).
// Wajib dijalankan juga di DB live.
// Jalankan: php config/schema-verif.php
require_once __DIR__ . '/database.php';

$cols = [
    'verif_token'  => "ALTER TABLE `pendaftar` ADD COLUMN `verif_token` CHAR(64) NULL DEFAULT NULL AFTER `no_invoice`",
    'verif_code'   => "ALTER TABLE `pendaftar` ADD COLUMN `verif_code` CHAR(6) NULL DEFAULT NULL AFTER `verif_token`",
    'verif_url'    => "ALTER TABLE `pendaftar` ADD COLUMN `verif_url` VARCHAR(255) NULL DEFAULT NULL AFTER `verif_code`",
    'verif_status' => "ALTER TABLE `pendaftar` ADD COLUMN `verif_status` ENUM('pending','synced','failed') NULL DEFAULT NULL AFTER `verif_url`",
    'verif_attempts' => "ALTER TABLE `pendaftar` ADD COLUMN `verif_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `verif_status`",
    'verif_last_error' => "ALTER TABLE `pendaftar` ADD COLUMN `verif_last_error` VARCHAR(500) NULL DEFAULT NULL AFTER `verif_attempts`",
    'verif_synced_at' => "ALTER TABLE `pendaftar` ADD COLUMN `verif_synced_at` DATETIME NULL DEFAULT NULL AFTER `verif_last_error`",
];

foreach ($cols as $name => $sql) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'pendaftar' AND column_name = ?"
    );
    $stmt->execute([$name]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec($sql);
        echo "  + kolom `{$name}` ditambahkan\n";
    } else {
        echo "  = kolom `{$name}` sudah ada\n";
    }
}

echo "OK: tabel `pendaftar` siap untuk rujukan verifikasi.\n";
