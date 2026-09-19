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
        <h1 class="page-hero__title">Dauroh Umum</h1>
        <p class="page-hero__desc" style="max-width: 800px;">Program pembelajaran intensif dengan tema spesifik dan periode tertentu. Cocok untuk pendalaman materi dalam waktu singkat.</p>
    </div>
</section>

<!-- =================== KENAPA DAUROH? =================== -->
<section class="section">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Keunggulan</span>
            <h2 class="section-title">Mengapa Mengikuti Dauroh?</h2>
            <p class="section-subtitle" style="margin: 0 auto;">Fokus, intensif, dan mendalam. Cara tercepat untuk menguasai satu topik bahasa Arab.</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><span class="material-symbols-rounded">topic</span></div>
                <h4>Tema Spesifik</h4>
                <p>Setiap Dauroh memiliki tema khusus (misal: Bahasa Arab untuk Wisatawan, Persiapan Umroh, atau Nahwu Dasar) yang dipelajari secara tuntas.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .1s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">timer</span></div>
                <h4>Waktu Intensif</h4>
                <p>Dilaksanakan dalam durasi singkat (1-3 hari) dengan jam belajar yang padat, ideal untuk liburan akhir pekan atau libur semester.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .2s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">diversity_3</span></div>
                <h4>Komunitas Belajar</h4>
                <p>Bertemu dengan peserta lain yang memiliki minat sama. Membangun jaringan (networking) dan semangat belajar bersama.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .3s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">workspace_premium</span></div>
                <h4>Sertifikat Keikutsertaan</h4>
                <p>Mendapatkan sertifikat resmi dari Al-Mutqon sebagai bukti telah menyelesaikan program Dauroh dengan baik.</p>
            </div>
        </div>
    </div>
</section>

<!-- =================== JENIS DAUROH =================== -->
<section class="section section--surface">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Pilihan Tema</span>
            <h2 class="section-title">Jenis Dauroh yang Tersedia</h2>
        </div>

        <div class="feature-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><span class="material-symbols-rounded">weekend</span></div>
                <h4>Dauroh Akhir Pekan</h4>
                <p>Program 2 hari (Sabtu-Minggu) dengan materi dasar yang aplikatif. Cocok untuk pekerja dan mahasiswa.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .1s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">beach_access</span></div>
                <h4>Dauroh Liburan</h4>
                <p>Program intensif 3-5 hari selama libur sekolah/kuliah. Dilengkapi dengan sesi outing dan praktik langsung.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay: .2s;">
                <div class="feature-card__icon"><span class="material-symbols-rounded">groups</span></div>
                <h4>Dauroh Komunitas</h4>
                <p>Program khusus yang bisa di-request oleh masjid, kampus, atau organisasi dengan tema dan jadwal yang disesuaikan.</p>
            </div>
        </div>
    </div>
</section>

<!-- =================== CTA / PENGAJUAN =================== -->
<?php $program = 'dauroh'; include __DIR__ . '/../../layouts/price-section.php'; ?>

<?php 
// Modal dimuat sekali via layouts/footer.php
include __DIR__ . '/../../layouts/footer.php'; 
?>