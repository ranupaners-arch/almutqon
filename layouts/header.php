<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$currentUser = $_SESSION['user'] ?? null;
$_theme = 'light';
if ($currentUser && isset($currentUser['id'])) {
    $stmtTheme = $pdo->prepare('SELECT theme FROM users WHERE id = ?');
    $stmtTheme->execute([(int) $currentUser['id']]);
    $_t = (string) ($stmtTheme->fetchColumn() ?: 'light');
    $_theme = in_array($_t, ['light', 'dark', 'system'], true) ? $_t : 'light';
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($_theme) ?>">
<head>
    <script>try{if(document.documentElement.getAttribute('data-theme')==='system'){document.documentElement.setAttribute('data-theme',matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');}}catch(e){}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Mutqon Arabic Learning — Menyalakan Cahaya Ilmu, Membangun Masa Depan</title>
    <meta name="description" content="Al-Mutqon hadir untuk membuka akses pendidikan, memperluas kesempatan belajar, dan membantu generasi muda tumbuh dengan ilmu, karakter, dan masa depan yang lebih baik.">
    <meta name="author" content="Al-Mutqon Arabic Learning">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Al-Mutqon Arabic Learning — Menyalakan Cahaya Ilmu, Membangun Masa Depan">
    <meta property="og:description" content="Membuka akses pendidikan, memperluas kesempatan belajar, dan membantu generasi muda tumbuh dengan ilmu, karakter, dan masa depan yang lebih baik.">
    
    <!-- Favicon & Fonts -->
    <link rel="icon" type="image/png" href="/assets/favicon.png?v=20260919a">
    <link rel="shortcut icon" href="/assets/favicon.ico?v=20260919a">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    
    <!-- Styles (refactored: base → layout → components → pages → theme) -->
    <link rel="stylesheet" href="/css/base.css?v=20260918a">
    <link rel="stylesheet" href="/css/layout.css?v=20260918a">
    <link rel="stylesheet" href="/css/components.css?v=20260918a">
    <link rel="stylesheet" href="/css/pages.css?v=20260918a">
    <link rel="stylesheet" href="/css/theme.css?v=20260918a">
</head>
<body>

<!-- =================== NAVBAR =================== -->

<header class="navbar" id="navbar">
    <div class="container navbar__inner">
        <a href="#beranda" class="logo" aria-label="Al-Mutqon Foundation - Beranda">
            <img src="/assets/1.png" alt="Logo Almutqon" class="logo__img">
            <span class="logo__text">
                <span class="logo__name">AL MUTQON</span>
                <span class="logo__sub">ARABIC LEARNING</span>
            </span>
        </a>
        <nav class="nav" id="nav" aria-label="Navigasi utama">
            <ul class="nav__list">
                <?php 
                    $currentUri = $_SERVER['REQUEST_URI'];
                    $isHome = ($currentUri === '/' || $currentUri === '/index.php' || $currentUri === '/almutqon/' || $currentUri === '/almutqon/index.php');
                    $isArtikelPage = (strpos($currentUri, '/pages/artikel/') !== false); 
                    $isTentangPage = (strpos($currentUri, '/pages/tentang/') !== false);
                    $isProgramPage = (strpos($currentUri, '/pages/program/') !== false);
                    $isKurikulumPage = false;
                ?>
                <li><a href="/#beranda" class="nav__link">Beranda</a></li>
                <li><a href="/#tentang" class="nav__link <?= $isTentangPage ? 'active' : '' ?>">Tentang</a></li>
                <li><a href="/#program" class="nav__link <?= $isProgramPage ? 'active' : '' ?>">Program</a></li>
                <li><a href="/#kurikulum" class="nav__link <?= $isKurikulumPage ? 'active' : '' ?>">Kurikulum</a></li>
                <li><a href="/#testimoni" class="nav__link">Testimoni</a></li>
                <li><a href="/#donasi" class="nav__link">Donasi</a></li>
                <li><a href="/#artikel" class="nav__link <?= $isArtikelPage ? 'active' : '' ?>">Artikel</a></li>
                <li><a href="/#kontak" class="nav__link">Kontak</a></li>
            </ul>
        </nav>
        <div class="navbar__actions">
            <button class="btn btn-primary btn-sm" id="navLoginBtn">Daftar/Masuk</button>

            <!-- ACCOUNT DROPDOWN (tampil saat sudah login) -->
            <div class="nav-account" id="navAccount" hidden>
                <button class="nav-account__trigger" id="navAccountBtn" aria-haspopup="true" aria-expanded="false">
                    <span class="nav-account__avatar" id="navAvatar">A</span>
                    <span class="nav-account__name" id="navName"></span>
                    <span class="material-symbols-rounded nav-account__caret">expand_more</span>
                </button>
                <div class="nav-account__menu" id="navAccountMenu" aria-hidden="true">
                    <div class="nav-account__head">
                        <span class="nav-account__avatar nav-account__avatar--lg" id="navAvatarLg">A</span>
                        <div class="nav-account__email" id="navEmail"></div>
                    </div>
                    <a class="nav-account__item" href="/pages/akun/profil.php"><span class="material-symbols-rounded">person</span>Profil Saya</a>
                    <a class="nav-account__item" href="/pages/akun/pendaftaran-saya.php"><span class="material-symbols-rounded">receipt_long</span>Pendaftaran Saya</a>
                    <a class="nav-account__item" href="/pages/akun/materi.php"><span class="material-symbols-rounded">menu_book</span>Mulai Belajar</a>
                    <a class="nav-account__item" href="/pages/akun/ganti-password.php"><span class="material-symbols-rounded">key</span>Ganti Password</a>
                    <a class="nav-account__item" href="/pages/akun/pengaturan.php"><span class="material-symbols-rounded">settings</span>Pengaturan</a>
                    <hr class="nav-account__sep">
                    <button class="nav-account__item nav-account__item--danger" id="navLogoutBtn"><span class="material-symbols-rounded">logout</span>Keluar</button>
                </div>
            </div>

            <button class="hamburger" id="hamburger" aria-label="Buka menu" aria-expanded="false" aria-controls="mobileMenu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<!-- =================== MOBILE MENU =================== -->
<div class="mobile-menu__overlay" id="mobileOverlay"></div>
<aside class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <button class="mobile-menu__close" id="mobileClose" aria-label="Tutup menu">
        <span class="material-symbols-rounded">close</span>
    </button>
    <ul class="mobile-menu__list">
        <?php 
            $currentUri = $_SERVER['REQUEST_URI'];
            $isHome = ($currentUri === '/' || $currentUri === '/index.php' || $currentUri === '/almutqon/' || $currentUri === '/almutqon/index.php');
            $isArtikelPage = (strpos($currentUri, '/pages/artikel/') !== false); 
            $isTentangPage = (strpos($currentUri, '/pages/tentang/') !== false);
            $isProgramPage = (strpos($currentUri, '/pages/program/') !== false);
            $isKurikulumPage = false;
        ?>
        <li><a href="/#beranda" class="mobile-menu__link">Beranda</a></li>
        <li><a href="/#tentang" class="mobile-menu__link <?= $isTentangPage ? 'active' : '' ?>">Tentang</a></li>
        <li><a href="/#program" class="mobile-menu__link <?= $isProgramPage ? 'active' : '' ?>">Program</a></li>
        <li><a href="/#kurikulum" class="mobile-menu__link <?= $isKurikulumPage ? 'active' : '' ?>">Kurikulum</a></li>
        <li><a href="/#testimoni" class="mobile-menu__link">Testimoni</a></li>
        <li><a href="/#donasi" class="mobile-menu__link">Donasi</a></li>
        <li><a href="/#artikel" class="mobile-menu__link <?= $isArtikelPage ? 'active' : '' ?>">Artikel</a></li>
        <li><a href="/#kontak" class="mobile-menu__link">Kontak</a></li>
    </ul>
    <div class="mobile-menu__account" id="mobileAccount" hidden>
        <div class="mobile-menu__account-row">
            <span class="nav-account__avatar" id="mobileAvatar">A</span>
            <div class="mobile-menu__account-meta">
                <div class="mobile-menu__account-name" id="mobileAccountName"></div>
                <div class="mobile-menu__account-email" id="mobileAccountEmail"></div>
            </div>
        </div>
        <a class="btn btn-outline btn-block" href="/pages/akun/pendaftaran-saya.php" style="text-decoration: none; margin-bottom: 10px; text-align: center;">Pendaftaran Saya</a>
        <a class="btn btn-outline btn-block" href="/pages/akun/materi.php" style="text-decoration: none; margin-bottom: 10px; text-align: center;">Mulai Belajar</a>
        <button class="btn btn-outline btn-block" id="mobileLogoutBtn">Keluar</button>
    </div>
    <button class="btn btn-primary btn-block" id="mobileLoginBtn">Daftar/Masuk</button>
</aside>

<main>