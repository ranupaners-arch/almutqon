<?php
declare(strict_types=1);

// Reset lokal yang disengaja. Wajib memakai dua pengaman agar tidak terpanggil tak sengaja.
// php tools/reset-database-identitas.php --execute --confirm=RESET-ALL-DATABASES

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = getopt('', ['execute', 'confirm:']);
if (!isset($options['execute']) || ($options['confirm'] ?? '') !== 'RESET-ALL-DATABASES') {
    fwrite(STDERR, "Dibatalkan. Gunakan --execute --confirm=RESET-ALL-DATABASES.\n");
    exit(2);
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/identity.php';

function reset_verif_connection(): PDO
{
    $values = parse_ini_file(dirname(__DIR__, 2) . '/verif.almutqon/.env', false, INI_SCANNER_RAW);
    if (!is_array($values)) {
        throw new RuntimeException('Konfigurasi database verifikasi tidak ditemukan.');
    }
    $host = (string) ($values['DB_HOST'] ?? '127.0.0.1');
    $port = (string) ($values['DB_PORT'] ?? '3306');
    $name = (string) ($values['DB_DATABASE'] ?? 'db_almutqon-verif');
    $user = (string) ($values['DB_USERNAME'] ?? 'root');
    $pass = (string) ($values['DB_PASSWORD'] ?? '');
    return new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function reset_tables(PDO $connection): void
{
    $tables = $connection->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    $connection->exec('SET FOREIGN_KEY_CHECKS = 0');
    try {
        foreach ($tables as $table) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $table)) {
                throw new RuntimeException('Nama tabel tidak aman: ' . $table);
            }
            $connection->exec('TRUNCATE TABLE `' . $table . '`');
        }
    } finally {
        $connection->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}

function seed_password(): string
{
    return bin2hex(random_bytes(9)) . 'Aa1!';
}

$verif = reset_verif_connection();
$requiredWebsite = ['users', 'pendaftar', 'identity_sequences'];
$requiredVerif = ['staff', 'dokumen', 'activity_log', 'system_actors'];
foreach ($requiredWebsite as $table) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    if ((int) $stmt->fetchColumn() !== 1) throw new RuntimeException("Tabel website {$table} belum siap.");
}
foreach ($requiredVerif as $table) {
    $stmt = $verif->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    if ((int) $stmt->fetchColumn() !== 1) throw new RuntimeException("Tabel verifikasi {$table} belum siap.");
}

$credentials = [
    'admin' => ['email' => 'admin.almutqon@gmail.com', 'password' => seed_password()],
    'direktur' => ['email' => 'direktur@almutqon.test', 'password' => seed_password()],
    'peserta' => ['email' => 'peserta@almutqon.test', 'password' => seed_password()],
];

reset_tables($verif);
reset_tables($pdo);

$pdo->beginTransaction();
$verif->beginTransaction();
try {
    $accounts = [
        'admin' => ['name' => 'Admin Al-Mutqon', 'role' => 'admin'],
        'direktur' => ['name' => 'Direktur Al-Mutqon', 'role' => 'direktur'],
        'peserta' => ['name' => 'Peserta Al-Mutqon', 'role' => 'user'],
    ];
    $insertUser = $pdo->prepare('INSERT INTO users (member_id, staff_id, name, email, password, peran, id_pengajar, aktif) VALUES (?, ?, ?, ?, ?, ?, NULL, 1)');
    foreach ($accounts as $key => &$account) {
        $memberId = generate_member_id($pdo);
        $staffId = $account['role'] === 'user' ? null : generate_staff_id($pdo, (int) date('Y'));
        $insertUser->execute([$memberId, $staffId, $account['name'], $credentials[$key]['email'], password_hash($credentials[$key]['password'], PASSWORD_DEFAULT), $account['role']]);
        $account['id'] = (int) $pdo->lastInsertId();
        $account['member_id'] = $memberId;
        $account['staff_id'] = $staffId;
    }
    unset($account);

    $enrollmentId = generate_enrollment_id($pdo, (int) date('Y'), 'offline', '01');
    $pdo->prepare("INSERT INTO pendaftar (user_id, enrollment_id, batch_code, program, nama, whatsapp, metode, jumlah_peserta, user_email, status) VALUES (?, ?, '01', 'offline', ?, ?, 'Peserta', 1, ?, 'baru')")
        ->execute([$accounts['peserta']['id'], $enrollmentId, $accounts['peserta']['name'], '08000000000', $credentials['peserta']['email']]);

    $verif->prepare('INSERT INTO system_actors (actor_key, display_name, is_active) VALUES (?, ?, 1)')
        ->execute(['almutqon_bridge', 'ALMUTQON Bridge (sistem)']);
    $insertStaff = $verif->prepare('INSERT INTO staff (nip, almutqon_user_id, nama_lengkap, jabatan, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
    foreach (['admin', 'direktur'] as $key) {
        $insertStaff->execute([
            $accounts[$key]['staff_id'], $accounts[$key]['id'], $accounts[$key]['name'],
            $key === 'admin' ? 'Admin' : 'Direktur', $credentials[$key]['email'],
            password_hash($credentials[$key]['password'], PASSWORD_DEFAULT), $key,
        ]);
        $pdo->prepare('UPDATE users SET verif_staff_id = ? WHERE id = ?')->execute([(int) $verif->lastInsertId(), $accounts[$key]['id']]);
    }

    $verif->commit();
    $pdo->commit();
} catch (Throwable $e) {
    if ($verif->inTransaction()) $verif->rollBack();
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}

$result = [
    'admin' => $credentials['admin'] + ['member_id' => $accounts['admin']['member_id'], 'staff_id' => $accounts['admin']['staff_id']],
    'direktur' => $credentials['direktur'] + ['member_id' => $accounts['direktur']['member_id'], 'staff_id' => $accounts['direktur']['staff_id']],
    'peserta' => $credentials['peserta'] + ['member_id' => $accounts['peserta']['member_id'], 'enrollment_id' => $enrollmentId],
    'system_actor' => ['actor_key' => 'almutqon_bridge', 'display_name' => 'ALMUTQON Bridge (sistem)'],
];
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
