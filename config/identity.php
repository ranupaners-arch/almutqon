<?php
// Generator dan resolver business identifier Al-Mutqon.
// Primary key internal tetap dipakai untuk relasi lama; ID di sini bersifat additive.

function identity_program_code(string $program): string
{
    return match (strtolower($program)) {
        'offline' => '01',
        'online' => '02',
        'dauroh' => '03',
        default => throw new InvalidArgumentException('Program tidak memiliki kode identitas.'),
    };
}

function identity_year_two_digits(int|string $year): string
{
    $year = (int) $year;
    if ($year >= 2000 && $year <= 2099) {
        $year %= 100;
    }
    if ($year < 0 || $year > 99) {
        throw new InvalidArgumentException('Tahun identitas harus berada pada rentang 2000-2099 atau 00-99.');
    }
    return str_pad((string) $year, 2, '0', STR_PAD_LEFT);
}

function identity_batch_code(string|int $batch): string
{
    $batch = str_pad((string) $batch, 2, '0', STR_PAD_LEFT);
    if (preg_match('/^[0-9]{2}$/', $batch) !== 1 || $batch === '00') {
        throw new InvalidArgumentException('Kode batch harus dua digit antara 01 dan 99.');
    }
    return $batch;
}

function identity_is_member_id(?string $id): bool
{
    return preg_match('/^[0-9]{10}$/', (string) $id) === 1;
}

function identity_is_enrollment_id(?string $id): bool
{
    return preg_match('/^[0-9]{10}$/', (string) $id) === 1;
}

function identity_is_staff_id(?string $id): bool
{
    return preg_match('/^[0-9]{2}90[0-9]{6}$/', (string) $id) === 1;
}

function generate_member_id(PDO $pdo): string
{
    $check = $pdo->prepare('SELECT 1 FROM users WHERE member_id = ? LIMIT 1');
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $id = (string) random_int(1000000000, 9999999999);
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            return $id;
        }
    }
    throw new RuntimeException('Member ID unik gagal dibuat setelah 100 percobaan.');
}

function identity_next_counter(PDO $pdo, string $scope, int $limit): int
{
    if (!$pdo->inTransaction()) {
        throw new LogicException('Generator sequence harus dijalankan di dalam transaksi.');
    }
    $stmt = $pdo->prepare(
        'INSERT INTO identity_sequences (scope, current_value) VALUES (?, LAST_INSERT_ID(1))
         ON DUPLICATE KEY UPDATE current_value = LAST_INSERT_ID(current_value + 1), updated_at = NOW()'
    );
    $stmt->execute([$scope]);
    $next = (int) $pdo->lastInsertId();
    if ($next > $limit) {
        throw new OverflowException("Sequence identitas {$scope} sudah mencapai batas {$limit}.");
    }
    return $next;
}

function generate_enrollment_id(PDO $pdo, int|string $year, string $program, string|int $batch = '01'): string
{
    $yy = identity_year_two_digits($year);
    $pp = preg_match('/^[0-9]{2}$/', $program) === 1 ? $program : identity_program_code($program);
    $bb = identity_batch_code($batch);
    $scope = "enrollment:{$yy}:{$pp}:{$bb}";
    $check = $pdo->prepare('SELECT 1 FROM pendaftar WHERE enrollment_id = ? LIMIT 1');
    for ($attempt = 0; $attempt < 9999; $attempt++) {
        $sequence = identity_next_counter($pdo, $scope, 9999);
        $id = $yy . $pp . $bb . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            return $id;
        }
    }
    throw new RuntimeException('Enrollment ID unik gagal dibuat.');
}

function generate_staff_id(PDO $pdo, int|string $year): string
{
    $yy = identity_year_two_digits($year);
    $scope = "staff:{$yy}:90";
    $check = $pdo->prepare('SELECT 1 FROM users WHERE staff_id = ? LIMIT 1');
    for ($attempt = 0; $attempt < 999999; $attempt++) {
        $sequence = identity_next_counter($pdo, $scope, 999999);
        $id = $yy . '90' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            return $id;
        }
    }
    throw new RuntimeException('Staff ID unik gagal dibuat.');
}

function ensure_user_member_id(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare('SELECT member_id FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$userId]);
    $current = $stmt->fetchColumn();
    if ($current === false) {
        throw new RuntimeException('Akun untuk Member ID tidak ditemukan.');
    }
    if ($current !== null && $current !== '') {
        if (!identity_is_member_id((string) $current)) {
            throw new RuntimeException('Member ID existing tidak valid dan tidak boleh ditimpa otomatis.');
        }
        return (string) $current;
    }
    $memberId = generate_member_id($pdo);
    $pdo->prepare("UPDATE users SET member_id = ? WHERE id = ? AND (member_id IS NULL OR member_id = '')")
        ->execute([$memberId, $userId]);
    return $memberId;
}

function find_pendaftar_by_reference(PDO $pdo, string $reference): array|false
{
    $reference = trim($reference);
    if ($reference === '') {
        return false;
    }
    if (ctype_digit($reference) && strlen($reference) <= 10) {
        $stmt = $pdo->prepare('SELECT * FROM pendaftar WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $reference]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }
    if (identity_is_enrollment_id($reference)) {
        $stmt = $pdo->prepare('SELECT * FROM pendaftar WHERE enrollment_id = ? LIMIT 1');
        $stmt->execute([$reference]);
        return $stmt->fetch();
    }
    return false;
}

function find_member_by_reference(PDO $pdo, string $reference): array|false
{
    $reference = trim($reference);
    if ($reference === '') {
        return false;
    }
    if (ctype_digit($reference) && strlen($reference) <= 10) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $reference]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }
    if (identity_is_member_id($reference)) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE member_id = ? LIMIT 1');
        $stmt->execute([$reference]);
        return $stmt->fetch();
    }
    return false;
}

function find_staff_by_reference(PDO $pdo, string $reference): array|false
{
    $reference = trim($reference);
    if ($reference === '') {
        return false;
    }
    if (ctype_digit($reference) && strlen($reference) <= 10) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND peran != 'user' LIMIT 1");
        $stmt->execute([(int) $reference]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE peran != 'user' AND (staff_id = ? OR id_pengajar = ?) LIMIT 1");
    $stmt->execute([$reference, $reference]);
    return $stmt->fetch();
}
