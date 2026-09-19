<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/csrf.php';

if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
$jenis_pesan = $_SESSION['jenis_pesan'] ?? '';
unset($_SESSION['pesan_sukses']);
unset($_SESSION['jenis_pesan']);

// Apakah akun ini punya password? (akun Google-only tidak punya)
$stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
$stmt->execute([(int) $_SESSION['user']['id']]);
$hasPassword = !empty($stmt->fetchColumn());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $old = $_POST['password_lama'] ?? '';
    $new = $_POST['password_baru'] ?? '';
    $conf = $_POST['konfirmasi_password'] ?? '';

    if ($new !== $conf) {
        $_SESSION['pesan_sukses'] = 'Konfirmasi password tidak cocok.';
        $_SESSION['jenis_pesan'] = 'error';
    } else {
        $result = update_password($pdo, (int) $_SESSION['user']['id'], $old, $new);
        $_SESSION['pesan_sukses'] = $result['message'];
        $_SESSION['jenis_pesan'] = $result['ok'] ? 'success' : 'error';
    }

    header('Location: ganti-password.php');
    exit;
}

include __DIR__ . '/../../layouts/header.php';
?>

<!-- =================== PAGE HERO =================== -->
<section class="page-hero">
    <div class="container">
        <span class="section-label">Akun</span>
        <h1 class="page-hero__title">Ganti Password</h1>
        <p class="page-hero__desc">Perbarui password akun Anda agar tetap aman.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px; padding-bottom: 80px; min-height: 60vh;">
    <div class="container" style="max-width: 480px;">

        <?php if ($pesan_sukses): ?>
            <div style="padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 15px; border-left: 4px solid;
                        background: <?= $jenis_pesan === 'success' ? '#dcfce7' : '#fee2e2' ?>;
                        color: <?= $jenis_pesan === 'success' ? '#166534' : '#991b1b' ?>;
                        border-left-color: <?= $jenis_pesan === 'success' ? '#22c55e' : '#ef4444' ?>;">
                <?= htmlspecialchars($pesan_sukses) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off"
              style="background: var(--surface, #fff); padding: 32px; border-radius: 12px; box-shadow: var(--shadow-sm, 0 4px 6px rgba(0,0,0,0.05));">
            <?= csrf_field() ?>
            <?php if ($hasPassword): ?>
            <div style="margin-bottom: 20px;">
                <label for="password_lama" style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-body, #475569);">Password Lama</label>
                <input type="password" id="password_lama" name="password_lama" required autofocus
                       style="width: 100%; padding: 12px 16px; border: 1px solid var(--border, #cbd5e1); border-radius: 8px; font-size: 16px; background: var(--background); color: var(--text-dark); box-sizing: border-box;">
            </div>
            <?php else: ?>
            <div style="padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; background: #f0f9ff; color: #075985; border: 1px solid #bae6fd;">
                Akun ini dibuat via Google dan belum punya password. Buat password sekarang agar bisa masuk lewat email/password juga.
            </div>
            <?php endif; ?>
            <div style="margin-bottom: 20px;">
                <label for="password_baru" style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-body, #475569);">Password Baru</label>
                <input type="password" id="password_baru" name="password_baru" required
                       style="width: 100%; padding: 12px 16px; border: 1px solid var(--border, #cbd5e1); border-radius: 8px; font-size: 16px; background: var(--background); color: var(--text-dark); box-sizing: border-box;">
                <p style="font-size: 13px; color: var(--muted, #94a3b8); margin-top: 6px;">Minimal 6 karakter.</p>
            </div>
            <div style="margin-bottom: 24px;">
                <label for="konfirmasi_password" style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-body, #475569);">Konfirmasi Password Baru</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" required
                       style="width: 100%; padding: 12px 16px; border: 1px solid var(--border, #cbd5e1); border-radius: 8px; font-size: 16px; background: var(--background); color: var(--text-dark); box-sizing: border-box;">
            </div>
            <button type="submit" class="btn btn-primary"
                    style="padding: 14px 32px; font-size: 16px; border: none; cursor: pointer; border-radius: 8px;">Simpan Password</button>
        </form>

    </div>
</section>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>