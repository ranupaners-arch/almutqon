<?php
session_start();
require_once '../config/database.php';
require_once '../config/csrf.php';
require_once '../config/auth.php';
require_once '../config/mail.php';

$kirim_ok = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $error = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Alur sama untuk email terdaftar maupun tidak (hindari user enumeration)
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token = store_reset_token($pdo, $email);
            $host = ($_SERVER['HTTP_HOST'] ?? 'almutqon.test');
            $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                . '://' . $host . '/auth/reset-password.php?token=' . $token;

            $subject = 'Reset Password Akun Al-Mutqon';
            $body = "Anda meminta reset password akun Al-Mutqon.\n\n"
                . "Klik link berikut untuk membuat password baru:\n{$resetUrl}\n\n"
                . "Link berlaku 30 menit. Abaikan email ini jika bukan Anda yang meminta.\n";
            // Kirim via SMTP (config/mail.php). JANGAN pernah tampilkan link reset di layar:
            // siapa pun bisa meminta reset untuk email orang lain. Saat SMTP gagal, link hanya
            // dicatat ke log server (hanya bila APP_DEBUG=true) untuk keperluan pengembangan.
            $mail = send_mail($email, $subject, $body);
            if (!$mail['ok']) {
                error_log('[mail] ' . $mail['error']);
                if (getenv('APP_DEBUG') === 'true') {
                    error_log('[reset-link-debug] ' . $resetUrl);
                }
            }
        }
        $kirim_ok = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — Almutqon</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #F8FAFC; color: #0F172A; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; max-width: 440px; width: 100%; padding: 36px; border-radius: 16px; box-shadow: 0 12px 24px -6px rgba(0,0,0,.10); }
        .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .logo img { width: 40px; height: 40px; border-radius: 8px; }
        .logo-name { font-weight: 800; letter-spacing: 1px; color: #166534; font-size: 18px; }
        .logo-sub { font-size: 10px; letter-spacing: 2px; color: #64748B; display: block; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        p.desc { color: #475569; font-size: 14px; margin-bottom: 24px; }
        label { font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px; }
        input[type=email] { width: 100%; padding: 12px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
        input[type=email]:focus { outline: none; border-color: #166534; box-shadow: 0 0 0 3px rgba(22,101,52,.15); }
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
            <span class="material-symbols-rounded" style="color:#166534;">lock_reset</span>
            <div>
                <span class="logo-name">ALMUTQON</span>
                <span class="logo-sub">ARABIC LEARNING</span>
            </div>
        </div>
        <h1>Reset Password</h1>
        <p class="desc">Masukkan email terdaftar. Jika email Anda ada di sistem, kami kirim link untuk membuat password baru (berlaku 30 menit).</p>

        <?php if ($kirim_ok): ?>
            <div class="flash-ok">
                Jika email terdaftar, link reset sudah dikirim. Cek inbox Anda (termasuk folder spam).
            </div>
        <?php elseif ($error): ?>
            <div class="flash-err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>
            <label for="email">Alamat Email</label>
            <input type="email" id="email" name="email" placeholder="nama@email.com" required autofocus>
            <button type="submit" class="btn">Kirim Link Reset</button>
        </form>
        <a href="/auth/login.php" class="back">← Kembali ke halaman masuk</a>
    </div>
</body>
</html>