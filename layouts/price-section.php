<?php
// layouts/price-section.php — bagian "Biaya Kursus" di halaman program
// Gunakan: <?php $program = 'offline'; include __DIR__ . '/../../layouts/price-section.php';
// Nama file dipanggil dari pages/program/*, jadi path relatif 2 tingkat naik.
require_once __DIR__ . '/../config/pembayaran.php';
$__priceCfg = biaya_kursus_config();

$__items = [];
$__ctaLabel = 'Daftar Sekarang';
$__ctaCall  = '';

if ($program === 'offline') {
    $__ctaLabel = 'Ajukan Konsultasi Offline';
    $__ctaCall  = 'app.openOfflineForm()';
    $__items = [
        [
            'icon' => 'person', 'judul' => 'Kelas Privat',
            'nominal' => rupiah(hitung_biaya_kursus('offline', 'Privat', 1)['biaya']),
            'satuan' => '/ sesi',
            'ket' => !empty($__priceCfg['offline']['Privat']['durasi_label']) ? htmlspecialchars((string) $__priceCfg['offline']['Privat']['durasi_label'], ENT_QUOTES, 'UTF-8') : 'Paket biaya per sesi',
            'poin' => ['One-on-one dengan pengajar', 'Materi 100% disesuaikan', 'Bebas tentukan jadwal'],
        ],
        [
            'icon' => 'group', 'judul' => 'Kelas Non-Privat',
            'nominal' => rupiah(hitung_biaya_kursus('offline', 'Non-Privat', 1)['biaya']),
            'satuan' => '/ sesi',
            'ket' => !empty($__priceCfg['offline']['Non-Privat']['durasi_label']) ? htmlspecialchars((string) $__priceCfg['offline']['Non-Privat']['durasi_label'], ENT_QUOTES, 'UTF-8') : 'Paket biaya per sesi',
            'poin' => ['Kelompok kecil (3-5 orang)', 'Biaya lebih terjangkau', 'Kurikulum standar'],
        ],
    ];
} elseif ($program === 'online') {
    $__ctaLabel = 'Ajukan Konsultasi Online';
    $__ctaCall  = 'app.openOnlineForm()';
    $__items = [
        [
            'icon' => 'person', 'judul' => 'Kelas Privat',
            'nominal' => rupiah(hitung_biaya_kursus('online', 'Privat', 1)['biaya']),
            'satuan' => '/ sesi',
            'ket' => !empty($__priceCfg['online']['Privat']['durasi_label']) ? htmlspecialchars((string) $__priceCfg['online']['Privat']['durasi_label'], ENT_QUOTES, 'UTF-8') : 'Paket biaya per sesi',
            'poin' => ['One-on-one via video call', 'Materi 100% disesuaikan', 'Bebas tentukan jadwal'],
        ],
        [
            'icon' => 'group', 'judul' => 'Kelas Non-Privat',
            'nominal' => rupiah(hitung_biaya_kursus('online', 'Non-Privat', 1)['biaya']),
            'satuan' => '/ sesi',
            'ket' => !empty($__priceCfg['online']['Non-Privat']['durasi_label']) ? htmlspecialchars((string) $__priceCfg['online']['Non-Privat']['durasi_label'], ENT_QUOTES, 'UTF-8') : 'Paket biaya per sesi',
            'poin' => ['Kelompok kecil via video call', 'Interaksi dengan sesama peserta', 'Kurikulum standar'],
        ],
    ];
} elseif ($program === 'dauroh') {
    $__ctaLabel = 'Ajukan Dauroh Sekarang';
    $__ctaCall  = 'app.openDaurohForm()';
    $__min = (int) biaya_kursus_config()['dauroh']['min_peserta'];
    $__items = [
        [
            'icon' => 'home_work', 'judul' => 'Dauroh Offline',
            'nominal' => rupiah((int) biaya_kursus_config()['dauroh']['per_orang']['Offline']),
            'satuan' => '/ orang',
            'ket' => 'Minimal ' . $__min . ' peserta · ' . htmlspecialchars((string) ($__priceCfg['dauroh']['Offline']['durasi_label'] ?? 'Durasi menyesuaikan'), ENT_QUOTES, 'UTF-8'),
            'poin' => ['Tatap muka tempat Anda/kami', 'Tema bisa di-request', 'Sesi 1-3 hari intensif'],
        ],
        [
            'icon' => 'videocam', 'judul' => 'Dauroh Online',
            'nominal' => rupiah((int) biaya_kursus_config()['dauroh']['per_orang']['Online']),
            'satuan' => '/ orang',
            'ket' => 'Minimal ' . $__min . ' peserta · ' . htmlspecialchars((string) ($__priceCfg['dauroh']['Online']['durasi_label'] ?? 'Durasi menyesuaikan'), ENT_QUOTES, 'UTF-8'),
            'poin' => ['Via video call / Zoom', 'Tema bisa di-request', 'Fleksibel dari mana saja'],
        ],
    ];
}
?>
<section class="section section--surface" id="biaya">
    <div class="container">
        <div class="section__head reveal" style="margin: 0 auto; text-align: center;">
            <span class="section-label">Biaya Kursus</span>
            <h2 class="section-title">Daftar Harga</h2>
            <p class="section-subtitle" style="max-width: 640px; margin: 0 auto;">Harga transparan sesuai pilihan Anda. Nominal total tampil langsung di formulir pendaftaran, lalu Anda menerima halaman status dengan waktu bayar 24 jam.</p>
        </div>

        <div class="feature-grid">
            <?php foreach ($__items as $i => $item): ?>
                <div class="feature-card reveal" <?= $i ? 'style="transition-delay:.1s"' : '' ?>>
                    <div class="feature-card__icon"><span class="material-symbols-rounded"><?= $item['icon'] ?></span></div>
                    <h4><?= $item['judul'] ?></h4>
                    <div class="price-tag"><?= $item['nominal'] ?><span class="price-tag__unit"><?= $item['satuan'] ?></span></div>
                    <p class="price-tag__ket"><?= $item['ket'] ?></p>
                    <ul class="curriculum-list">
                        <?php foreach ($item['poin'] as $poin): ?>
                            <li><span class="material-symbols-rounded">check_circle</span> <?= $poin ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="reveal" style="text-align: center; margin-top: 32px;">
            <?php
            $__bankNames = implode('/', array_map(fn($b) => $b['label'], donasi_bank_channels()));
            $__walletNames = implode('/', array_map(fn($w) => $w['label'], donasi_ewallet_channels()));
            ?>
            <p class="section-subtitle" style="margin: 0 auto 24px auto;">Pembayaran via <strong>Transfer Bank (<?= htmlspecialchars($__bankNames) ?>)</strong> a.n. <?= htmlspecialchars(donasi_info()['bank_nama'] ?? '') ?> atau <strong>E-Wallet (<?= htmlspecialchars($__walletNames) ?>)</strong> — nomor rekening dan kode transfer antarbank tertera di halaman status setelah Anda mengajukan program.</p>
            <button class="btn btn-primary" onclick="<?= $__ctaCall ?>" style="font-size: 15px; padding: 14px 28px;">
                <?= $__ctaLabel ?>
                <span class="material-symbols-rounded btn__icon">arrow_forward</span>
            </button>
        </div>
    </div>
</section>
