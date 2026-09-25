<?php
// layouts/admin-header.php — shared sidebar + topbar for admin dashboard
// Uses: $admin_title, $admin_aktif (set by page before including)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();

$admin_title  = $admin_title  ?? 'Dashboard';
$admin_aktif  = $admin_aktif  ?? 'dashboard';
$userName     = $_SESSION['user']['name']  ?? 'Admin';
$userEmail    = $_SESSION['user']['email'] ?? '';

$__menu = [
    'dashboard'  => ['label' => 'Dashboard',       'href' => 'index.php',           'icon' => 'dashboard'],
    'artikel'    => ['label' => 'Artikel',         'href' => 'daftar-artikel.php',  'icon' => 'article'],
    'tulis'      => ['label' => 'Tulis Artikel',    'href' => 'tambah-artikel.php',  'icon' => 'edit_note'],
    'pendaftar'  => ['label' => 'Data Pendaftar',   'href' => 'data-pendaftar.php',  'icon' => 'groups'],
    'pengajar'   => ['label' => 'Data Pengajar',    'href' => 'data-pengajar.php',   'icon' => 'supervisor_account'],
    'pembayaran' => ['label' => 'Pembayaran Kursus', 'href' => 'data-pembayaran.php', 'icon' => 'payments'],
    'donasi'     => ['label' => 'Data Donasi',      'href' => 'data-donasi.php',     'icon' => 'volunteer_activism'],
    'agenda'     => ['label' => 'Agenda Kegiatan',  'href' => 'data-agenda.php',     'icon' => 'calendar_month'],
    'jurnal'     => ['label' => 'Tugas Peserta',    'href' => 'data-jurnal.php',     'icon' => 'menu_book'],
    'password'   => ['label' => 'Ganti Password',   'href' => 'ganti-password.php',  'icon' => 'key'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($admin_title) ?> — Admin Almutqon</title>
    <link rel="icon" type="image/png" href="/assets/favicon.png?v=20260919a">
    <link rel="shortcut icon" href="/assets/favicon.ico?v=20260919a">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if (!empty($admin_quill)): ?>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <?php endif; ?>
    <link rel="stylesheet" href="/css/admin.css?v=20260925f">
</head>
<body>

<div class="admin">

    <!-- =================== SIDEBAR =================== -->
    <aside class="admin__sidebar" id="adminSidebar">
        <a class="admin__brand" href="index.php">
            <img src="/assets/1.png" alt="Logo" class="admin__brand-img">
            <div>
                <div class="admin__brand-text">AL MUTQON</div>
                <div class="admin__brand-sub">DASHBOARD</div>
            </div>
        </a>

        <nav class="admin__nav">
            <?php foreach ($__menu as $__k => $__item): ?>
                <a href="<?= $__item['href'] ?>" class="admin__nav-link <?= $admin_aktif === $__k ? 'active' : '' ?>">
                    <span class="material-symbols-rounded"><?= $__item['icon'] ?></span>
                    <?= $__item['label'] ?>
                </a>
            <?php endforeach; ?>

            <a href="/" class="admin__nav-link" target="_blank">
                <span class="material-symbols-rounded">language</span> Lihat Situs
            </a>

            <div class="admin__nav-sep"></div>

            <a href="logout.php" class="admin__nav-link admin__nav-link--danger">
                <span class="material-symbols-rounded">logout</span> Keluar
            </a>
        </nav>

        <div class="admin__footer">
            <p class="admin__footer-name"><?= htmlspecialchars($userName) ?></p>
            <p class="admin__footer-email"><?= htmlspecialchars($userEmail) ?></p>
        </div>
    </aside>

    <div class="admin__overlay" id="adminOverlay"></div>

    <!-- =================== MAIN =================== -->
    <div class="admin__main">
        <header class="admin__topbar">
            <button class="admin__hamburger" id="adminHamburger" aria-label="Buka menu">
                <span class="material-symbols-rounded">menu</span>
            </button>
            <h1 class="admin__topbar-title"><?= htmlspecialchars($admin_title) ?></h1>
        </header>

        <main class="admin__content">
