<?php
require_once __DIR__ . '/../tools/cli-only.php';

// Tes format, immutability, sequence, prefix, dan rollback generator identitas.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/identity.php';

$pass = 0;
$fail = 0;
function identity_test(string $name, bool $condition): void
{
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "PASS {$name}\n";
    } else {
        $fail++;
        echo "FAIL {$name}\n";
    }
}

$member = generate_member_id($pdo);
identity_test('Member ID selalu 10 digit', identity_is_member_id($member));

$pdo->beginTransaction();
try {
    $e1 = generate_enrollment_id($pdo, 2026, 'offline', '01');
    $e2 = generate_enrollment_id($pdo, 2026, 'offline', '01');
    $eBatch = generate_enrollment_id($pdo, 2026, 'offline', '02');
    $eProgram = generate_enrollment_id($pdo, 2026, 'online', '01');
    $staff1 = generate_staff_id($pdo, 2026);
    $staff2 = generate_staff_id($pdo, 2026);

    identity_test('Enrollment ID format YYPPBBNNNN', preg_match('/^260101[0-9]{4}$/', $e1) === 1);
    identity_test('Sequence enrollment bertambah', (int) substr($e2, 6) === (int) substr($e1, 6) + 1);
    identity_test('Batch berbeda menghasilkan prefix berbeda', str_starts_with($eBatch, '260102'));
    identity_test('Program berbeda menghasilkan prefix berbeda', str_starts_with($eProgram, '260201'));
    identity_test('Staff ID format YY90NNNNNN', preg_match('/^2690[0-9]{6}$/', $staff1) === 1);
    identity_test('Sequence staff bertambah', (int) substr($staff2, 4) === (int) substr($staff1, 4) + 1);
} finally {
    $pdo->rollBack();
}

$user = $pdo->query('SELECT id, member_id FROM users ORDER BY id LIMIT 1')->fetch();
if ($user) {
    $pdo->beginTransaction();
    try {
        $first = ensure_user_member_id($pdo, (int) $user['id']);
        $second = ensure_user_member_id($pdo, (int) $user['id']);
        identity_test('Member ID tidak berubah', $first === $second);
        identity_test('Resolver membuka Member ID baru', (int) (find_member_by_reference($pdo, $first)['id'] ?? 0) === (int) $user['id']);
        identity_test('Resolver membuka primary key member', (int) (find_member_by_reference($pdo, (string) $user['id'])['id'] ?? 0) === (int) $user['id']);
        $staffRow = find_staff_by_reference($pdo, (string) $user['id']);
        if (($staffRow['peran'] ?? 'user') !== 'user') {
            identity_test('Resolver membuka Staff ID baru', (int) (find_staff_by_reference($pdo, (string) $staffRow['staff_id'])['id'] ?? 0) === (int) $user['id']);
            if (!empty($staffRow['id_pengajar'])) {
                identity_test('Resolver membuka ID Pengajar legacy', (int) (find_staff_by_reference($pdo, (string) $staffRow['id_pengajar'])['id'] ?? 0) === (int) $user['id']);
            } else {
                identity_test('Akun baru tidak diberi ID Pengajar legacy', true);
            }
        }
    } finally {
        $pdo->rollBack();
    }

    $pdo->beginTransaction();
    try {
        $memberBefore = (string) $user['member_id'];
        $enrollmentA = generate_enrollment_id($pdo, 2026, 'offline', '01');
        $enrollmentB = generate_enrollment_id($pdo, 2026, 'online', '01');
        $insert = $pdo->prepare("INSERT INTO pendaftar (user_id, enrollment_id, batch_code, program, nama, whatsapp, user_email) VALUES (?, ?, '01', ?, 'UJI IDENTITAS', '0800000000', '')");
        $insert->execute([(int) $user['id'], $enrollmentA, 'offline']);
        $legacyId = (int) $pdo->lastInsertId();
        $insert->execute([(int) $user['id'], $enrollmentB, 'online']);
        $memberAfter = (string) $pdo->query('SELECT member_id FROM users WHERE id = ' . (int) $user['id'])->fetchColumn();
        identity_test('Member ID tetap saat mengikuti batch/program baru', $memberBefore === $memberAfter);
        identity_test('Resolver membuka legacy ID', (int) (find_pendaftar_by_reference($pdo, (string) $legacyId)['id'] ?? 0) === $legacyId);
        identity_test('Resolver membuka Enrollment ID baru', (string) (find_pendaftar_by_reference($pdo, $enrollmentA)['enrollment_id'] ?? '') === $enrollmentA);
    } finally {
        $pdo->rollBack();
    }
}

identity_test('Kode program tetap', identity_program_code('offline') === '01' && identity_program_code('online') === '02' && identity_program_code('dauroh') === '03');
echo "Hasil: pass={$pass} fail={$fail}\n";
exit($fail > 0 ? 1 : 0);
