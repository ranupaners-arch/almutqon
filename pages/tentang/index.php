<?php
// Beritahu header bahwa kita sedang di halaman 'tentang'
$currentPage = 'tentang'; 

require_once __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../layouts/header.php'; 
?>

<!-- =================== PAGE HERO =================== -->
<section class="page-hero">
    <div class="container">
        <span class="section-label">Tentang Kami</span>
        <h1 class="page-hero__title">Mengenal Lebih Dekat Al-Mutqon</h1>
        <p class="page-hero__desc">Lebih dari sekadar kursus bahasa, kami adalah mitra perjalanan Anda dalam membuka cakrawala ilmu dan membangun karakter.</p>
    </div>
</section>

<!-- =================== STORY SECTION =================== -->
<section class="section">
    <div class="container">
        <div class="about__inner">
            <div class="about__visual reveal-left">
                <div class="about__image">
                    <!-- Gunakan path absolut /assets/ -->
                    <img src="https://images.unsplash.com/photo-1622137879013-beaca5144a4b?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8a3Vyc3VzJTIwYmFoYXNhJTIwYXJhYnxlbnwwfHwwfHx8Mg%3D%3D" alt="Suasana belajar Al-Mutqon" loading="lazy">
                </div>
                <div class="about__badge">
                    <span class="material-symbols-rounded">verified</span>
                    <div>
                        <strong>10 Tahun</strong>
                        <span>Berkontribusi</span>
                    </div>
                </div>
            </div>
            <div class="about__content reveal-right">
                <span class="section-label">Cerita Kami</span>
                <h2 class="section-title">Dari Sebuah Mimpi Menjadi Gerakan Pendidikan</h2>
                <p class="about__text">
                    Al-Mutqon Arabic Learning lahir dari keprihatinan akan sulitnya akses pembelajaran bahasa Arab yang terstruktur, menyenangkan, dan relevan dengan kebutuhan zaman. Kami percaya bahwa bahasa Arab bukan hanya sekadar alat komunikasi, tetapi kunci untuk memahami peradaban, ilmu agama, dan wawasan global.
                </p>
                <p class="about__text">
                    Selama satu dekade terakhir, kami telah membantu ratusan peserta—mulai dari pelajar, mahasiswa, hingga profesional—untuk menguasai bahasa Arab dari nol hingga mahir. Dengan metode yang adaptif dan dukungan teknologi, kami berkomitmen untuk terus menyalakan cahaya ilmu bagi siapa saja yang ingin belajar.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- =================== VISI MISI =================== -->
<section class="section section--surface">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Arah & Tujuan</span>
            <h2 class="section-title">Visi & Misi Kami</h2>
            <p class="section-subtitle" style="margin: 0 auto;">Komitmen kami untuk memberikan dampak nyata melalui pendidikan bahasa Arab.</p>
        </div>

        <div class="visi-misi-grid">
            <!-- Visi -->
            <div class="visi-misi-card reveal">
                <div class="visi-misi-icon">
                    <span class="material-symbols-rounded">visibility</span>
                </div>
                <h3>Visi</h3>
                <p>Mencetak pembelajar yang mampu berkomunikasi dalam bahasa Arab secara fasih, memahami kaidahnya dengan baik, dan mampu mengajarkannya kepada orang lain</p>
            </div>

            <!-- Misi -->
            <div class="visi-misi-card reveal" style="transition-delay: .1s;">
                <div class="visi-misi-icon">
                    <span class="material-symbols-rounded">rocket_launch</span>
                </div>
                <h3>Misi</h3>
                <ul>
                    <li>
                        <span class="material-symbols-rounded">check_circle</span>
                        Menyediakan kurikulum yang terstruktur, aplikatif, dan sesuai dengan kebutuhan peserta.
                    </li>
                    <li>
                        <span class="material-symbols-rounded">check_circle</span>
                        Mengintegrasikan teknologi untuk memudahkan proses belajar dan murojaah.
                    </li>
                    <li>
                        <span class="material-symbols-rounded">check_circle</span>
                        Membangun komunitas belajar yang suportif dan saling menginspirasi.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- =================== VALUES / NILAI =================== -->
<section class="section">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Nilai Kami</span>
            <h2 class="section-title">Prinsip yang Kami Pegang</h2>
        </div>
        
        <div class="values-grid">
            <div class="value-card reveal">
                <div class="value-card__icon">
                    <span class="material-symbols-rounded">school</span>
                </div>
                <h4>Integritas Ilmu</h4>
                <p>Kami mengajarkan bahasa Arab dengan kaidah yang benar, terverifikasi, dan dapat dipertanggungjawabkan secara akademis.</p>
            </div>
            <div class="value-card reveal" style="transition-delay: .1s;">
                <div class="value-card__icon">
                    <span class="material-symbols-rounded">diversity_3</span>
                </div>
                <h4>Inklusivitas</h4>
                <p>Pintu belajar kami terbuka untuk semua kalangan, tanpa memandang latar belakang, usia, atau tingkat kemampuan awal.</p>
            </div>
            <div class="value-card reveal" style="transition-delay: .2s;">
                <div class="value-card__icon">
                    <span class="material-symbols-rounded">lightbulb</span>
                </div>
                <h4>Inovasi Berkelanjutan</h4>
                <p>Kami terus memperbarui metode dan teknologi pembelajaran agar tetap relevan dengan tantangan zaman.</p>
            </div>
        </div>
    </div>
</section>

<?php 
include __DIR__ . '/../../layouts/footer.php'; 
?>