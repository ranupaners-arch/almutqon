<?php
// pages/info/faq.php — pertanyaan yang sering ditanyakan seputar program & pembayaran.
require_once __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../layouts/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="section-label">Bantuan</span>
        <h1 class="page-hero__title">Pertanyaan Umum (FAQ)</h1>
        <p class="page-hero__desc">Jawaban atas hal yang paling sering ditanyakan seputar program dan pembayaran.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width: 760px;">
        <?php
        $faq = [
            ['Bagaimana cara mendaftar program?', 'Pilih program (Offline / Online / Dauroh) di halaman utama, klik Daftar, isi formulir, lalu pilih metode pembayaran. Anda otomatis diarahkan ke halaman status pembayaran.'],
            ['Berapa lama batas waktu pembayaran?', '24 jam sejak pengajuan dibuat. Halaman status menampilkan hitungan mundur. Simpan link halaman status untuk menyalin nomor rekening kapan saja.'],
            ['Bagaimana jika batas waktu habis?', 'Pengajuan dapat dibatalkan tim kami. Hubungi admin via WhatsApp untuk perpanjangan atau pengajuan ulang.'],
            ['Kapan invoice dan struk diterbitkan?', 'Setelah admin mengonfirmasi pembayaran (LUNAS). Invoice dan struk PDF dikirim ke email Anda dan bisa diunduh dari halaman status.'],
            ['Di mana saya mengunduh materi dan mengunggah tugas?', 'Masuk ke akun Anda, buka menu Mulai Belajar (khusus peserta yang sudah lunas): Materi Kursus, Unggah Tugas, dan Riwayat Tugas Saya.'],
            ['Bagaimana cara menghubungi admin?', 'Melalui WhatsApp +62 822-5844-2647 atau tombol "Konfirmasi ke Admin" di halaman status pembayaran.'],
        ];
        foreach ($faq as [$tanya, $jawab]):
        ?>
            <details style="background: var(--surface, #fff); border: 1px solid var(--border, #e2e8f0); border-radius: 12px; padding: 16px 20px; margin-bottom: 12px;">
                <summary style="font-weight: 700; cursor: pointer; font-size: 15.5px; color: var(--text-dark, #0F172A);"><?= htmlspecialchars($tanya) ?></summary>
                <p style="margin: 10px 0 0; font-size: 14.5px; line-height: 1.7; color: var(--text-body, #475569);"><?= htmlspecialchars($jawab) ?></p>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
