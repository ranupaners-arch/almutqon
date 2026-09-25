<?php
// pages/bayar/struk.php — struk/kwitansi pembayaran kursus (bisa dicetak / download PDF)
// Akses: /pages/bayar/struk.php?no=INV-YYYYMMDD-XXXX
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/pembayaran.php';

$no  = trim($_GET['no'] ?? '');
$row = $no !== '' ? cari_pendaftar_by_invoice($pdo, $no) : false;

if ($row === false) {
    http_response_code(404);
    ?><!doctype html><html lang="id"><head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk Tidak Ditemukan</title></head>
    <body style="font-family:sans-serif;text-align:center;padding:60px 20px;">
        <h1>Struk tidak ditemukan</h1>
        <p>Periksa kembali nomor invoice Anda, atau hubungi admin:<br>
        <strong>admin.almutqon@gmail.com</strong> / WhatsApp admin.</p>
        <p><a href="/">← Kembali ke Beranda</a></p>
    </body></html><?php
    exit;
}

$lunas  = $row['status_pembayaran'] === 'lunas';
$label  = match ($row['program']) {
    'offline' => ($row['metode'] === 'Privat') ? 'Offline Privat' : 'Offline Non-Privat',
    'online'  => ($row['metode'] === 'Privat') ? 'Online Privat' : 'Online Non-Privat',
    'dauroh'  => 'Dauroh ' . ($row['metode'] ?: 'Offline'),
};
$metodeBayar = match ($row['metode_pembayaran']) {
    'bank'    => 'Transfer Bank',
    'qris'    => 'QRIS',
    'ewallet' => 'E-Wallet',
    default   => '-',
};
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Struk <?= htmlspecialchars($no) ?> — AL MUTQON</title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #eef1f5; font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color:#111; }
    .toolbar { background:#0B1120; color:#fff; padding: 14px 20px; display:flex; gap:10px; align-items:center; justify-content:center; flex-wrap:wrap; }
    .toolbar h2 { margin:0 16px 0 0; font-size:16px; }
    .toolbar .btn { background:#16a34a; color:#fff; border:0; padding:9px 18px; border-radius:8px; font-size:14px; cursor:pointer; text-decoration:none; }
    .toolbar .btn:hover { filter:brightness(1.1); }
    .wrap { max-width: 720px; margin: 26px auto; padding: 0 14px; }
    .receipt { background:#fff; border:1px solid #d5d9e0; padding: 42px 44px; box-shadow: 0 6px 24px rgba(11,17,32,.10); }
    .head { text-align:center; border-bottom: 3px solid #12203f; padding-bottom: 14px; }
    .head h1 { margin:0; font-size: 26px; letter-spacing: 2px; color:#12203f; }
    .head p { margin: 4px 0 0; color:#4b5563; font-size: 13px; letter-spacing: 1px; }
    h2.t { text-align:center; font-size: 14px; letter-spacing: 1px; margin: 18px 0 6px; color:#12203f; }
    table.detail { width:100%; border-collapse: collapse; margin-top: 8px; }
    table.detail td { padding: 8px 6px; font-size: 14px; vertical-align: top; }
    table.detail td.k { color:#4b5563; width: 34%; }
    table.detail td.v { font-weight:600; }
    .box-amount { background:#f4f7fb; border:1px solid #d5d9e0; border-radius:10px; padding: 16px 18px; margin: 14px 0; }
    .box-amount .row { display:flex; justify-content:space-between; font-size:15px; padding:4px 0; }
    .box-amount .total { font-weight:800; font-size:18px; border-top:1px dashed #b6c0cf; margin-top:8px; padding-top:10px; color:#12203f; }
    .status { display:inline-block; padding:2px 14px; border-radius: 999px; font-size:12px; font-weight:800; letter-spacing:1px; }
    .status.lunas { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
    .status.belum { background:#fef3c7; color:#a16207; border:1px solid #fde68a; }
    .sign { display:flex; justify-content:flex-end; margin-top: 30px; }    .sign__col { text-align:center; }
    .sign__col p { margin: 4px 0; font-size:13px; color:#374151; }
    .sign__qr { margin: 10px auto 6px; }
    .sign__qr svg, .sign__qr canvas { display:block; border-radius:8px; margin:0 auto; }
    .sign__code strong { font-size:16px; letter-spacing:2px; color:#111; }
    .sign .space { height:36px; }
    .sign .line { border-top:1px solid #111; width:180px; margin:0 auto; }
    .sign b { font-size:14px; }
    .foot { text-align:center; color:#9ca3af; font-size:11px; margin-top: 34px; }
    @media print {
        body { background:#fff; }
        .toolbar { display:none; }
        .wrap { max-width: none; margin: 0; padding: 0; }
        .receipt { border:0; box-shadow:none; padding: 30px; }
    }
    @media (max-width:600px) {
        .receipt { padding: 22px 18px; }
        .sign { justify-content: center; }
    }
</style>
</head>
<body>
<div class="toolbar no-print">
    <h2>Struk Pembayaran AL MUTQON</h2>
    <button class="btn" onclick="window.print()">Cetak / Simpan PDF</button>
    <a class="btn" href="struk-pdf.php?no=<?= urlencode($no) ?>">Download PDF</a>
</div>

<div class="wrap">
    <div class="receipt">
        <div class="head">
            <h1>AL MUTQON</h1>
            <p>ARABIC LEARNING CENTER</p>
        </div>
        <h2 class="t">STRUK KONFIRMASI PEMBAYARAN KURSUS</h2>

        <table class="detail">
            <tr><td class="k">No. Invoice</td><td class="v"><?= htmlspecialchars($row['no_invoice']) ?></td></tr>
            <tr><td class="k">Enrollment ID</td><td class="v"><?= htmlspecialchars($row['enrollment_id'] ?: '-') ?></td></tr>
            <tr><td class="k">Tanggal</td><td class="v"><?= date('d M Y') ?></td></tr>
            <?php if ($lunas && !empty($row['tanggal_bayar'])): ?>
            <tr><td class="k">Tanggal Bayar</td><td class="v"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal_bayar']))) ?></td></tr>
            <?php endif; ?>
            <tr><td class="k">Diterima dari</td><td class="v"><?= htmlspecialchars($row['nama']) ?></td></tr>
            <tr><td class="k">WhatsApp</td><td class="v"><?= htmlspecialchars($row['whatsapp']) ?></td></tr>
            <tr><td class="k">Program</td><td class="v"><?= htmlspecialchars($label) ?></td></tr>
            <?php if ($row['program'] === 'dauroh' && (int) $row['jumlah_peserta'] > 1): ?>
            <tr><td class="k">Jumlah Peserta</td><td class="v"><?= (int) $row['jumlah_peserta'] ?> orang</td></tr>
            <?php endif; ?>
            <?php if (!empty($row['instansi'])): ?>
            <tr><td class="k">Instansi</td><td class="v"><?= htmlspecialchars($row['instansi']) ?></td></tr>
            <?php endif; ?>
        </table>

        <div class="box-amount">
            <div class="row"><span>Total Pembayaran</span><strong><?= rupiah((int) $row['biaya']) ?></strong></div>
            <div class="row"><span>Terbilang</span><span><?= htmlspecialchars(ucfirst(terbilang((int) $row['biaya'])) . ' rupiah') ?></span></div>
            <div class="row"><span>Metode Pembayaran</span><span><?= htmlspecialchars($metodeBayar) ?></span></div>
            <div class="row total"><span>Status</span><span class="status <?= $lunas ? 'lunas' : 'belum' ?>"><?= $lunas ? 'LUNAS' : strtoupper($row['status_pembayaran'] ?? 'belum') ?></span></div>
        </div>

        <?php $verifUrl = (string) ($row['verif_url'] ?? ''); $verifCode = (string) ($row['verif_code'] ?? ''); ?>

        <div class="sign">
            <div class="sign__col">
                <p>Bagian Keuangan</p>
                <?php if ($verifUrl !== ''): ?>
                <div class="sign__qr" id="verifQr" role="img" aria-label="QR verifikasi dokumen"></div>
                <p class="sign__code">Kode: <strong><?= htmlspecialchars($verifCode) ?></strong></p>
                <?php endif; ?>
                <div class="space"></div>
                <div class="line"></div>
                <b>Admin Almutqon</b>
            </div>
        </div>

        <?php if ($verifUrl !== ''): ?>
        <script src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
        <script>
        (function () {
            var el = document.getElementById('verifQr');
            if (!el || typeof QRCodeStyling === 'undefined') return;
            try {
                new QRCodeStyling({
                    width: 110, height: 110,
                    data: <?= json_encode($verifUrl) ?>,
                    dotsOptions: { color: '#000000', type: 'square' },
                    backgroundOptions: { color: '#ffffff' },
                    cornersSquareOptions: { color: '#000000', type: 'square' },
                    cornersDotOptions: { color: '#000000', type: 'square' }
                }).append(el);
            } catch (e) { /* kode tetap tampil */ }
        })();
        </script>
        <?php endif; ?>

        <div class="foot">Dokumen ini sah sebagai bukti pembayaran kursus AL MUTQON.<br>
        Simpan baik-baik. Konfirmasi: admin.almutqon@gmail.com</div>
    </div>
</div>
</body>
</html>
