<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

if (is_admin()) {
    header('Location: daftar-artikel.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Sesi berakhir atau token tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            $error = 'Email dan password wajib diisi.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, password, avatar FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Email atau password salah.';
            } elseif (!in_array($user['email'], $ADMIN_EMAILS, true)) {
                $error = 'Akun ini tidak memiliki akses admin.';
            } else {
                regenerate_session();
                $_SESSION['admin'] = true;
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'avatar' => $user['avatar'] ?? null,
                ];
                header('Location: daftar-artikel.php');
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
    <title>Login Admin — Almutqon</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%230F766E'/><path d='M50 18 L56 42 L80 44 L60 58 L66 80 L50 66 L34 80 L40 58 L20 44 L44 42 Z' fill='white'/></svg>">
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
                <div class="admin__login-title">Login Admin</div>
                <div class="admin__brand-sub">AL MUTQON DASHBOARD</div>
            </div>
        </div>
        <p class="admin__login-sub">Masuk untuk mengelola artikel, pendaftar, dan donasi.</p>

        <?php if ($error): ?>
            <div class="admin__alert admin__alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <?= csrf_field() ?>
            <div class="admin__form-group">
                <label class="admin__form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="admin__input" required autofocus>
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