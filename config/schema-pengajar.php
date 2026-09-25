<?php
// config/schema-pengajar.php — migrasi idempoten: peran, ID pengajar & status aktif di tabel `users`
// - peran: user (peserta biasa) + 6 peran tenaga pengajar
// - id_pengajar: kode unik (mis. PGJ-0001), NULL untuk non-pengajar
// - aktif: 0 = akses dicabut (revoke) → login publik ditolak
// Jalankan: php config/schema-pengajar.php
require_once __DIR__ . '/database.php';

function tambah_kolom_pengajar(PDO $pdo, string $column, string $definition): void
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = ?"
    );
    $stmt->execute([$column]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `{$column}` {$definition}");
        echo "  + kolom `{$column}` ditambahkan\n";
    } else {
        echo "  = kolom `{$column}` sudah ada\n";
    }
}

echo "Migrasi data pengajar...\n";
tambah_kolom_pengajar($pdo, 'peran',
    "ENUM('user','pengajar','koor_mubtadi','koor_mutawassith','koor_mahir','koor_mutafawwiq','direktur','admin') NOT NULL DEFAULT 'user' AFTER theme");
$pdo->exec("ALTER TABLE users MODIFY COLUMN peran ENUM('user','pengajar','koor_mubtadi','koor_mutawassith','koor_mahir','koor_mutafawwiq','direktur','admin') NOT NULL DEFAULT 'user'");
tambah_kolom_pengajar($pdo, 'id_pengajar',
    "VARCHAR(20) NULL DEFAULT NULL AFTER peran");
tambah_kolom_pengajar($pdo, 'aktif',
    "TINYINT(1) NOT NULL DEFAULT 1 AFTER id_pengajar");

$idx = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'uq_users_id_pengajar'")->fetch();
if (!$idx) {
    $pdo->exec("CREATE UNIQUE INDEX uq_users_id_pengajar ON users (id_pengajar)");
    echo "  + index unik `uq_users_id_pengajar` ditambahkan\n";
} else {
    echo "  = index unik `uq_users_id_pengajar` sudah ada\n";
}

echo "OK: tabel `users` siap untuk data pengajar.\n";
