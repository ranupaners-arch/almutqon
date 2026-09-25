<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/csrf.php';

if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

$user = $_SESSION['user'];

// Ambil tanggal daftar dari database
$stmt = $pdo->prepare('SELECT created_at, theme FROM users WHERE id = ?');
$stmt->execute([(int) $user['id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$created_at = $row['created_at'] ?? null;
$themeStored = (string) ($row['theme'] ?? 'light');

include __DIR__ . '/../../layouts/header.php';
?>

<!-- =================== PAGE HERO =================== -->
<section class="page-hero">
    <div class="container">
        <span class="section-label">Akun</span>
        <h1 class="page-hero__title">Pengaturan</h1>
        <p class="page-hero__desc">Kelola informasi akun Anda.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px; padding-bottom: 80px; min-height: 60vh;">
    <div class="container" style="max-width: 560px;">

        <div style="background: var(--surface, #fff); padding: 32px; border-radius: 12px; box-shadow: var(--shadow-sm, 0 4px 6px rgba(0,0,0,0.05)); margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border, #e2e8f0);">
                <div style="width: 72px; height: 72px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
                            background: linear-gradient(135deg, var(--forest, #166534), var(--forest-dark, #14532D)); color: #fff; font-size: 30px; font-weight: 700;
                            display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($user['name'] ?: '?', 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-size: 18px; font-weight: 700; color: var(--text-dark, #0F172A);"><?= htmlspecialchars($user['name']) ?></div>
                    <div style="font-size: 14px; color: var(--muted, #94A3B8);"><?= htmlspecialchars($user['email']) ?></div>
                    <?php if ($created_at): ?>
                        <div style="font-size: 13px; color: var(--muted, #94A3B8); margin-top: 4px;">Bergabung sejak <?= date('d F Y', strtotime($created_at)) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <a href="/pages/akun/profil.php" style="display: flex; align-items: center; gap: 12px; padding: 14px 8px; border-radius: 8px; text-decoration: none; color: var(--text-body, #475569); font-weight: 500; transition: background .2s;">
                <span class="material-symbols-rounded" style="color: var(--primary);">person</span>
                <span style="flex: 1;">Profil, Foto & Email</span>
                <span class="material-symbols-rounded" style="color: var(--muted, #94A3B8); font-size: 20px;">chevron_right</span>
            </a>
            <a href="/pages/akun/pendaftaran-saya.php" style="display: flex; align-items: center; gap: 12px; padding: 14px 8px; border-radius: 8px; text-decoration: none; color: var(--text-body, #475569); font-weight: 500; transition: background .2s;">
                <span class="material-symbols-rounded" style="color: var(--primary);">receipt_long</span>
                <span style="flex: 1;">Pendaftaran Saya</span>
                <span class="material-symbols-rounded" style="color: var(--muted, #94A3B8); font-size: 20px;">chevron_right</span>
            </a>
            <a href="/pages/akun/materi.php" style="display: flex; align-items: center; gap: 12px; padding: 14px 8px; border-radius: 8px; text-decoration: none; color: var(--text-body, #475569); font-weight: 500; transition: background .2s;">
                <span class="material-symbols-rounded" style="color: var(--primary);">menu_book</span>
                <span style="flex: 1;">Mulai Belajar</span>
                <span class="material-symbols-rounded" style="color: var(--muted, #94A3B8); font-size: 20px;">chevron_right</span>
            </a>
            <a href="/pages/akun/ganti-password.php" style="display: flex; align-items: center; gap: 12px; padding: 14px 8px; border-radius: 8px; text-decoration: none; color: var(--text-body, #475569); font-weight: 500; transition: background .2s;">
                <span class="material-symbols-rounded" style="color: var(--primary);">key</span>
                <span style="flex: 1;">Ganti Password</span>
                <span class="material-symbols-rounded" style="color: var(--muted, #94A3B8); font-size: 20px;">chevron_right</span>
            </a>
        </div>

        <div style="background: var(--surface, #fff); padding: 24px 32px; border-radius: 12px; box-shadow: var(--shadow-sm, 0 4px 6px rgba(0,0,0,0.05)); margin-bottom: 24px;">
            <h3 style="margin: 0 0 8px; font-size: 16px; color: var(--text-dark, #0F172A);">Tampilan</h3>
            <p style="margin: 0 0 16px; font-size: 14px; color: var(--muted, #94A3B8);">Pilih mode tampilan: terang, gelap, atau ikuti sistem.</p>
            <?php
            $themeVariant  = 'big';
            $themeEndpoint = '/api/theme.php';
            $themeCsrf     = csrf_token();
            include __DIR__ . '/../../layouts/theme-switch.php';
            ?>
            <p style="margin: 12px 0 0; font-size: 12.5px; color: var(--muted, #94A3B8);">Sistem mengikuti terang/gelap perangkat secara otomatis.</p>
        </div>

        <div style="background: var(--surface, #fff); padding: 24px 32px; border-radius: 12px; box-shadow: var(--shadow-sm, 0 4px 6px rgba(0,0,0,0.05));">
            <h3 style="margin: 0 0 12px; font-size: 16px; color: var(--text-dark, #0F172A);">Tentang Akun</h3>
            <p style="margin: 0; font-size: 14px; color: #94A3B8; line-height: 1.6;">
                Akun digunakan untuk mengikuti program belajar Al-Mutqon dan menyimpan progres Anda.
                Halaman ini akan diperluas seiring berjalannya fitur (notifikasi, preferensi melanjutkan belajar).
            </p>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
