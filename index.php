<?php require_once __DIR__ . '/config/database.php'; ?>
<?php include __DIR__ . '/layouts/header.php'; ?>

<!-- HERO -->
<section class="hero" id="beranda">
<div class="hero__bg-decor" aria-hidden="true"><span class="hero__blob hero__blob--1"></span><span class="hero__blob hero__blob--2"></span></div>
<div class="container hero__inner">
<div class="hero__content">
<span class="badge reveal"><span class="badge__dot"></span>Program Kursus Bahasa Arab</span>
<h1 class="hero__title reveal" style="transition-delay:.1s">Ingin Paham <span class="text-accent">Dasar Agama?</span>..<br>Bahasa Arab <span class="text-primary">Kuncinya!</span></h1>
<p class="hero__desc reveal" style="transition-delay:.2s">Al-Mutqon hadir untuk menghadirkan pembelajaran bahasa Arab yang terstruktur, interaktif, dan aplikatif—didukung teknologi untuk menjadikan proses belajar dan murojaah lebih efektif, menyenangkan, serta relevan bagi kebutuhan sehari-hari maupun akademis.</p>
<div class="hero__actions reveal" style="transition-delay:.3s">
<button class="btn btn-primary" id="heroRegisterBtn">Daftar Sekarang<span class="material-symbols-rounded btn__icon">arrow_forward</span></button>
<a href="#program" class="btn btn-outline">Kenali Program Kami</a>
</div>

</div>
<div class="hero__visual reveal-scale" style="transition-delay:.2s">
<div class="hero__image-wrap">
<div class="hero__image"><img src="assets/2.png" alt="Suasana belajar bahasa Arab" loading="eager"></div>
<div class="hero__floating hero__floating--stat">
<div class="hero__floating-icon"><span class="material-symbols-rounded">groups</span></div>
<div><strong>905+</strong><span>Bergabung Bersama Kami</span></div>
</div>
<div class="hero__floating hero__floating--badge"><span class="material-symbols-rounded">volunteer_activism</span><span>Dukung Kami</span></div>
</div>
</div>
</div>
</section>

<!-- ABOUT -->
<section class="about section" id="tentang">
<div class="container about__inner">
<div class="about__visual reveal-left">
<div class="about__image"><img src="https://images.unsplash.com/photo-1623314556929-69d34cb19010?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0" alt="Suasana kegiatan belajar Almutqon" loading="lazy"></div>
<div class="about__badge"><span class="material-symbols-rounded">verified</span><div><strong>10 Tahun</strong><span>Berkontribusi</span></div></div>
</div>
<div class="about__content reveal-right">
<span class="section-label">Tentang Al-Mutqon</span>
<h2 class="section-title">Membuka Jalan Menuju Kemahiran Bahasa Arab</h2>
<p class="about__text">Al-Mutqon adalah layanan kursus privat dan non-privat yang berfokus pada pembelajaran bahasa Arab yang hadir untuk memberikan pengalaman belajar yang terstruktur, interaktif, dan aplikatif bagi siapa saja yang ingin mengembangkan kemampuan bahasa Arabnya.</p>
<p class="about__text">Melalui kurikulum bertahap, modul pembelajaran, latihan interaktif, murojaah berbasis teknologi, serta program pembelajaran yang fleksibel, Almutqon membantu peserta berkembang dari tingkat pemula hingga mahir.</p>
<ul class="about__features">
<li><span class="material-symbols-rounded">check_circle</span>Kurikulum terstruktur dari tingkat pemula hingga mahir</li>
<li><span class="material-symbols-rounded">check_circle</span>Pembelajaran interaktif dan didukung teknologi</li>
<li><span class="material-symbols-rounded">check_circle</span>Kemampuan bahasa Arab yang aplikatif dan berkelanjutan</li>
</ul>
<a href="/pages/tentang/index.php" class="link-arrow">Selengkapnya<span class="material-symbols-rounded">arrow_forward</span></a>
</div>
</div>
</section>

