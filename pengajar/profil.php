<?php
// Pusat pengaturan akun pengajar, mengikuti pola Profil pada verif.almutqon.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pengajar.php';
require_once __DIR__ . '/../config/account-settings.php';
require_once __DIR__ . '/../config/verif-client.php';

require_pengajar();
$pengajarId = (int) ($_SESSION['pengajar']['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, name, email, avatar, peran, staff_id, member_id FROM users WHERE id = ? AND peran != \'user\' AND aktif = 1 LIMIT 1');
$stmt->execute([$pengajarId]);
$profil = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
if (!$profil) {
    destroy_session();
    header('Location: /auth/login-pengajar.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $aksi = (string) ($_POST['aksi'] ?? '');
    $flash = ['ok' => false, 'message' => 'Permintaan pengaturan tidak dikenali.'];
    if ($aksi === 'email') {
        $result = change_account_email($pdo, $pengajarId, (string) ($_POST['email'] ?? ''), (string) ($_POST['password_saat_ini'] ?? ''));
        if ($result['ok']) {
            $_SESSION['pengajar']['email'] = $result['email'];
            $current = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $current->execute([$pengajarId]);
            $sync = verif_sync_email($pdo, $current->fetch(PDO::FETCH_ASSOC) ?: []);
            $flash = ['ok' => true, 'message' => $result['message'] . ($sync['ok'] ? '' : ' Sinkronisasi ke dashboard verifikasi akan dicoba kembali.')];
        } else {
            $flash['message'] = $result['message'];
        }
    } elseif ($aksi === 'nama') {
        $nama = trim((string) ($_POST['name'] ?? ''));
        if ($nama === '' || mb_strlen($nama) > 100) {
            $flash['message'] = 'Nama lengkap wajib diisi dan maksimal 100 karakter.';
        } else {
            $current = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $current->execute([$pengajarId]);
            $userForSync = $current->fetch(PDO::FETCH_ASSOC) ?: [];
            $userForSync['name'] = $nama;
            $sync = verif_sync_profile($pdo, $userForSync);
            if (!$sync['ok']) {
                $flash['message'] = 'Nama belum diubah karena sinkronisasi verifikasi gagal. Silakan coba lagi.';
            } else {
                $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$nama, $pengajarId]);
                $_SESSION['pengajar']['name'] = $nama;
                $flash = ['ok' => true, 'message' => 'Nama profil Al-Mutqon dan verifikasi berhasil diperbarui.'];
            }
        }
    } elseif ($aksi === 'avatar') {
        $upload = $_FILES['avatar'] ?? null;
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$upload || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $flash['message'] = 'Pilih berkas avatar terlebih dahulu.';
        } elseif ((int) $upload['size'] > 1024 * 1024) {
            $flash['message'] = 'Ukuran avatar maksimal 1 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']);
            if (!isset($allowed[$mime])) {
                $flash['message'] = 'Gunakan avatar JPG, PNG, atau WebP.';
            } else {
                $directory = dirname(__DIR__) . '/assets/avatars';
                if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
                    $flash['message'] = 'Penyimpanan avatar tidak dapat disiapkan.';
                } else {
                    $filename = 'avt_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
                    $destination = $directory . '/' . $filename;
                    if (!move_uploaded_file((string) $upload['tmp_name'], $destination)) {
                        $flash['message'] = 'Avatar gagal disimpan.';
                    } else {
                        $current = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
                        $current->execute([$pengajarId]);
                        $sync = verif_sync_profile($pdo, $current->fetch(PDO::FETCH_ASSOC) ?: [], $destination);
                        if (!$sync['ok']) {
                            @unlink($destination);
                            $flash['message'] = 'Avatar belum diubah karena sinkronisasi verifikasi gagal. Silakan coba lagi.';
                        } else {
                            $newPath = 'assets/avatars/' . $filename;
                            $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$newPath, $pengajarId]);
                            $oldPath = basename((string) ($profil['avatar'] ?? ''));
                            if ($oldPath !== '' && is_file($directory . '/' . $oldPath)) @unlink($directory . '/' . $oldPath);
                            $_SESSION['pengajar']['avatar'] = $newPath;
                            $flash = ['ok' => true, 'message' => 'Avatar Al-Mutqon dan verifikasi berhasil diperbarui.'];
                        }
                    }
                }
            }
        }
    }
    $_SESSION['pengajar_pengaturan_flash'] = $flash;
    header('Location: profil.php');
    exit;
}

