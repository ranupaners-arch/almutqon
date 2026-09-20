<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $old  = $_POST['password_lama'] ?? '';
    $new  = $_POST['password_baru'] ?? '';
    $conf = $_POST['konfirmasi_password'] ?? '';

    if ($new !== $conf) {
        flash_error("Konfirmasi password tidak cocok.");
    } else {
        $result = update_password($pdo, (int) $_SESSION['user']['id'], $old, $new);
        if ($result['ok']) flash($result['message']);
        else flash_error($result['message']);
    }
    header('Location: ganti-password.php');
    exit;
}

pesan_flash();

$admin_title = 'Ganti Password';
$admin_aktif = 'password';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__card" style="max-width:560px; margin:0 auto;">
    <form method="POST" autocomplete="off">
        <?= csrf_field() ?>
        <div class="admin__form-group">
            <label class="admin__form-label" for="password_lama">Password Lama</label>
            <input type="password" id="password_lama" name="password_lama" class="admin__input" required autofocus>
        </div>
        <div class="admin__form-group">
            <label class="admin__form-label" for="password_baru">Password Baru</label>
            <input type="password" id="password_baru" name="password_baru" class="admin__input" required>
            <p class="admin__form-hint">Minimal 6 karakter.</p>
        </div>
        <div class="admin__form-group">
            <label class="admin__form-label" for="konfirmasi_password">Konfirmasi Password Baru</label>
            <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="admin__input" required>
        </div>
        <div class="admin__form-row">
            <button type="submit" class="admin__btn admin__btn--primary">Simpan Password</button>
            <a href="daftar-artikel.php" class="admin__btn admin__btn--outline">Batal</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
