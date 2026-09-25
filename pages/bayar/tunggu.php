<?php
// pages/bayar/tunggu.php — halaman status pengajuan program (countdown 24 jam + status bayar)
// Akses: /pages/bayar/tunggu.php?id=… — hanya pemilik pengajuan (user_email) atau admin.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/pembayaran.php';
require_once __DIR__ . '/../../config/identity.php';

$reference = trim((string) ($_GET['id'] ?? $_GET['enrollment_id'] ?? ''));
$row = find_pendaftar_by_reference($pdo, $reference);
$id = (int) ($row['id'] ?? 0);

$allowed = false;
if ($row) {
    $userEmail = (string) ($_SESSION['user']['email'] ?? '');
    $allowed = is_admin() || ($userEmail !== '' && strcasecmp($userEmail, (string) $row['user_email']) === 0);
}

if (!$row || !$allowed) {
    http_response_code(404);
    ?><!doctype html><html lang="id"><head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengajuan Tidak Ditemukan</title></head>
    <body style="font-family:sans-serif;text-align:center;padding:60px 20px;">
        <h1>Pengajuan tidak ditemukan</h1>
        <p>Link tidak valid atau Anda bukan pemilik pengajuan ini.<br>
        Silakan <a href="/auth/login.php">masuk</a> dengan akun yang sama saat mendaftar.</p>
        <p><a href="/">← Kembali ke Beranda</a></p>
    </body></html><?php
    exit;
}

$lunas = $row['status_pembayaran'] === 'lunas';
$label = match ($row['program']) {
    'offline' => 'Offline ' . ($row['metode'] ?: ''),
    'online'  => 'Online '  . ($row['metode'] ?: ''),
    'dauroh'  => 'Dauroh '  . ($row['metode'] ?: ''),
};

// Batas bayar: kolom batas_bayar; bila kosong (data lama) fallback created_at + 24 jam.
$batas = $row['batas_bayar'] ?: date('Y-m-d H:i:s', strtotime($row['created_at'] ?? 'now') + 86400);
$deadlineTs = strtotime($batas);
$expired = !$lunas && time() > $deadlineTs;

$mb = $row['metode_pembayaran'] ?: 'bank';
$target = donasi_target($mb, (string) ($row['kanal_pembayaran'] ?? ''));