<!-- STATISTICS -->
<section class="stats" id="sdm">
<div class="container">
<div class="stats__card reveal">
<div class="stats__item"><div class="stats__icon"><span class="material-symbols-rounded">groups</span></div><div class="stats__data"><span class="stats__num" data-target="1250" data-suffix="+">0</span><span class="stats__label">Alumni Aktif</span></div></div>
<div class="stats__divider"></div>
<div class="stats__item"><div class="stats__icon"><span class="material-symbols-rounded">school</span></div><div class="stats__data"><span class="stats__num" data-target="45">0</span><span class="stats__label">Pengajar Berpengalaman</span></div></div>
<div class="stats__divider"></div>
<div class="stats__item"><div class="stats__icon"><span class="material-symbols-rounded">workspace_premium</span></div><div class="stats__data"><span class="stats__num" data-target="98" data-suffix="%">0</span><span class="stats__label">Tingkat Kelulusan</span></div></div>
<div class="stats__divider"></div>
<div class="stats__item"><div class="stats__icon"><span class="material-symbols-rounded">public</span></div><div class="stats__data"><span class="stats__num" data-target="15">0</span><span class="stats__label">Negara Tersebar</span></div></div>
</div>
</div>
</section>

<!-- =================== PROGRAMS =================== -->
<section class="programs section section--surface" id="program">
    <div class="container">
        <div class="section__head reveal">
            <span class="section-label">Program Unggulan</span>
            <h2 class="section-title">Program Al-Mutqon</h2>
            <p class="section-subtitle">Pilih jalur belajar yang sesuai dengan kebutuhan dan target Anda bersama Al-Mutqon Arabic Learning.</p>
        </div>
        
        <div class="programs__carousel reveal">
            <div class="programs__viewport">
                <div class="programs__track" id="programsTrack">
                    <?php 
                    // Data Program (Mirip State/Props di React)
                    $programs = [
                        ['icon' => 'school', 'title' => 'Al-Mutqon Offline', 'desc' => 'Belajar langsung dengan pengalaman pembelajaran yang lebih interaktif.', 'link' => '/pages/program/offline.php', 'btn_text' => 'Konsultasi Kelas Offline'],
                        ['icon' => 'videocam', 'title' => 'Al-Mutqon Online', 'desc' => 'Belajar dari mana saja secara interaktif dan fleksibel.', 'link' => '/pages/program/online.php', 'btn_text' => 'Konsultasi Kelas Online'],
                        ['icon' => 'groups', 'title' => 'Dauroh Umum', 'desc' => 'Program intensif dengan tema dan periode tertentu.', 'link' => '/pages/program/dauroh.php', 'btn_text' => 'Ajukan Dauroh']
                    ];
                    foreach ($programs as $p) {
                        include __DIR__ . '/components/card-program.php';
                    }
                    ?>
                </div>
            </div>
            <div class="programs__nav">
                <button class="stories__btn programs__prev" id="programsPrev" aria-label="Program sebelumnya"><span class="material-symbols-rounded">arrow_back</span></button>
                <div class="stories__dots" id="programsDots"></div>
                <button class="stories__btn programs__next" id="programsNext" aria-label="Program berikutnya"><span class="material-symbols-rounded">arrow_forward</span></button>
            </div>
        </div>
    </div>
</section>

<?php
$kurikulum_items = [
    [
        'title' => "Mubtadi'",
        'desc' => "Penguasaan mufrodat, pelafalan, dan ungkapan dasar sehari-hari. Cocok untuk pemula yang ingin membangun fondasi bahasa Arab yang kuat.",
        'icon' => "school",
        'color' => "#10B981",
        'delay' => "0"
    ],
    [
        'title' => "Mutawassith",
        'desc' => "Perluasan ungkapan harian, pengenalan kaidah nahwu awal, dan pembiasaan pola kalimat. Untuk yang sudah menguasai dasar-dasar.",
        'icon' => "groups",
        'color' => "#F59E0B",
        'delay' => "0.1"
    ],
    [
        'title' => "Mahir",
        'desc' => "Membaca tanpa harakat dengan strategi parsing, kecakapan lisan lancar, dan kaidah mandiri. Tingkat lanjut untuk kemandirian berbahasa.",
        'icon' => "trending_up",
        'color' => "#3B82F6",
        'delay' => "0.2"
    ],
    [
        'title' => "Mutafawwiq",
        'desc' => "Teori nahwu lanjutan, analisis teks kompleks, dan penggunaan bahasa untuk akademik/profesional. Tingkat ahli.",
        'icon' => "workspace_premium",
        'color' => "#8B5CF6",
        'delay' => "0.3"
    ]
];

// Include component kurikulum
include __DIR__ . '/components/card-kurikulum.php';
?>
<!-- Modal (login/register, logout, donasi, offline/online/dauroh) dimuat SEKALI
lewat layouts/footer.php -> include layouts/modals.php -->

