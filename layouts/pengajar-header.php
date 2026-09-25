<?php
// layouts/pengajar-header.php — shell dashboard pengajar (pola dashboard admin).
// Menu menyesuaikan peran: Direktur dapat menu SDM & Kinerja (penuh), lainnya reguler.
// Uses: $pengajar_title, $pengajar_aktif (set by page before including)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/pengajar.php';

require_pengajar();

$pengajar_title = $pengajar_title ?? 'Dashboard';
$pengajar_aktif = $pengajar_aktif ?? 'dashboard';
$pgNama   = $_SESSION['pengajar']['name'] ?? 'Pengajar';
$pgId     = $_SESSION['pengajar']['staff_id'] ?? $_SESSION['pengajar']['id_pengajar'] ?? '-';
$pgPeran  = $_SESSION['pengajar']['peran'] ?? 'pengajar';
$pgAvatar = $_SESSION['pengajar']['avatar'] ?? null;

$__menu = [
    'dashboard' => ['label' => 'Dashboard',   'href' => 'index.php',  'icon' => 'dashboard'],
    'agenda'    => ['label' => 'Agenda',      'href' => 'agenda.php', 'icon' => 'calendar_month'],
    'materi'    => ['label' => 'Materi',      'href' => 'materi.php', 'icon' => 'folder_open'],
    'tugas'     => ['label' => 'Tugas Masuk', 'href' => 'tugas.php',  'icon' => 'assignment'],
    'absensi'   => ['label' => 'Absensi',       'href' => 'absensi.php','icon' => 'fact_check'],
    'rekap'     => ['label' => 'Rekap Absensi', 'href' => 'rekap.php',  'icon' => 'bar_chart'],
    'wali'      => ['label' => 'Perwalian',     'href' => 'wali.php',   'icon' => 'group'],
    'rpp'       => ['label' => 'RPP Materi',    'href' => 'rpp.php',    'icon' => 'edit_note'],
];
if (($pgPeran === 'direktur')) {
    $__menu['sdm'] = ['label' => 'SDM & Kinerja', 'href' => 'sdm.php', 'icon' => 'supervisor_account'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pengajar_title) ?> — Pengajar Almutqon</title>
    <link rel="icon" type="image/png" href="/assets/favicon.png?v=20260919a">
    <link rel="shortcut icon" href="/assets/favicon.ico?v=20260919a">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <link rel="stylesheet" href="/css/admin.css?v=20260926a">
</head>
<body>

<div class="admin">

    <!-- =================== SIDEBAR =================== -->
    <aside class="admin__sidebar" id="adminSidebar">
        <a class="admin__brand" href="index.php">
            <img src="/assets/1.png" alt="Logo" class="admin__brand-img">
            <div>
                <div class="admin__brand-text">AL MUTQON</div>
                <div class="admin__brand-sub">HUMAN RESOURCE</div>
            </div>
        </a>

        <nav class="admin__nav">
            <?php foreach ($__menu as $__k => $__item): ?>
                <a href="<?= $__item['href'] ?>" class="admin__nav-link <?= $pengajar_aktif === $__k ? 'active' : '' ?>">
                    <span class="material-symbols-rounded"><?= $__item['icon'] ?></span>
                    <?= $__item['label'] ?>
                </a>
            <?php endforeach; ?>

            <div class="admin__nav-sep"></div>

            <button class="admin__nav-link admin__nav-link--danger" id="pgLogoutBtn" style="width:100%; border:0; cursor:pointer; background:none; text-align:left; font:inherit; font-size:14px;">
                <span class="material-symbols-rounded">logout</span> Keluar
            </button>
        </nav>

        <a class="admin__footer" href="profil.php" aria-label="Buka Pengaturan akun" title="Pengaturan akun" style="display:flex; align-items:center; gap:10px; text-decoration:none;">
            <span style="width:36px; height:36px; border-radius:50%; overflow:hidden; flex:none; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#166534,#14532D); color:#fff; font-weight:700;">
                <?php if (!empty($pgAvatar)): ?>
                    <img src="/<?= htmlspecialchars($pgAvatar) ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($pgNama ?: '?', 0, 1))) ?>
                <?php endif; ?>
            </span>
            <div style="min-width:0; flex:1;">
                <p class="admin__footer-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($pgNama) ?></p>
                <p class="admin__footer-email" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars((string) $pgId) ?> · <?= htmlspecialchars(peran_label($pgPeran)) ?></p>
            </div>
        </a>
    </aside>

    <div class="admin__overlay" id="adminOverlay"></div>

    <!-- =================== MAIN =================== -->
    <div class="admin__main">
        <header class="admin__topbar">
            <button class="admin__hamburger" id="adminHamburger" aria-label="Buka menu">
                <span class="material-symbols-rounded">menu</span>
            </button>
            <h1 class="admin__topbar-title"><?= htmlspecialchars($pengajar_title) ?></h1>
        </header>

        <main class="admin__content">
