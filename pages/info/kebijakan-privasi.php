<?php
// pages/info/kebijakan-privasi.php — kebijakan privasi data pengguna ALMUTQON.
require_once __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../layouts/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="section-label">Legal</span>
        <h1 class="page-hero__title">Kebijakan Privasi</h1>
        <p class="page-hero__desc">Terakhir diperbarui: September 2026.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width: 760px; font-size: 15px; line-height: 1.8; color: var(--text-body, #475569);">
        <h2 class="section-title" style="font-size: 20px; text-align: left;">1. Data yang Kami Kumpulkan</h2>
        <p>Akun (nama, email, foto profil), data pendaftaran program (kontak WhatsApp, usia, instansi, catatan), riwayat pembayaran dan invoice, materi yang diunduh, serta file tugas yang Anda unggah beserta penilaiannya.</p>
        <h2 class="section-title" style="font-size: 20px; text-align: left;">2. Penggunaan Data</h2>
        <p>Data digunakan untuk administrasi pendaftaran, verifikasi pembayaran, kegiatan belajar-mengajar, penilaian tugas, dan notifikasi (email/WhatsApp) terkait layanan. Data tidak dijual atau dibagikan ke pihak ketiga untuk pemasaran.</p>
        <h2 class="section-title" style="font-size: 20px; text-align: left;">3. Keamanan</h2>
        <p>Password disimpan terenkripsi (hash), akses halaman sensitif dibatasi per peran (peserta, pengajar, direktur, admin), dan unduhan file melalui tautan berizin. Meski demikian, tidak ada sistem yang 100% aman dari gangguan.</p>
        <h2 class="section-title" style="font-size: 20px; text-align: left;">4. Hak Anda</h2>
        <p>Anda dapat memperbarui profil, meminta salinan data, atau meminta penghapusan akun dengan menghubungi admin di admin.almutqon@gmail.com atau WhatsApp +62 822-5844-2647.</p>
    </div>
</section>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
