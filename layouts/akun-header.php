<?php
// layouts/akun-header.php — shell dashboard untuk halaman akun, persis pola dashboard admin.
// Cara pakai di halaman: set $akun_title & $akun_aktif, lalu include file ini.
// Uses: $akun_title, $akun_aktif (set by page before including)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

// Tendang sesi yang aksesnya sudah dicabut (revoke) — toleran bila kolom belum ada.
try {
    $cekAktif = $pdo->prepare("SELECT aktif FROM users WHERE id = ?");
    $cekAktif->execute([(int) ($_SESSION['user']['id'] ?? 0)]);
    if ((int) $cekAktif->fetchColumn() === 0) {
        destroy_session();
        header('Location: /');
        exit;
    }
} catch (PDOException $e) {
    error_log('[akun] cek kolom aktif gagal: ' . $e->getMessage());
}

$akun_title = $akun_title ?? 'Akun Saya';
$akun_aktif = $akun_aktif ?? 'materi';
$userName   = $_SESSION['user']['name']  ?? 'Pengguna';
$userEmail  = $_SESSION['user']['email'] ?? '';
$userAvatar = $_SESSION['user']['avatar'] ?? null;

$__menu = [
    'materi'      => ['label' => 'Materi Kursus',      'href' => 'materi.php',        'icon' => 'folder_open'],
    'unggah'      => ['label' => 'Unggah Tugas',       'href' => 'unggah-tugas.php',  'icon' => 'upload_file'],
    'riwayat'     => ['label' => 'Riwayat Tugas Saya', 'href' => 'riwayat-tugas.php', 'icon' => 'history'],
    'kalender'    => ['label' => 'Kalender',           'href' => 'kalender.php',      'icon' => 'calendar_month'],
    'beranda'     => ['label' => 'Kembali ke Beranda', 'href' => '/',                 'icon' => 'home'],
];

// Program yang diambil user (untuk tanda di pojok profil sidebar).
$__progs = [];
if ($userEmail !== '') {
    $__st = $pdo->prepare("SELECT program, metode, status_pembayaran FROM pendaftar WHERE user_email = ? ORDER BY id DESC");
    $__st->execute([$userEmail]);
    $__progs = $__st->fetchAll();
}
$__progLabel = function (array $r): string {
    $base = match ($r['program']) {
        'offline' => 'Offline', 'online' => 'Online', 'dauroh' => 'Dauroh',
        default => ucfirst((string) $r['program']),
    };
    return $base . ($r['metode'] !== '' ? ' ' . $r['metode'] : '');
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($akun_title) ?> — Akun Almutqon</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%230F766E'/><path d='M50 18 L56 42 L80 44 L60 58 L66 80 L50 66 L34 80 L40 58 L20 44 L44 42 Z' fill='white'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <link rel="stylesheet" href="/css/admin.css?v=20260917g">
</head>
<body>

<div class="admin">

    <!-- =================== SIDEBAR =================== -->
    <aside class="admin__sidebar" id="adminSidebar">
        <a class="admin__brand" href="/">
            <img src="/assets/1.png" alt="Logo" class="admin__brand-img">
            <div>
                <div class="admin__brand-text">AL MUTQON</div>
                <div class="admin__brand-sub">AKUN SAYA</div>
            </div>
        </a>

        <nav class="admin__nav">
            <?php foreach ($__menu as $__k => $__item): ?>
                <a href="<?= $__item['href'] ?>" class="admin__nav-link <?= $akun_aktif === $__k ? 'active' : '' ?>">
                    <span class="material-symbols-rounded"><?= $__item['icon'] ?></span>
                    <?= $__item['label'] ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin__footer" style="display:flex; align-items:center; gap:10px;">
            <span style="width:36px; height:36px; border-radius:50%; overflow:hidden; flex:none; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#166534,#14532D); color:#fff; font-weight:700;">
                <?php if (!empty($userAvatar)): ?>
                    <img src="/<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($userName ?: '?', 0, 1))) ?>
                <?php endif; ?>
            </span>
            <div style="min-width:0; flex:1;">
                <p class="admin__footer-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($userName) ?></p>
                <p class="admin__footer-email" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($userEmail) ?></p>
                <?php if (empty($__progs)): ?>
                    <p style="margin:4px 0 0; font-size:11px; color:#94A3B8;">Belum ada program</p>
                <?php else: ?>
                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:6px;">
                        <?php foreach ($__progs as $__pg): ?>
                            <?php $__st = $__pg['status_pembayaran'] === 'lunas' ? ['LUNAS', '#22C55E'] : ($__pg['status_pembayaran'] === 'pending' ? ['Pending', '#F59E0B'] : ['Belum', '#94A3B8']); ?>
                            <span title="<?= $__st[0] ?>" style="font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(255,255,255,.08); border:1px solid <?= $__st[1] ?>; color:#fff; white-space:nowrap;"><?= htmlspecialchars($__progLabel($__pg)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <div class="admin__overlay" id="adminOverlay"></div>

    <!-- =================== MAIN =================== -->
    <div class="admin__main">
        <header class="admin__topbar">
            <button class="admin__hamburger" id="adminHamburger" aria-label="Buka menu">
                <span class="material-symbols-rounded">menu</span>
            </button>
            <h1 class="admin__topbar-title"><?= htmlspecialchars($akun_title) ?></h1>
        </header>

        <main class="admin__content">
