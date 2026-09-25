<?php
// Migrasi additive dan idempoten untuk Member ID, Enrollment ID, dan Staff ID.
// Jalankan: php config/schema-identitas.php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/identity.php';

function identity_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function identity_index_exists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?');
    $stmt->execute([$table, $index]);
    return (int) $stmt->fetchColumn() > 0;
}

echo "Migrasi identitas Al-Mutqon...\n";
$pdo->exec("ALTER TABLE users MODIFY COLUMN peran ENUM('user','pengajar','koor_mubtadi','koor_mutawassith','koor_mahir','koor_mutafawwiq','direktur','admin') NOT NULL DEFAULT 'user'");
echo "  = role admin tersedia\n";
$columns = [
    ['users', 'member_id', "CHAR(10) NULL DEFAULT NULL AFTER id"],
    ['users', 'staff_id', "CHAR(10) NULL DEFAULT NULL AFTER id_pengajar"],
    ['pendaftar', 'user_id', "INT NULL DEFAULT NULL AFTER id"],
    ['pendaftar', 'enrollment_id', "CHAR(10) NULL DEFAULT NULL AFTER user_id"],
    ['pendaftar', 'batch_code', "CHAR(2) NULL DEFAULT NULL AFTER enrollment_id"],
];
foreach ($columns as [$table, $column, $definition]) {
    if (!identity_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "  + {$table}.{$column}\n";
    } else {
        echo "  = {$table}.{$column}\n";
    }
}

$pdo->exec("CREATE TABLE IF NOT EXISTS identity_sequences (
    scope VARCHAR(80) NOT NULL PRIMARY KEY,
    current_value INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$indexes = [
    ['users', 'uq_users_member_id', 'UNIQUE', 'member_id'],
    ['users', 'uq_users_staff_id', 'UNIQUE', 'staff_id'],
    ['pendaftar', 'uq_pendaftar_enrollment_id', 'UNIQUE', 'enrollment_id'],
    ['pendaftar', 'idx_pendaftar_user_id', '', 'user_id'],
];
foreach ($indexes as [$table, $name, $kind, $column]) {
    if (!identity_index_exists($pdo, $table, $name)) {
        $pdo->exec("CREATE {$kind} INDEX `{$name}` ON `{$table}` (`{$column}`)");
        echo "  + index {$name}\n";
    } else {
        echo "  = index {$name}\n";
    }
}

$stats = ['scanned' => 0, 'migrated' => 0, 'skipped' => 0, 'conflict' => 0, 'failed' => 0];

foreach ($pdo->query('SELECT id, member_id FROM users ORDER BY id')->fetchAll() as $user) {
    $stats['scanned']++;
    if (identity_is_member_id((string) ($user['member_id'] ?? ''))) {
        $stats['skipped']++;
        continue;
    }
    if (!empty($user['member_id'])) {
        $stats['conflict']++;
        echo "  ! users.id={$user['id']}: Member ID existing tidak valid, perlu review manual\n";
        continue;
    }
    try {
        $pdo->beginTransaction();
        ensure_user_member_id($pdo, (int) $user['id']);
        $pdo->commit();
        $stats['migrated']++;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $stats['failed']++;
        echo "  ! users.id={$user['id']}: {$e->getMessage()}\n";
    }
}

foreach ($pdo->query("SELECT id, staff_id, created_at FROM users WHERE peran != 'user' ORDER BY id")->fetchAll() as $user) {
    $stats['scanned']++;
    if (identity_is_staff_id((string) ($user['staff_id'] ?? ''))) {
        $stats['skipped']++;
        continue;
    }
    if (!empty($user['staff_id'])) {
        $stats['conflict']++;
        echo "  ! users.id={$user['id']}: Staff ID existing tidak valid, perlu review manual\n";
        continue;
    }
    try {
        $year = (int) date('Y', strtotime((string) ($user['created_at'] ?: 'now')));
        $pdo->beginTransaction();
        $staffId = generate_staff_id($pdo, $year);
        $pdo->prepare("UPDATE users SET staff_id = ? WHERE id = ? AND (staff_id IS NULL OR staff_id = '')")
            ->execute([$staffId, (int) $user['id']]);
        $pdo->commit();
        $stats['migrated']++;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $stats['failed']++;
        echo "  ! users.id={$user['id']}: {$e->getMessage()}\n";
    }
}

$pdo->exec("UPDATE pendaftar p
    JOIN users u ON CONVERT(LOWER(u.email) USING utf8mb4) COLLATE utf8mb4_unicode_ci
        = CONVERT(LOWER(p.user_email) USING utf8mb4) COLLATE utf8mb4_unicode_ci
    SET p.user_id = u.id
    WHERE p.user_id IS NULL AND p.user_email <> ''");
foreach ($pdo->query('SELECT id, program, enrollment_id, batch_code, created_at, user_id, user_email FROM pendaftar ORDER BY id')->fetchAll() as $row) {
    $stats['scanned']++;
    if (identity_is_enrollment_id((string) ($row['enrollment_id'] ?? ''))) {
        $stats['skipped']++;
        continue;
    }
    if (!empty($row['enrollment_id'])) {
        $stats['conflict']++;
        echo "  ! pendaftar.id={$row['id']}: Enrollment ID existing tidak valid, perlu review manual\n";
        continue;
    }
    try {
        $year = (int) date('Y', strtotime((string) ($row['created_at'] ?: 'now')));
        $batch = !empty($row['batch_code']) ? (string) $row['batch_code'] : '01';
        $pdo->beginTransaction();
        $enrollmentId = generate_enrollment_id($pdo, $year, (string) $row['program'], $batch);
        $pdo->prepare("UPDATE pendaftar SET enrollment_id = ?, batch_code = ? WHERE id = ? AND (enrollment_id IS NULL OR enrollment_id = '')")
            ->execute([$enrollmentId, identity_batch_code($batch), (int) $row['id']]);
        $pdo->commit();
        $stats['migrated']++;
        if (empty($row['user_id'])) {
            echo "  ? pendaftar.id={$row['id']}: Enrollment ID dibuat, Member perlu review manual\n";
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $stats['failed']++;
        echo "  ! pendaftar.id={$row['id']}: {$e->getMessage()}\n";
    }
}

echo "Hasil: scanned={$stats['scanned']} migrated={$stats['migrated']} skipped={$stats['skipped']} conflict={$stats['conflict']} failed={$stats['failed']}\n";
exit($stats['failed'] > 0 ? 1 : 0);