$flash = $_SESSION['pengajar_pengaturan_flash'] ?? null;
unset($_SESSION['pengajar_pengaturan_flash']);
$roleLabel = peran_label((string) $profil['peran']);
$roleBadge = $profil['peran'] === 'direktur' ? 'admin__badge--amber' : ($profil['peran'] === 'admin' ? 'admin__badge--blue' : 'admin__badge--green');
$pengajar_title = 'Pengaturan';
$pengajar_aktif = 'profil';
include __DIR__ . '/../layouts/pengajar-header.php';
?>
<div style="max-width:680px;margin:0 auto;width:100%;display:flex;flex-direction:column;justify-content:center;min-height:calc(100vh - 180px);">
    <?php if ($flash): ?><div class="admin__alert <?= $flash['ok'] ? 'admin__alert--success' : 'admin__alert--error' ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
    <section class="admin__card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;"><div><div class="admin__card-title">Profil</div><p class="sub" style="margin:4px 0 0;">Foto dan nama untuk akun dashboard Anda.</p></div><span class="admin__badge <?= $roleBadge ?>"><?= htmlspecialchars($roleLabel) ?></span></div>
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;"><span style="width:72px;height:72px;border-radius:50%;overflow:hidden;flex:none;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#166534,#14532D);color:#fff;font-size:28px;font-weight:800;"><?php if (!empty($profil['avatar'])): ?><img src="/<?= htmlspecialchars($profil['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><?= htmlspecialchars(mb_strtoupper(mb_substr($profil['name'], 0, 1))) ?><?php endif; ?></span><div><strong style="font-size:17px;"><?= htmlspecialchars($profil['name']) ?></strong><p class="sub" style="margin:3px 0 0;"><?= htmlspecialchars($roleLabel) ?></p></div></div>
        <form method="post" enctype="multipart/form-data" class="admin__form-row pengajar-avatar-form"><?= csrf_field() ?><input type="hidden" name="aksi" value="avatar"><div class="admin__form-group" style="flex:1;min-width:220px;margin-bottom:0;"><label class="admin__form-label" for="avatar">Ganti Avatar</label><input class="admin__input" id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required><p class="admin__form-hint">JPG, PNG, atau WebP, maksimal 1 MB.</p></div><button type="submit" class="admin__btn admin__btn--outline">Simpan Avatar</button></form>
        <form method="post" class="admin__form-row" style="align-items:end;margin-top:20px;"><?= csrf_field() ?><input type="hidden" name="aksi" value="nama"><div class="admin__form-group" style="flex:1;min-width:220px;margin-bottom:0;"><label class="admin__form-label" for="name">Nama Lengkap</label><input class="admin__input" id="name" type="text" name="name" maxlength="100" value="<?= htmlspecialchars($profil['name']) ?>" required></div><button type="submit" class="admin__btn admin__btn--primary">Simpan Nama</button></form>
    </section>
    <section class="admin__card"><div class="admin__card-title">Info Akun</div><div class="admin__table-wrap"><table class="admin__table"><tbody><tr><th>Staff ID</th><td><code><?= htmlspecialchars($profil['staff_id'] ?: '-') ?></code></td></tr><tr><th>Member ID</th><td><code><?= htmlspecialchars($profil['member_id'] ?: '-') ?></code></td></tr><tr><th>Email</th><td><?= htmlspecialchars($profil['email']) ?></td></tr></tbody></table></div><button type="button" id="btn-ganti-email" class="admin__btn admin__btn--outline" style="margin-top:20px;">Ganti Email</button><form method="post" id="form-ganti-email" style="margin-top:20px;display:none;"><?= csrf_field() ?><input type="hidden" name="aksi" value="email"><div class="admin__form-group"><label class="admin__form-label" for="email-pengajar">Email Baru</label><input class="admin__input" id="email-pengajar" type="email" name="email" maxlength="150" value="<?= htmlspecialchars($profil['email']) ?>" required><p class="admin__form-hint">Email dipakai untuk login dashboard dan harus unik.</p></div><div class="admin__form-group"><label class="admin__form-label" for="password-email-pengajar">Password Saat Ini</label><div class="admin__password-wrap"><input class="admin__input" id="password-email-pengajar" type="password" name="password_saat_ini" required autocomplete="current-password"><button type="button" class="admin__password-toggle" data-toggle="password-email-pengajar" aria-label="Tampilkan password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button></div></div><div style="display:flex;gap:10px;align-items:center;"><button type="submit" class="admin__btn admin__btn--primary">Simpan Email</button><button type="button" id="btn-batal-email" class="admin__btn admin__btn--outline">Batal</button></div></form><script>(function(){var btn=document.getElementById('btn-ganti-email'),form=document.getElementById('form-ganti-email'),btl=document.getElementById('btn-batal-email');if(!btn||!form)return;btn.addEventListener('click',function(){btn.style.display='none';form.style.display='block';form.querySelector('input[name="password_saat_ini"]').focus();});btl.addEventListener('click',function(){form.style.display='none';btn.style.display='inline-flex';form.querySelector('input[name="email"]').value=form.querySelector('input[name="email"]').defaultValue;});})();</script></section>
    <section class="admin__card"><div class="admin__card-title">Keamanan</div><p class="sub" style="margin:4px 0 16px;">Gunakan password yang kuat dan jangan bagikan kepada orang lain.</p><a class="admin__btn admin__btn--primary" href="ganti-password.php"><span class="material-symbols-rounded">lock</span> Ganti Password</a></section>
</div>
<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