<!-- =================== TESTIMONI =================== -->
<section class="stories section" id="testimoni">
    <div class="container">
        <div class="section__head reveal">
            <span class="section-label">KATA MEREKA</span>
            <h2 class="section-title">Apa Kata Mereka Tentang Al-Mutqon?</h2>
        </div>
        <div class="stories__carousel reveal">
            <div class="stories__viewport">
                <div class="stories__track" id="storiesTrack">
                    <?php 
                    $testimonials = [
                        ['quote' => '"Awalnya saya cukup kesulitan memahami bahasa Arab karena belum punya dasar yang kuat. Setelah mengikuti pembelajaran privat di Almutqon, materi dijelaskan secara bertahap dan disesuaikan dengan kemampuan saya."', 'name' => 'Jasmine Puspa Medina', 'role' => 'Alumni Program Privat Mubtadi\'', 'img' => 'https://images.unsplash.com/vector-1742875355318-00d715aec3e8?q=80&w=1760&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'],
                        ['quote' => '"Yang saya suka dari kelas privat Almutqon adalah pembelajarannya benar-benar menyesuaikan kebutuhan saya. Saya bisa bertanya ketika belum paham dan mendapatkan penjelasan sampai benar-benar mengerti."', 'name' => 'Fajar Nur Fadhilah', 'role' => 'Alumni Program Privat Mahir', 'img' => 'https://images.unsplash.com/vector-1742875355318-00d715aec3e8?q=80&w=1760&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'],
                        ['quote' => '"Saya mengikuti program Almutqon karena ingin belajar bahasa Arab secara lebih terstruktur. Materinya mudah diikuti, ada latihan dan murojaah, sehingga saya tidak hanya menerima materi tetapi juga mengulang dan mempraktikkannya."', 'name' => 'Rizki Abdullah', 'role' => 'Peserta Program Non-Privat Mubtadi\'', 'img' => 'https://images.unsplash.com/vector-1742875355318-00d715aec3e8?q=80&w=1760&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'],
                        ['quote' => '"Sebelumnya saya sering bingung harus mulai belajar bahasa Arab dari mana. Di Almutqon, saya bisa belajar secara bertahap melalui modul dan quiz yang tersedia. Materinya membantu saya memahami dasar-dasar bahasa Arab dengan lebih terarah."', 'name' => 'Fatimah', 'role' => 'Pelanggan Akun Premium Al-Mutqon', 'img' => 'https://images.unsplash.com/vector-1742875355318-00d715aec3e8?q=80&w=1760&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D']
                    ];
                    foreach ($testimonials as $t) {
                        include __DIR__ . '/components/card-testimoni.php';
                    }
                    ?>
                </div>
            </div>
            <div class="stories__nav">
                <button class="stories__btn stories__prev" id="storiesPrev" aria-label="Cerita sebelumnya"><span class="material-symbols-rounded">arrow_back</span></button>
                <div class="stories__dots" id="storiesDots"></div>
                <button class="stories__btn stories__next" id="storiesNext" aria-label="Cerita berikutnya"><span class="material-symbols-rounded">arrow_forward</span></button>
            </div>
        </div>
    </div>
</section>

<!-- IMPACT CTA -->
<section class="impact-cta" id="donasi">
    <div class="impact-cta__decor" aria-hidden="true">
        <span class="impact-cta__circle impact-cta__circle--1"></span>
        <span class="impact-cta__circle impact-cta__circle--2"></span>
    </div>
    <div class="container impact-cta__inner reveal">
        <span class="section-label section-label--white">Bergabung Bersama Kami</span>
        <h2 class="impact-cta__title">Mari Dukung Program Subsidi Pendidikan Kami</h2>
        <p class="impact-cta__text">Setiap kontribusi membuka satu kesempatan baru untuk belajar, tumbuh, dan meraih masa depan.</p>
        <button class="btn btn-white" data-donate>
            Salurkan Kontribusi
            <span class="material-symbols-rounded btn__icon">favorite</span>
        </button>
    </div>
</section>

<!-- ALFARAHIDI FLOATING WIDGET -->
<div class="alfarahidi-widget" id="alfarahidiWidget">
    <div class="alfarahidi-panel">
        <div class="alfarahidi-panel__header">
            <div class="alfarahidi-head-left" id="alfarahidiTags"></div>
            <button class="alfarahidi-close" id="alfarahidiClose" aria-label="Tutup">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="alfarahidi-progress-bar">
            <span id="alfarahidiProgressText">1 / 15</span>
        </div>
        <div class="alfarahidi-panel__body" id="alfarahidiContent"></div>
    </div>
    <button class="alfarahidi-fab" id="alfarahidiFab" aria-label="Buka Alfarahidi">
        <span class="material-symbols-rounded">menu_book</span>
    </button>