$kanalLabel = donasi_kanal_label($mb, (string) ($target['kanal'] ?? ''));
$metodeBayarLabel = match ($mb) {
    'bank' => 'Transfer Bank' . ($kanalLabel !== '' ? ' · ' . $kanalLabel : ''),
    'qris' => 'QRIS',
    'ewallet' => 'E-Wallet' . ($kanalLabel !== '' ? ' · ' . $kanalLabel : ''),
    default => '-',
};
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Status Pengajuan — AL MUTQON</title>
<link rel="stylesheet" href="/css/base.css?v=20260917h">
<link rel="stylesheet" href="/css/layout.css?v=20260917h">
<link rel="stylesheet" href="/css/components.css?v=20260917h">
<link rel="stylesheet" href="/css/pages.css?v=20260917h">
<link rel="stylesheet" href="/css/theme.css?v=20260917h">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
<style>
    .wt { max-width: 720px; margin: 0 auto; padding: 48px 20px 80px; }
    .wt__card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: 0 20px 50px rgba(11,17,32,.10); overflow: hidden; }
    .wt__head { padding: 28px 30px; text-align: center; border-bottom: 1px solid var(--border); background: linear-gradient(180deg, rgba(15,118,110,.05), #fff); }
    .wt__head .status { display: inline-block; padding: 5px 16px; border-radius: 999px; font-size: 12px; font-weight: 800; letter-spacing: 1px; }
    .status.lunas { background: #dcfce7; color: #15803d; }
    .status.belum { background: #fef3c7; color: #a16207; }
    .status.pending { background: #dbeafe; color: #1d4ed8; }
    .status.lewat { background: #fee2e2; color: #b91c1c; }
    .wt__head h1 { margin: 14px 0 6px; font-family: var(--font-heading); font-size: 22px; color: #0B1120; }
    .wt__head p { margin: 0; color: #64748b; font-size: 14px; }
    .wt__body { padding: 26px 30px 32px; }
    .count { display: flex; justify-content: center; gap: 12px; margin: 22px 0 8px; }
    .count__box { flex: 1; max-width: 96px; padding: 14px 6px; text-align: center; background: #0B1120; color: #fff; border-radius: 14px; }
    .count__num { font-size: 30px; font-weight: 800; font-variant-numeric: tabular-nums; line-height: 1.05; }
    .count__lbl { font-size: 11px; opacity: .65; margin-top: 4px; letter-spacing: 1px; }
    .wt__note { text-align: center; font-size: 13px; color: #64748b; margin-top: 6px; }
    .wt__note b { color: #b45309; }
    .wt__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 26px; }
    .box { border: 1px solid var(--border); border-radius: 12px; padding: 16px 18px; }
    .box h3 { margin: 0 0 10px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: #64748b; }
    .box .big { font-size: 22px; font-weight: 800; color: #0F766E; display: flex; align-items: center; gap: 6px; }
    .box li { list-style: none; padding: 3px 0; font-size: 14px; color: #334155; display: flex; align-items: center; gap: 2px; }
    .box li::before { content: "•"; color: #0F766E; margin-right: 8px; }
    .copy { display: inline-flex; align-items: center; justify-content: center; width: 27px; height: 27px; flex: none; padding: 0; border: 1px solid var(--border); background: #f8fafc; color: #475569; border-radius: 7px; cursor: pointer; transition: background .15s, color .15s, border-color .15s, transform .1s; }
    .copy:hover { background: #0F766E; color: #fff; border-color: #0F766E; }
    .copy:active { transform: scale(.92); }
    .copy .material-symbols-rounded { font-size: 16px; }
    .copy.copied { background: #16a34a; color: #fff; border-color: #16a34a; }
    .box .gambar { text-align: center; margin-top: 8px; }
    .box .gambar img { width: 170px; border-radius: 10px; }
    .verif2 { margin-top: 16px; border: 1px solid var(--border); border-radius: 12px; padding: 16px 18px; background: #f8fafc; display: flex; gap: 16px; align-items: center; }
    .verif2 p { margin: 4px 0; font-size: 13.5px; color: #334155; }
    .verif2__code strong { font-size: 16px; letter-spacing: 2px; }
    .verif2__qr { flex: none; margin-left: auto; }
    .verif2__qr svg, .verif2__qr canvas { display: block; border-radius: 8px; }
    .wt__actions { margin-top: 26px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn2 { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 10px; font-weight: 700; font-size: 14px; text-decoration: none; border: 0; cursor: pointer; }
    .btn2--dark { background: #0B1120; color: #fff; }
    .btn2--green { background: #16a34a; color: #fff; }
    .btn2--gold { background: #d97706; color: #fff; }
    .btn2--ghost { background: #f1f5f9; color: #334155; }
    .box--alert { display: none; margin-top: 20px; padding: 16px; border-radius: 12px; font-size: 14px; }
    .box--alert.show { display: block; }
    .box--alert.err { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .box--alert.ok  { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    /* Blok perayaan saat pembayaran lunas */
    .lunas-hero { position: relative; overflow: hidden; text-align: center; margin: 22px 0 6px; padding: 32px 18px 28px; border-radius: 16px; background: linear-gradient(180deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; }
    .lunas-hero__icon { position: relative; z-index: 1; display: inline-flex; align-items: center; justify-content: center; width: 84px; height: 84px; border-radius: 50%; background: #16a34a; color: #fff; box-shadow: 0 12px 30px rgba(22,163,74,.35); animation: lunas-pop .6s cubic-bezier(.2,1.4,.4,1) both, lunas-float 3s ease-in-out .7s infinite; }
    .lunas-hero__icon .material-symbols-rounded { font-size: 46px; }
    .lunas-hero__icon::before, .lunas-hero__icon::after { content: ""; position: absolute; inset: 0; border-radius: 50%; border: 3px solid rgba(22,163,74,.45); animation: lunas-ring 2.2s ease-out infinite; }
    .lunas-hero__icon::after { animation-delay: 1.1s; }
    @keyframes lunas-pop { 0% { transform: scale(0); } 100% { transform: scale(1); } }
    @keyframes lunas-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    @keyframes lunas-ring { 0% { transform: scale(1); opacity: .8; } 100% { transform: scale(1.7); opacity: 0; } }
    .lunas-hero h2 { position: relative; z-index: 1; margin: 16px 0 8px; font-family: var(--font-heading); font-size: 20px; color: #14532d; }
    .lunas-hero p { position: relative; z-index: 1; margin: 0 auto; max-width: 480px; font-size: 14px; line-height: 1.65; color: #3f6212; }
    .lunas-hero p strong { color: #14532d; }
    .confetti { position: absolute; top: -14px; left: var(--l, 50%); width: 8px; height: 14px; border-radius: 2px; background: var(--c, #f59e0b); opacity: 0; animation: confetti-fall 3.2s ease-in infinite; animation-delay: var(--d, 0s); }
    @keyframes confetti-fall { 0% { transform: translateY(-10px) rotate(0deg); opacity: 0; } 10% { opacity: 1; } 100% { transform: translateY(320px) translateX(var(--x, 0px)) rotate(540deg); opacity: 0; } }
    @media (prefers-reduced-motion: reduce) {
        .lunas-hero__icon, .lunas-hero__icon::before, .lunas-hero__icon::after, .confetti { animation: none !important; }
        .confetti { display: none; }
    }
    @media (max-width: 560px) {
        .wt { padding: 24px 14px 60px; }
        .wt__head, .wt__body { padding: 22px 18px; }
        .wt__grid { grid-template-columns: 1fr; }
        .verif2 { flex-direction: column; }
        .verif2__qr { margin-left: 0; }
        .count__num { font-size: 24px; }
    }
</style>
</head>
<body>
<div class="wt">
    <div class="wt__card">
        <div class="wt__head">
            <span class="status <?= $lunas ? 'lunas' : ($expired ? 'lewat' : $row['status_pembayaran']) ?>">
                <?= $lunas ? 'PEMBAYARAN LUNAS' : ($expired ? 'BATAS WAKTU HABIS' : 'MENUNGGU PEMBAYARAN') ?>
            </span>
            <h1><?= $lunas ? 'Pembayaran Lunas — Selamat Bergabung!' : 'Pengajuan Program Terkirim, Silahkan Menyelesaikan Pembayaran' ?></h1>
            <p><strong><?= htmlspecialchars($row['nama']) ?></strong> · <?= htmlspecialchars($label) ?></p>
            <p>Enrollment ID: <code><?= htmlspecialchars($row['enrollment_id'] ?: '-') ?></code></p>
        </div>
        <div class="wt__body">

            <?php if (!$lunas && !$expired): ?>
                <div class="count" id="countWrap" data-deadline="<?= date('c', $deadlineTs) ?>">
                    <div class="count__box"><div class="count__num" id="cD">00</div><div class="count__lbl">Hari</div></div>
                    <div class="count__box"><div class="count__num" id="cH">00</div><div class="count__lbl">Jam</div></div>
                    <div class="count__box"><div class="count__num" id="cM">00</div><div class="count__lbl">Menit</div></div>
                    <div class="count__box"><div class="count__num" id="cS">00</div><div class="count__lbl">Detik</div></div>
                </div>
                <p class="wt__note">Selesaikan pembayaran sebelum <b><?= date('d M Y, H:i', $deadlineTs) ?> WIB</b> agar pengajuan tetap aktif.</p>
            <?php elseif ($lunas): ?>
                <div class="lunas-hero" role="status">
                    <?php $confettiColors = ['#f59e0b', '#0ea5e9', '#ec4899', '#16a34a', '#8b5cf6', '#ef4444']; ?>
                    <?php for ($i = 0; $i < 16; $i++): ?>
                        <span class="confetti" aria-hidden="true" style="--l: <?= 4 + $i * 6 ?>%; --d: <?= number_format(($i % 8) * 0.35, 2, '.', '') ?>s; --c: <?= $confettiColors[$i % 6] ?>; --x: <?= ($i % 2 ? '' : '-') . (20 + ($i * 13) % 60) ?>px;"></span>
                    <?php endfor; ?>
                    <span class="lunas-hero__icon"><span class="material-symbols-rounded">verified</span></span>
                    <h2>Pembayaran Anda Telah Kami Terima. Terima Kasih! 🎉</h2>
                    <p>Selamat! Pendaftaran program <strong><?= htmlspecialchars($label) ?></strong> sudah <strong>LUNAS</strong>. Selamat belajar — semoga ilmunya bermanfaat dan berkah. Tim kami akan menghubungi Anda via WhatsApp untuk langkah selanjutnya.</p>
                </div>
            <?php else: ?>
                <div class="box--alert err show">
                    Batas waktu pembayaran telah berakhir. Pengajuan dapat dibatalkan oleh tim kami. Hubungi admin untuk perpanjangan atau pengajuan ulang.
                </div>
            <?php endif; ?>

            <div class="wt__grid">
                <div class="box">
                    <h3><?= $lunas ? 'Total yang Dibayar' : 'Total yang Harus Dibayar' ?></h3>
                    <div class="big">
                        <span><?= rupiah((int) $row['biaya']) ?></span>
                        <?php if (!$lunas): ?>
                        <button type="button" class="copy" data-copy="<?= (int) $row['biaya'] ?>" title="Salin nominal" aria-label="Salin nominal">
                            <span class="material-symbols-rounded">content_copy</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="box">
                    <h3>Metode Pembayaran</h3>
                    <div class="big" style="font-size:18px;"><?= htmlspecialchars($metodeBayarLabel) ?></div>
                </div>
            </div>

            <?php if (!$lunas): ?>
            <div class="wt__grid" style="margin-top:0;">
                <div class="box" style="grid-column: 1 / -1;">
                    <h3>Transfer ke Tujuan Ini</h3>
                    <ul>
                        <?php foreach ($target['baris'] as $b): ?>
                            <li>
                                <span><?= htmlspecialchars($b) ?></span>
                                <button type="button" class="copy" data-copy="<?= htmlspecialchars($b, ENT_QUOTES) ?>" title="Salin <?= htmlspecialchars($b, ENT_QUOTES) ?>" aria-label="Salin <?= htmlspecialchars($b, ENT_QUOTES) ?>">
                                    <span class="material-symbols-rounded">content_copy</span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($target['gambar'] !== ''): ?>
                        <div class="gambar"><img src="<?= htmlspecialchars($target['gambar']) ?>" alt="QRIS Almutqon"></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php $verifUrl = $lunas ? (string) ($row['verif_url'] ?? '') : ''; $verifCode = (string) ($row['verif_code'] ?? ''); ?>
            <?php if ($verifUrl !== ''): ?>
            <div class="verif2">
                <div>
                    <?php if ($verifCode !== ''): ?>
                    <p class="verif2__code">Kode: <strong><?= htmlspecialchars($verifCode) ?></strong></p>
                    <?php endif; ?>
                </div>
                <div class="verif2__qr" id="verifQr" data-url="<?= htmlspecialchars($verifUrl) ?>" role="img" aria-label="QR verifikasi dokumen"></div>
            </div>
            <?php endif; ?>

            <div class="wt__actions">
                <?php if ($lunas): ?>
                    <?php if ($row['no_invoice'] !== ''): ?>
                    <a class="btn2 btn2--green" href="struk.php?no=<?= urlencode($row['no_invoice']) ?>">
                        <span class="material-symbols-rounded" style="font-size:18px;">receipt_long</span> Lihat Struk
                    </a>
                    <a class="btn2 btn2--ghost" href="struk-pdf.php?no=<?= urlencode($row['no_invoice']) ?>">
                        <span class="material-symbols-rounded" style="font-size:18px;">download</span> Download PDF
                    </a>
                    <?php endif; ?>
                    <?php if ($verifUrl !== ''): ?>
                    <a class="btn2 btn2--dark" target="_blank" rel="noopener" href="<?= htmlspecialchars($verifUrl) ?>">
                        <span class="material-symbols-rounded" style="font-size:18px;">verified</span> Verifikasi Dokumen
                    </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="btn2 btn2--gold" href="https://wa.me/6282258442647?text=<?= rawurlencode('Halo, saya ' . $row['nama'] . ' ingin konfirmasi pembayaran program ' . $label . ' (Enrollment ID: ' . ($row['enrollment_id'] ?: $row['id']) . ')') ?>">
                        <span class="material-symbols-rounded" style="font-size:18px;">chat</span> Konfirmasi ke Admin
                    </a>
                <?php endif; ?>
                <a class="btn2 btn2--dark" href="/">
                    <span class="material-symbols-rounded" style="font-size:18px;">home</span> Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const wrap = document.getElementById('countWrap');
    if (!wrap) return;
    const deadline = new Date(wrap.dataset.deadline).getTime();
    const pad = n => String(n).padStart(2, '0');
    const els = { d: document.getElementById('cD'), h: document.getElementById('cH'), m: document.getElementById('cM'), s: document.getElementById('cS') };
    function tick() {
        let diff = Math.max(0, deadline - Date.now());
        const d = Math.floor(diff / 86400000);
        diff -= d * 86400000;
        const h = Math.floor(diff / 3600000);
        diff -= h * 3600000;
        const m = Math.floor(diff / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        els.d.textContent = pad(d);
        els.h.textContent = pad(h);
        els.m.textContent = pad(m);
        els.s.textContent = pad(s);
        if (diff <= 0) location.reload();
    }
    tick();
    setInterval(tick, 1000);
})();

// Salin nominal & nomor rekening (dengan fallback untuk koneksi non-HTTPS)
(function () {
    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.top = '-1000px';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        let ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(ta);
        return ok;
    }
    function markCopied(btn) {
        btn.classList.add('copied');
        const icon = btn.querySelector('.material-symbols-rounded');
        const prev = icon ? icon.textContent : '';
        if (icon) icon.textContent = 'check';
        clearTimeout(btn._t);
        btn._t = setTimeout(() => {
            btn.classList.remove('copied');
            if (icon) icon.textContent = prev;
        }, 1400);
    }
    document.querySelectorAll('.copy').forEach((btn) => {
        btn.addEventListener('click', () => {
            const text = btn.dataset.copy || '';
            const done = () => markCopied(btn);
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done).catch(() => { if (fallbackCopy(text)) done(); });
            } else if (fallbackCopy(text)) {
                done();
            }
        });
    });
})();
</script>
<script src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
<script>
// QR verifikasi dokumen (hanya ada saat LUNAS terverifikasi; gagal load = kode tetap tampil)
(function () {
    var el = document.getElementById('verifQr');
    if (!el || typeof QRCodeStyling === 'undefined') return;
    var data = el.getAttribute('data-url');
    if (!data) return;
    try {
        new QRCodeStyling({
            width: 120, height: 120,
            data: data,
            dotsOptions: { color: '#000000', type: 'square' },
            backgroundOptions: { color: '#ffffff' },
            cornersSquareOptions: { color: '#000000', type: 'square' },
            cornersDotOptions: { color: '#000000', type: 'square' }
        }).append(el);
    } catch (e) { /* abaikan */ }
})();
</script>
</body>
</html>
