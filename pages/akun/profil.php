<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../config/account-settings.php';

if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
$jenis_pesan = $_SESSION['jenis_pesan'] ?? '';
unset($_SESSION['pesan_sukses']);
unset($_SESSION['jenis_pesan']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (($_POST['aksi'] ?? '') === 'email') {
        $result = change_account_email($pdo, $user_id, (string) ($_POST['email'] ?? ''), (string) ($_POST['password_saat_ini'] ?? ''));
        if ($result['ok']) {
            $_SESSION['user']['email'] = $result['email'];
        }
        $_SESSION['pesan_sukses'] = $result['message'];
        $_SESSION['jenis_pesan'] = $result['ok'] ? 'success' : 'error';
        header('Location: profil.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $avatar_path = $user['avatar'] ?? null;

    if ($name === '') {
        $_SESSION['pesan_sukses'] = 'Nama lengkap wajib diisi.';
        $_SESSION['jenis_pesan'] = 'error';
    } else {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $target_dir = __DIR__ . '/../../assets/avatars/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $_FILES['avatar']['tmp_name']);
            $max_size = 1024 * 1024; // 1 MB

            if (!isset($allowed[$mime])) {
                $_SESSION['pesan_sukses'] = 'Format avatar tidak didukung. Gunakan JPG, PNG, atau WebP.';
                $_SESSION['jenis_pesan'] = 'error';
            } elseif ($_FILES['avatar']['size'] > $max_size) {
                $_SESSION['pesan_sukses'] = 'Ukuran avatar maksimal 1 MB.';
                $_SESSION['jenis_pesan'] = 'error';
            } else {
                $new_name = 'avt_' . bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                $target = $target_dir . $new_name;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                    if (!empty($user['avatar'])) {
                        $old_file = $target_dir . pathinfo($user['avatar'], PATHINFO_BASENAME);
                        if (file_exists($old_file)) {
                            @unlink($old_file);
                        }
                    }
                    $avatar_path = 'assets/avatars/' . $new_name;
                }
            }
        }

        if ($jenis_pesan !== 'error') {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, avatar = ? WHERE id = ?');
            $stmt->execute([$name, $avatar_path, $user_id]);

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['avatar'] = $avatar_path;
            $user = $_SESSION['user'];

            $_SESSION['pesan_sukses'] = 'Profil berhasil diperbarui.';
            $_SESSION['jenis_pesan'] = 'success';
        }
    }

    header('Location: profil.php');
    exit;
}

include __DIR__ . '/../../layouts/header.php';
?>

<!-- =================== PAGE HERO =================== -->
<section class="page-hero">
    <div class="container">
        <span class="section-label">Akun</span>
        <h1 class="page-hero__title">Profil Saya</h1>
        <p class="page-hero__desc">Perbarui nama dan foto profil Anda.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px; padding-bottom: 80px; min-height: 60vh;">
    <div class="container" style="max-width: 520px;">

        <?php if ($pesan_sukses): ?>
            <div style="padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 15px; border-left: 4px solid;
                        background: <?= $jenis_pesan === 'success' ? '#dcfce7' : '#fee2e2' ?>;
                        color: <?= $jenis_pesan === 'success' ? '#166534' : '#991b1b' ?>;
                        border-left-color: <?= $jenis_pesan === 'success' ? '#22c55e' : '#ef4444' ?>;">
                <?= htmlspecialchars($pesan_sukses) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data"
              style="background: var(--surface, #fff); padding: 32px; border-radius: 12px; box-shadow: var(--shadow-sm, 0 4px 6px rgba(0,0,0,0.05));">
            <?= csrf_field() ?>
            <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 24px;">
                <div id="avatarPreview" style="width: 96px; height: 96px; border-radius: 50%; overflow: hidden;
                            background: linear-gradient(135deg, var(--forest, #166534), var(--forest-dark, #14532D)); color: #fff; font-size: 40px; font-weight: 700;
                            display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($user['name'] ?: '?', 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <label for="avatar" style="font-size: 14px; color: var(--primary); font-weight: 600; cursor: pointer;">Ubah Foto Profil</label>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="avatar" style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-body, #475569);">Foto Profil</label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp"
                       style="width: 100%; padding: 10px; border: 2px dashed var(--border, #cbd5e1); border-radius: 8px; background: var(--background, #f8fafc);">
                <p style="font-size: 13px; color: var(--muted, #94a3b8); margin-top: 6px;">JPG, PNG, atau WebP. Maksimal 1 MB.</p>
            </div>

            <div style="margin-bottom: 24px;">
                <label for="name" style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-body, #475569);">Nama Lengkap</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user['name']) ?>"
                        style="width: 100%; padding: 12px 16px; border: 1px solid var(--border, #cbd5e1); border-radius: 8px; font-size: 16px; background: var(--background); color: var(--text-dark); box-sizing: border-box;">
            </div>

            <button type="submit" class="btn btn-primary"
                    style="padding: 14px 32px; font-size: 16px; border: none; cursor: pointer; border-radius: 8px;">Simpan Profil</button>
        </form>

        <form method="POST" autocomplete="off"
              style="margin-top: 20px; background: var(--surface, #fff); padding: 32px; border-radius: 12px; box-shadow: var(--shadow-sm, 0 4px 6px rgba(0,0,0,0.05));">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="email">
            <h2 style="font-size: 20px; margin: 0 0 6px;">Email Akun</h2>
            <p style="font-size: 13px; color: var(--muted, #94a3b8); margin: 0 0 20px;">Email digunakan untuk login dan pemulihan akun.</p>
            <label for="email-akun" style="display:block;font-weight:600;margin-bottom:8px;color:var(--text-body,#475569);">Email Baru</label>
            <input type="email" id="email-akun" name="email" value="<?= htmlspecialchars($user['email']) ?>" maxlength="150" required
                   style="width:100%;padding:12px 16px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:16px;background:var(--background);color:var(--text-dark);box-sizing:border-box;">
            <label for="password-email" style="display:block;font-weight:600;margin:16px 0 8px;color:var(--text-body,#475569);">Password Saat Ini</label>
            <input type="password" id="password-email" name="password_saat_ini" autocomplete="current-password"
                   style="width:100%;padding:12px 16px;border:1px solid var(--border,#cbd5e1);border-radius:8px;font-size:16px;background:var(--background);color:var(--text-dark);box-sizing:border-box;">
            <p style="font-size:13px;color:var(--muted,#94a3b8);margin:6px 0 18px;">Wajib untuk akun dengan password. Akun Google dapat mengosongkannya.</p>
            <button type="submit" class="btn btn-primary" style="padding:14px 32px;font-size:16px;border:none;cursor:pointer;border-radius:8px;">Simpan Email</button>
        </form>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('avatar');
    const preview = document.getElementById('avatarPreview');
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
