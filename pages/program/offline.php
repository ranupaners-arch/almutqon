<?php
// Beritahu header bahwa kita sedang di halaman 'program'
$currentPage = 'program'; 

require_once __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../layouts/header.php'; 
?>

<!-- SPACER (Sesuai preferensimu sebelumnya) -->
<div style="height: 100px;"></div>

<!-- =================== PAGE HERO =================== -->
<section class="page-hero" style="padding-top: 40px; padding-bottom: 60px;">
    <div class="container">
        <span class="section-label">Program Unggulan</span>
        <h1 class="page-hero__title">Al-Mutqon Offline</h1>
        <p class="page-hero__desc" style="max-width: 800px;">Belajar bahasa Arab secara tatap muka dengan pengalaman pembelajaran yang lebih interaktif, intensif, dan personal.</p>
    </div>
</section>

<!-- =================== KENAPA OFFLINE? =================== -->
<section class="section">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Keunggulan</span>
            <h2 class="section-title">Mengapa Belajar Offline?</h2>
            <p class="section-subtitle" style="margin: 0 auto;">Interaksi langsung menciptakan suasana belajar yang lebih hidup dan efektif.</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><span class="material-symbols-rounded">groups</span></div>
                <h4>Interaksi Langsung</h4>
                <p>Diskusi dua arah dengan pengajar memungkinkan koreksi pelafalan (makhraj) dan tata bahasa secara real-time.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .1s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">home</span></div>
                <h4>Fleksibilitas Lokasi</h4>
                <p>Tersedia opsi kelas di kampus kami atau Home Visit (pengajar datang ke tempat Anda) untuk kenyamanan maksimal.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .2s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">schedule</span></div>
                <h4>Jadwal Terstruktur</h4>
                <p>Pembelajaran terjadwal dengan rutin membantu membangun disiplin dan kebiasaan belajar yang konsisten.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .3s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">emoji_events</span></div>
                <h4>Sertifikat Kompetensi</h4>
                <p>Mendapatkan sertifikat resmi dari Al-Mutqon setelah menyelesaikan tingkat kemahiran tertentu.</p>
            </div>
        </div>
    </div>
</section>

<!-- =================== PILIHAN KELAS =================== -->
<section class="section section--surface">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Format Kelas</span>
            <h2 class="section-title">Pilih Format yang Sesuai</h2>
        </div>

        <div class="feature-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><span class="material-symbols-rounded">person</span></div>
                <h4>Kelas Privat</h4>
                <p>Belajar satu-on-satu dengan pengajar. Materi disesuaikan 100% dengan kecepatan dan tujuan belajar Anda.</p>
                <ul class="curriculum-list" style="margin-top: 16px;">
                    <li><span class="material-symbols-rounded">check_circle</span> Durasi 90 menit per sesi</li>
                    <li><span class="material-symbols-rounded">check_circle</span> Bebas tentukan jadwal</li>
                    <li><span class="material-symbols-rounded">check_circle</span> Fokus pada kelemahan spesifik</li>
                </ul>
            </div>
            <div class="feature-card reveal" style="transition-delay: .1s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">group</span></div>
                <h4>Kelas Non-Privat (Reguler)</h4>
                <p>Belajar dalam kelompok kecil (3-5 orang). Cocok untuk membangun semangat kompetisi dan diskusi.</p>
                <ul class="curriculum-list" style="margin-top: 16px;">
                    <li><span class="material-symbols-rounded">check_circle</span> Biaya lebih terjangkau</li>
                    <li><span class="material-symbols-rounded">check_circle</span> Interaksi dengan sesama peserta</li>
                    <li><span class="material-symbols-rounded">check_circle</span> Kurikulum standar terstruktur</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- =================== CTA / KONSULTASI =================== -->
<?php $program = 'offline'; include __DIR__ . '/../../layouts/price-section.php'; ?>

<?php 
// Modal dimuat sekali via layouts/footer.php
include __DIR__ . '/../../layouts/footer.php'; 
?>