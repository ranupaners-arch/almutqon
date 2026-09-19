<?php
// pages/info/syarat-ketentuan.php — syarat & ketentuan layanan ALMUTQON.
require_once __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../layouts/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="section-label">Legal</span>
        <h1 class="page-hero__title">Syarat &amp; Ketentuan</h1>
        <p class="page-hero__desc">Terakhir diperbarui: September 2026.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width: 760px; font-size: 15px; line-height: 1.8; color: var(--text-body, #475569);">
        <h2 class="section-title" style="font-size: 20px; text-align: left;">1. Pendaftaran &amp; Pembayaran</h2>
        <p>Pengajuan program wajib disertai data yang benar. Batas pembayaran 24 jam sejak pengajuan; melewati batas, pengajuan dapat dibatalkan dan perlu pengajuan ulang. Invoice dan struk resmi diterbitkan setelah pembayaran dikonfirmasi LUNAS oleh admin.</p>
        <h2 class="section-title" style="font-size: 20px; text-align: left;">2. Kegiatan Belajar</h2>
        <p>Peserta aktif wajib mengikuti jadwal yang ditetapkan, mengunduh materi dari dashboard resmi, dan mengumpulkan tugas tepat waktu dalam format yang ditentukan (PDF/DOC/DOCX, maks 5MB). Penilaian tugas menjadi wewenang pengajar.</p>
        <h2 class="section-title" style="font-size: 20px; text-align: left;">3. Pengembalian Dana</h2>
        <p>Biaya yang sudah dinyatakan LUNAS tidak dapat dikembalikan kecuali ada kesalahan sistem atau pembatalan resmi dari pihak lembaga. Pengajuan keberatan disampaikan ke admin maksimal 7 hari setelah pembayaran.</p>
        <h2 class="section-title" style="font-size: 20px; text-align: left;">4. Tata Tertib</h2>
        <p>Peserta menjaga adab dan ketertiban selama kegiatan. Pelanggaran berat (kecurangan, pelecehan, atau penyalahgunaan akun) dapat berujung pada pencabutan akses tanpa pengembalian dana.</p>
    </div>
</section>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
