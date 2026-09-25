<?php
session_start();
require_once '../config/database.php';
require_once '../config/csrf.php';
require_once '../config/auth.php';
require_once '../config/verif-client.php';

$token = trim($_POST['token'] ?? ($_GET['token'] ?? ''));
$error = '';
$sukses = '';

if ($token === '') {
    $error = 'Link reset tidak lengkap. Silakan meminta link baru.';
} else {
    $row = find_valid_reset($pdo, $token);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf();

        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        if (!$row) {
            $error = 'Token tidak valid, sudah dipakai, atau kedaluwarsa. Minta link baru.';
        } elseif ($password === '') {
            $error = 'Password baru wajib diisi.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$row['email']]);
            $user = $stmt->fetch();
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $sync = ['ok' => true, 'error' => ''];
            if ($user && ($user['peran'] ?? 'user') !== 'user') {
                $sync = verif_sync_password($pdo, $user, $newHash);
            }
            if (!$user || !$sync['ok']) {
                $error = !$user ? 'Akun tidak ditemukan.' : 'Password belum diubah karena sinkronisasi verifikasi gagal. Silakan coba lagi.';
                if (!$sync['ok']) error_log('[reset-password] sinkronisasi verif gagal: ' . $sync['error']);
            } else {
                $pdo->prepare('UPDATE users SET password = ? WHERE email = ?')->execute([$newHash, $row['email']]);
                mark_reset_used($pdo, $token);

                $email = $row['email'];

                // Arahkan admin yang mereset akunnya sendiri agar set login ulang
                regenerate_session();
                $_SESSION['admin'] = false;
                unset($_SESSION['admin']);

                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'avatar' => $user['avatar'] ?? null,
                ];

                $sukses = 'Password berhasil direset. Anda sudah masuk, silakan lanjut belajar.';
            }
        }
    } elseif (!$row) {
        $error = 'Token tidak valid, sudah dipakai, atau kedaluwarsa. Minta link baru.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Almutqon</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #F8FAFC; color: #0F172A; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; max-width: 440px; width: 100%; padding: 36px; border-radius: 16px; box-shadow: 0 12px 24px -6px rgba(0,0,0,.10); }
        .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .logo-name { font-weight: 800; letter-spacing: 1px; color: #166534; font-size: 18px; }
        .logo-sub { font-size: 10px; letter-spacing: 2px; color: #64748B; display: block; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        p.desc { color: #475569; font-size: 14px; margin-bottom: 24px; }
        label { font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px; }
        input[type=password] { width: 100%; padding: 12px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
        input[type=password]:focus { outline: none; border-color: #166534; box-shadow: 0 0 0 3px rgba(22,101,52,.15); }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 10px; background: #166534; color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; }
        .btn:hover { background: #14532D; }
        .back { display: block; text-align: center; margin-top: 16px; color: #475569; font-size: 13px; text-decoration: none; }
        .back:hover { color: #166534; }
        .flash-ok { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
        .flash-err { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <span class="material-symbols-rounded" style="color:#166534;">password</span>
            <div>
                <span class="logo-name">ALMUTQON</span>
                <span class="logo-sub">ARABIC LEARNING</span>
            </div>
        </div>
        <h1>Buat Password Baru</h1>
        <p class="desc">Masukkan password baru untuk akun Anda (minimal 6 karakter).</p>

        <?php if ($error): ?>
            <div class="flash-err"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($sukses): ?>
            <div class="flash-ok"><?= htmlspecialchars($sukses) ?></div>
            <a href="/" class="btn" style="text-align:center; text-decoration:none; display:block;">Ke Beranda</a>
        <?php else: ?>
            <form method="POST" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password baru" required minlength="6">
                <button type="submit" class="btn">Simpan Password Baru</button>
            </form>
        <?php endif; ?>
        <a href="/auth/login.php" class="back">← Kembali ke halaman masuk</a>
    </div>
</body>
</html>
