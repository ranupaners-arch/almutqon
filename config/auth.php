<?php
// config/auth.php — helper autentikasi admin + sesi + reset password

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Daftar email yang berhak mengelola halaman admin.
// Gunakan email yang SUDAH terdaftar di tabel users.
$ADMIN_EMAILS = [
    'admin.almutqon@gmail.com',
];

function is_admin(): bool
{
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}
function require_admin(): void
{
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }
}

// === Dashboard pengajar (sesi 'pengajar', terpisah dari sesi 'user') ===
function is_pengajar(): bool
{
    return isset($_SESSION['pengajar']) && ($_SESSION['pengajar']['aktif'] ?? 0) == 1;
}

function require_pengajar(): void
{
    if (!is_pengajar()) {
        header('Location: /auth/login-pengajar.php');
        exit;
    }
}

// Direktur = akses penuh SDM + monitoring. Selain itu = akses reguler.
function is_direktur(): bool
{
    return is_pengajar() && (($_SESSION['pengajar']['peran'] ?? '') === 'direktur');
}

function require_direktur(): void
{
    if (!is_direktur()) {
        http_response_code(403);
        exit('Akses khusus Direktur.');
    }
}

function destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Cegah session fixation: ganti ID sesi setiap kali status autentikasi berubah
// (login, daftar, OAuth, reset password). Panggil SEBELUM menulis data sesi baru.
function regenerate_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function update_password(PDO $pdo, int $userId, string $oldPassword, string $newPassword): array
{
    if (strlen($newPassword) < 6) {
        return ['ok' => false, 'message' => 'Password baru minimal 6 karakter.'];
    }

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    // Akun tanpa password (mis. dibuat via Google OAuth) tidak perlu password lama
    $stored = $row['password'] ?? null;
    if ($stored !== null && $stored !== '' && !password_verify($oldPassword, $stored)) {
        return ['ok' => false, 'message' => 'Password lama salah.'];
    }

    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userId]);

    return ['ok' => true, 'message' => 'Password berhasil diganti.'];
}

// === Flash messages (PRG pattern) ===
function flash(string $msg): void {
    $_SESSION['pesan_sukses'] = $msg;
    $_SESSION['jenis_pesan'] = 'success';
}
function flash_error(string $msg): void {
    $_SESSION['pesan_sukses'] = $msg;
    $_SESSION['jenis_pesan'] = 'error';
}
function pesan_flash(): void {
    global $pesan_sukses, $jenis_pesan;
    $pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
    $jenis_pesan  = $_SESSION['jenis_pesan'] ?? 'success';
    unset($_SESSION['pesan_sukses'], $_SESSION['jenis_pesan']);
}

// Simpan token reset (hapus token lama email tsb), kembalikan token hex
function store_reset_token(PDO $pdo, string $email): string
{
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
    $stmt->execute([$email]);

    $stmt = $pdo->prepare(
        'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
    );
    $stmt->execute([$email, $token]);

    return $token;
}

// Cari token reset yang valid (ada, belum dipakai, belum kedaluwarsa)
function find_valid_reset(PDO $pdo, string $token): array|false
{
    $stmt = $pdo->prepare(
        'SELECT id, email FROM password_resets
         WHERE token = ? AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$token]);
    return $stmt->fetch();
}

// Tandai token sudah dipakai (sekali pakai)
function mark_reset_used(PDO $pdo, string $token): void
{
    $stmt = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
    $stmt->execute([$token]);
}

// Perpanjang cookie sesi 30 hari (fitur "Ingat saya")
function remember_me(): void
{
    if (!empty($_POST['remember'])) {
        setcookie(session_name(), session_id(), [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}