</div>

<!-- ARTICLES -->
<section class="articles section section--surface" id="artikel">
    <div class="container">
        <div class="section__head reveal">
            <span class="section-label">Artikel & Kabar</span>
            <h2 class="section-title">Ikuti Perjalanan dan Cerita Kami</h2>
        </div>
        <div class="articles__carousel reveal">
            <div class="articles__viewport">
                <div class="articles__track" id="articlesTrack">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM articles ORDER BY tanggal DESC, id DESC LIMIT 3");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $fallback_img = 'https://images.unsplash.com/photo-1606326608606-aa0b62935f2b?auto=format&fit=crop&w=600&q=80';
                    foreach ($rows as $i => $row) {
                        $a = [
                            'delay'    => ($i * 0.1) . 's',
                            'link'     => '/pages/artikel/detail.php?id=' . (int) $row['id'],
                            'img'      => $row['gambar'] ?: $fallback_img,
                            'category' => $row['kategori'] ?: 'Umum',
                            'date'     => date('j M Y', strtotime($row['tanggal'])),
                            'title'    => $row['judul'],
                            'excerpt'  => mb_strimwidth(strip_tags($row['konten']), 0, 120, '...'),
                        ];
                        include __DIR__ . '/components/card-artikel.php';
                    }
                    ?>
                </div>
            </div>
            <!-- Navigasi kiri/kanan (class sama dengan carousel testimoni) -->
            <div class="stories__nav">
                <button class="stories__btn" id="articlesPrev" aria-label="Artikel sebelumnya"><span class="material-symbols-rounded">arrow_back</span></button>
                <div class="stories__dots" id="articlesDots"></div>
                <button class="stories__btn" id="articlesNext" aria-label="Artikel berikutnya"><span class="material-symbols-rounded">arrow_forward</span></button>
            </div>
        </div>
        <div style="text-align: center; margin-top: 44px;">
            <a href="/pages/artikel/index.php" class="btn btn-outline">
                Lihat Semua Artikel
                <span class="material-symbols-rounded btn__icon">arrow_forward</span>
            </a>
        </div>
    </div>
</section>

<!-- PARTNERS -->
<section class="partners section">
    <div class="container">
        <div class="section__head reveal">
            <h2 class="section-title section-title--center">Kolaborasi untuk Kebaikan yang Lebih Besar</h2>
            <p class="section-subtitle section-subtitle--center">Bersama mitra terpercaya, kami memperluas dampak pendidikan ke lebih banyak komunitas.</p>
        </div>
        <div class="partners__grid reveal">
            <div class="partner-logo">PARTNER 01</div>
            <div class="partner-logo">PARTNER 02</div>
            <div class="partner-logo">PARTNER 03</div>
            <div class="partner-logo">PARTNER 04</div>
            <div class="partner-logo">PARTNER 05</div>
            <div class="partner-logo">PARTNER 06</div>
        </div>
    </div>
</section>

<!-- NEWSLETTER -->
<section class="newsletter" id="kontak">
    <div class="container">
        <div class="newsletter__card reveal-scale">
            <div class="newsletter__decor" aria-hidden="true">
                <span class="material-symbols-rounded newsletter__decor-icon">mail</span>
            </div>
            <h2 class="newsletter__title">Dapatkan Inspirasi Mingguan</h2>
            <p class="newsletter__text">Ikuti kabar terbaru tentang program, kegiatan, dan cerita inspiratif dari Almutqon.</p>
            <form class="newsletter__form" id="newsletterForm" novalidate>
                <div class="newsletter__input-group">
                    <label for="newsletterEmail" class="sr-only">Alamat email Anda</label>
                    <span class="material-symbols-rounded newsletter__input-icon">mail</span>
                    <input type="email" id="newsletterEmail" placeholder="Alamat email Anda" required autocomplete="email">
                    <button type="submit" class="btn btn-primary">Berlangganan
                        <span class="material-symbols-rounded btn__icon">send</span>
                    </button>
                </div>
                <p class="newsletter__message" id="newsletterMessage" role="alert"></p>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/layouts/footer.php'; ?>