<?php
// Beritahu header bahwa kita sedang di halaman 'program'
$currentPage = 'program'; 

require_once __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../layouts/header.php'; 
?>

<!-- SPACER -->
<div style="height: 100px;"></div>

<!-- =================== PAGE HERO =================== -->
<section class="page-hero" style="padding-top: 40px; padding-bottom: 60px;">
    <div class="container">
        <span class="section-label">Program Unggulan</span>
        <h1 class="page-hero__title">Al-Mutqon Online</h1>
        <p class="page-hero__desc" style="max-width: 800px;">Belajar bahasa Arab dari mana saja, kapan saja. Fleksibel, interaktif, dan tetap terarah meski secara daring.</p>
    </div>
</section>

<!-- =================== KENAPA ONLINE? =================== -->
<section class="section">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Keunggulan</span>
            <h2 class="section-title">Mengapa Belajar Online?</h2>
            <p class="section-subtitle" style="margin: 0 auto;">Solusi belajar tanpa batas geografis, cocok untuk Anda yang memiliki kesibukan tinggi.</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><span class="material-symbols-rounded">laptop_mac</span></div>
                <h4>Fleksibilitas Penuh</h4>
                <p>Belajar dari rumah, kantor, atau mana pun. Anda hanya membutuhkan perangkat dan koneksi internet yang stabil.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .1s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">schedule</span></div>
                <h4>Jadwal Fleksibel</h4>
                <p>Sesuaikan waktu belajar dengan kesibukan Anda. Tersedia sesi pagi, siang, hingga malam hari.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .2s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">cloud_download</span></div>
                <h4>Akses Materi Digital</h4>
                <p>Semua modul, rekaman kelas, dan latihan interaktif dapat diakses kapan saja melalui dashboard pribadi.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .3s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">videocam</span></div>
                <h4>Rekaman Pembelajaran</h4>
                <p>Setiap sesi live direkam, sehingga Anda bisa mengulang materi yang terlewat atau butuh pemahaman lebih.</p>
            </div>
        </div>
    </div>
</section>

<!-- =================== PILIHAN FORMAT =================== -->
<section class="section section--surface">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Format Kelas</span>
            <h2 class="section-title">Pilih Format yang Sesuai</h2>
        </div>

        <div class="feature-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><span class="material-symbols-rounded">videocam</span></div>
                <h4>Live Class (Zoom/Meet)</h4>
                <p>Kelas tatap muka virtual secara real-time. Interaksi dua arah langsung dengan pengajar layaknya kelas offline.</p>
                <ul class="curriculum-list" style="margin-top: 16px;">
                    <li><span class="material-symbols-rounded">check_circle</span> Durasi 60-90 menit per sesi</li>
                    <li><span class="material-symbols-rounded">check_circle</span> Tanya jawab langsung</li>
                    <li><span class="material-symbols-rounded">check_circle</span> Rekaman otomatis tersedia</li>
                </ul>
            </div>
            <div class="feature-card reveal" style="transition-delay: .1s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">self_improvement</span></div>
                <h4>Self-Paced (Mandiri)</h4>
                <p>Akses modul video, kuis, dan latihan murojaah secara mandiri. Cocok untuk belajar dengan kecepatan sendiri.</p>
                <ul class="curriculum-list" style="margin-top: 16px;">
                    <li><span class="material-symbols-rounded">check_circle</span> Akses materi 24/7</li>
                    <li><span class="material-symbols-rounded">check_circle</span> Progress tracking pribadi</li>
                    <li><span class="material-symbols-rounded">check_circle</span> Sertifikat setelah lulus ujian</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- =================== CTA / KONSULTASI =================== -->
<?php $program = 'online'; include __DIR__ . '/../../layouts/price-section.php'; ?>

<?php 
// Modal dimuat sekali via layouts/footer.php
include __DIR__ . '/../../layouts/footer.php'; 
?>