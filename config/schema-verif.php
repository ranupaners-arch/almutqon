<?php
// config/schema-verif.php — migrasi idempoten: rujukan dokumen verifikasi di tabel `pendaftar`
// Kolom `verif_token`/`verif_code`/`verif_url` diisi saat admin konfirmasi LUNAS
// (didaftarkan ke API internal verif.almutqon v1). Wajib dijalankan juga di DB live.
// Jalankan: php config/schema-verif.php
require_once __DIR__ . '/database.php';

$cols = [
    'verif_token' => "ALTER TABLE `pendaftar` ADD COLUMN `verif_token` CHAR(64) NULL DEFAULT NULL AFTER `no_invoice`",
    'verif_code'  => "ALTER TABLE `pendaftar` ADD COLUMN `verif_code` CHAR(6) NULL DEFAULT NULL AFTER `verif_token`",
    'verif_url'   => "ALTER TABLE `pendaftar` ADD COLUMN `verif_url` VARCHAR(255) NULL DEFAULT NULL AFTER `verif_code`",
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
