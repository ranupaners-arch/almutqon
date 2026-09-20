<?php
// auth/login-pengajar.php — masuk dashboard pengajar dengan ID Pengajar (PGJ-XXXX) atau email.
// Hanya akun berperangajar aktif (peran != 'user', aktif = 1).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

if (is_pengajar()) {
    header('Location: /pengajar/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Sesi berakhir atau token tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $identitas = trim($_POST['identitas'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($identitas === '' || $password === '') {
            $error = 'ID Pengajar / email dan password wajib diisi.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, password, avatar, peran, id_pengajar, aktif FROM users WHERE email = ? OR id_pengajar = ? LIMIT 1");
            $stmt->execute([$identitas, $identitas]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, (string) $user['password'])) {
                $error = 'ID / email atau password salah.';
            } elseif (($user['peran'] ?? 'user') === 'user') {
                $error = 'Akun ini bukan akun pengajar.';
            } elseif ((int) ($user['aktif'] ?? 1) === 0) {
                $error = 'Akun dinonaktifkan. Hubungi admin.';
            } else {
                regenerate_session();
                $_SESSION['pengajar'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'avatar' => $user['avatar'] ?? null,
                    'peran' => $user['peran'],
                    'id_pengajar' => $user['id_pengajar'],
                    'aktif' => 1,
                ];
                header('Location: /pengajar/');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengajar — Almutqon</title>
    <link rel="icon" type="image/png" href="/assets/favicon.png?v=20260919a">
    <link rel="shortcut icon" href="/assets/favicon.ico?v=20260919a">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/admin.css?v=20260917g">
</head>
<body>
<div class="admin__login">
    <div class="admin__login-card">
        <div class="admin__login-logo">
            <img src="/assets/1.png" alt="Logo Almutqon">
            <div>
                <div class="admin__login-title">Login Pengajar</div>
                <div class="admin__brand-sub">AL MUTQON DASHBOARD</div>
            </div>
        </div>
        <p class="admin__login-sub">Masuk dengan ID Pengajar (mis. PGJ-0001) atau email untuk mengelola kelas Anda.</p>

        <?php if ($error): ?>
            <div class="admin__alert admin__alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <?= csrf_field() ?>
            <div class="admin__form-group">
                <label class="admin__form-label" for="identitas">ID Pengajar / Email</label>
                <input type="text" id="identitas" name="identitas" class="admin__input" required autofocus placeholder="PGJ-0001 atau email">
            </div>
            <div class="admin__form-group">
                <label class="admin__form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="admin__input" required>
            </div>
            <button type="submit" class="admin__btn admin__btn--primary">Masuk</button>
        </form>

        <a href="/" class="admin__login-back">&larr; Kembali ke beranda</a>
    </div>
</div>
</body>
</html>
