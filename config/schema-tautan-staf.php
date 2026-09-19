<?php
// config/schema-tautan-staf.php — migrasi idempoten: tautan personel ke staff verif-almt
// Kolom `users.verif_staff_id` = id baris `staff` di db_almutqon-verif (lintas DB: tanpa FK).
// Diisi skrip tools/padankan-staf.php + hook data-pengajar. Wajib juga di DB live.
// Jalankan: php config/schema-tautan-staf.php
require_once __DIR__ . '/database.php';

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'verif_staff_id'"
);
$stmt->execute();
if ((int) $stmt->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE `users` ADD COLUMN `verif_staff_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id_pengajar`');
    echo "  + kolom `verif_staff_id` ditambahkan\n";
} else {
    echo "  = kolom `verif_staff_id` sudah ada\n";
}

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_users_verif_staff'"
);
$stmt->execute();
if ((int) $stmt->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE `users` ADD INDEX `idx_users_verif_staff` (`verif_staff_id`)');
    echo "  + index `idx_users_verif_staff` ditambahkan\n";
} else {
    echo "  = index `idx_users_verif_staff` sudah ada\n";
}

echo "OK: tabel `users` siap untuk tautan staf verifikasi.\n";
