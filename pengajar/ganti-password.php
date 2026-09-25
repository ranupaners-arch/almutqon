<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/verif-client.php';
require_pengajar();
$pengajarId = (int) ($_SESSION['pengajar']['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $lama = (string) ($_POST['password_lama'] ?? ''); $baru = (string) ($_POST['password_baru'] ?? ''); $konfirmasi = (string) ($_POST['password_konfirmasi'] ?? '');
    $flash = ['ok' => false, 'message' => 'Password gagal diperbarui.'];
    if ($lama === '' || $baru === '' || $konfirmasi === '') $flash['message'] = 'Semua kolom password wajib diisi.';
    elseif (strlen($baru) < 8 || !preg_match('/[A-Za-z]/', $baru) || !preg_match('/[0-9]/', $baru)) $flash['message'] = 'Password baru minimal 8 karakter dan harus mengandung huruf serta angka.';
    elseif ($baru !== $konfirmasi) $flash['message'] = 'Konfirmasi password tidak cocok.';
    else {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? AND peran != \'user\' AND aktif = 1'); $stmt->execute([$pengajarId]); $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($lama, (string) $hash)) $flash['message'] = 'Password lama salah.';
        else {
            $newHash = password_hash($baru, PASSWORD_DEFAULT);
            $userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $userStmt->execute([$pengajarId]);
            $sync = verif_sync_password($pdo, $userStmt->fetch(PDO::FETCH_ASSOC) ?: [], $newHash);
            if (!$sync['ok']) {
                $flash['message'] = 'Password belum diubah karena sinkronisasi verifikasi gagal. Silakan coba lagi.';
                error_log('[pengajar-password] sinkronisasi verif gagal: ' . $sync['error']);
            } else {
                $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $pengajarId]);
                $flash = ['ok' => true, 'message' => 'Password Al-Mutqon dan verifikasi berhasil diperbarui.'];
            }
        }
    }
    $_SESSION['pengajar_password_flash'] = $flash; header('Location: ganti-password.php'); exit;
}
$flash = $_SESSION['pengajar_password_flash'] ?? null; unset($_SESSION['pengajar_password_flash']);
$pengajar_title = 'Ganti Password'; $pengajar_aktif = 'profil'; include __DIR__ . '/../layouts/pengajar-header.php';
?>
<div style="max-width:580px;margin:0 auto;width:100%;display:flex;flex-direction:column;justify-content:center;min-height:calc(100vh - 180px);">
<?php if ($flash): ?><div class="admin__alert <?= $flash['ok'] ? 'admin__alert--success' : 'admin__alert--error' ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
<section class="admin__card"><div class="admin__card-title">Ubah Kata Sandi</div><p class="sub" style="margin:4px 0 20px;">Masukkan password lama untuk verifikasi, lalu buat password baru.</p><form method="post"><?= csrf_field() ?><div class="admin__form-group"><label class="admin__form-label" for="password_lama">Password Lama</label><div class="admin__password-wrap"><input class="admin__input" id="password_lama" type="password" name="password_lama" required autocomplete="current-password"><button type="button" class="admin__password-toggle" data-toggle="password_lama" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button></div></div><div class="admin__form-group"><label class="admin__form-label" for="password_baru">Password Baru</label><div class="admin__password-wrap"><input class="admin__input" id="password_baru" type="password" name="password_baru" minlength="8" required autocomplete="new-password"><button type="button" class="admin__password-toggle" data-toggle="password_baru" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button></div><p class="admin__form-hint">Minimal 8 karakter, mengandung huruf dan angka.</p></div><div class="admin__form-group"><label class="admin__form-label" for="password_konfirmasi">Konfirmasi Password Baru</label><div class="admin__password-wrap"><input class="admin__input" id="password_konfirmasi" type="password" name="password_konfirmasi" minlength="8" required autocomplete="new-password"><button type="button" class="admin__password-toggle" data-toggle="password_konfirmasi" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button></div></div><button type="submit" class="admin__btn admin__btn--primary">Simpan Password Baru</button> <a class="admin__btn admin__btn--outline" href="profil.php">Kembali ke Pengaturan</a></form></section>
</div>
<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
