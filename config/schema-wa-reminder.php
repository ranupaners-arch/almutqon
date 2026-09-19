<?php
// config/schema-wa-reminder.php — migrasi idempoten: penanda reminder WA di tabel `pendaftar`
// Kolom `wa_reminder_at` diisi saat reminder <3 jam terkirim → cron tidak mengulang.
// Jalankan: php config/schema-wa-reminder.php
require_once __DIR__ . '/database.php';

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'pendaftar' AND column_name = 'wa_reminder_at'"
);
$stmt->execute();
if ((int) $stmt->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE `pendaftar` ADD COLUMN `wa_reminder_at` DATETIME NULL DEFAULT NULL AFTER `batas_bayar`");
    echo "  + kolom `wa_reminder_at` ditambahkan\n";
} else {
    echo "  = kolom `wa_reminder_at` sudah ada\n";
}

echo "OK: tabel `pendaftar` siap untuk reminder WA.\n";
