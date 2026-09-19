<?php
// pages/akun/pendaftaran-saya.php — riwayat pengajuan program milik user yang login.
// Tiap baris menautkan kembali ke halaman status (tunggu.php?id=…) yang berisi
// countdown 24 jam + tombol salin nominal & nomor rekening/tujuan transfer.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/pembayaran.php';

if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

$email = (string) ($_SESSION['user']['email'] ?? '');
$stmt = $pdo->prepare('SELECT id, program, nama, metode, biaya, metode_pembayaran, kanal_pembayaran, status_pembayaran, no_invoice, batas_bayar, created_at FROM pendaftar WHERE user_email = ? ORDER BY id DESC');
$stmt->execute([$email]);
$daftar = $stmt->fetchAll();

// Sisa waktu dalam format ringkas untuk badge ("2 hari 3 jam" / "5 jam 12 menit" / "9 menit").
function sisa_waktu_teks(int $deadlineTs): string
{
    $diff = $deadlineTs - time();
    if ($diff <= 0) {
        return 'berakhir';
    }
    $d = intdiv($diff, 86400);
    $h = intdiv($diff % 86400, 3600);
    $m = intdiv($diff % 3600, 60);
    if ($d > 0) {
        return "{$d} hari {$h} jam";
    }
    if ($h > 0) {
        return "{$h} jam {$m} menit";
    }
    return "{$m} menit";
}

include __DIR__ . '/../../layouts/header.php';
?>

<!-- =================== PAGE HERO =================== -->
<section class="page-hero">
    <div class="container">
        <span class="section-label">Akun</span>
        <h1 class="page-hero__title">Pendaftaran Saya</h1>
        <p class="page-hero__desc">Buka kembali halaman status & pembayaran Anda — lengkap dengan countdown dan tombol salin nomor rekening.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px; padding-bottom: 80px; min-height: 60vh;">
    <div class="container" style="max-width: 720px;">

        <?php if (empty($daftar)): ?>
            <div style="background: var(--surface, #fff); padding: 40px 32px; border-radius: 12px; box-shadow: var(--shadow-sm, 0 4px 6px rgba(0,0,0,0.05)); text-align: center;">
                <span class="material-symbols-rounded" style="font-size: 48px; color: var(--muted, #94A3B8);">receipt_long</span>
                <p style="font-size: 16px; font-weight: 600; color: var(--text-dark, #0F172A); margin: 12px 0 6px;">Belum ada pengajuan program</p>
                <p style="font-size: 14px; color: var(--muted, #94A3B8); margin: 0 0 20px;">Pilih program belajar, lalu halaman status pembayaran akan muncul di sini.</p>
                <a href="/#program" class="btn btn-primary" style="display: inline-block; padding: 12px 28px; font-size: 15px; border-radius: 8px; text-decoration: none;">Lihat Program</a>
            </div>
        <?php else: ?>
            <?php foreach ($daftar as $r): ?>
                <?php
                $id = (int) $r['id'];
                $label = match ($r['program']) {
                    'offline' => 'Offline ' . ($r['metode'] ?: ''),
                    'online'  => 'Online '  . ($r['metode'] ?: ''),
                    'dauroh'  => 'Dauroh '  . ($r['metode'] ?: ''),
                    default   => ucfirst((string) $r['program']),
                };
                $mb = (string) ($r['metode_pembayaran'] ?: 'bank');
                $kanalLabel = donasi_kanal_label($mb, (string) ($r['kanal_pembayaran'] ?? ''));
                $metodeLabel = match ($mb) {
                    'bank' => 'Transfer Bank' . ($kanalLabel !== '' ? ' · ' . $kanalLabel : ''),
                    'qris' => 'QRIS',
                    'ewallet' => 'E-Wallet' . ($kanalLabel !== '' ? ' · ' . $kanalLabel : ''),
                    default => '-',
                };
                $lunas = ($r['status_pembayaran'] ?? '') === 'lunas';
                $batas = $r['batas_bayar'] ?: date('Y-m-d H:i:s', strtotime($r['created_at'] ?? 'now') + 86400);
                $deadlineTs = (int) strtotime($batas);
                $expired = !$lunas && time() > $deadlineTs;
                if ($lunas) {
                    $badgeBg = '#dcfce7'; $badgeFg = '#166534'; $badgeBd = '#22c55e'; $badgeTxt = 'LUNAS';
                } elseif ($expired) {
                    $badgeBg = '#fee2e2'; $badgeFg = '#991b1b'; $badgeBd = '#ef4444'; $badgeTxt = 'BATAS WAKTU HABIS';
                } else {
                    $badgeBg = '#fef3c7'; $badgeFg = '#92400e'; $badgeBd = '#f59e0b'; $badgeTxt = 'MENUNGGU · ' . sisa_waktu_teks($deadlineTs);
                }
                ?>
                <div style="background: var(--surface, #fff); padding: 20px 24px; border-radius: 12px; box-shadow: var(--shadow-sm, 0 4px 6px rgba(0,0,0,0.05)); margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--text-dark, #0F172A);"><?= htmlspecialchars($label) ?> — <?= htmlspecialchars($r['nama']) ?></div>
                            <div style="font-size: 13.5px; color: var(--text-body, #475569); margin-top: 4px;"><?= htmlspecialchars($metodeLabel) ?> · <?= rupiah((int) $r['biaya']) ?></div>
                            <div style="font-size: 13px; color: var(--muted, #94A3B8); margin-top: 2px;">
                                <?= $lunas ? 'Pembayaran diterima. Terima kasih!' : ('Batas bayar: ' . date('d M Y, H:i', $deadlineTs) . ' WIB') ?>
                            </div>
                        </div>
                        <span style="font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 999px; border: 1px solid <?= $badgeBd ?>; background: <?= $badgeBg ?>; color: <?= $badgeFg ?>; white-space: nowrap;"><?= htmlspecialchars($badgeTxt) ?></span>
                    </div>
                    <div style="margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="/pages/bayar/tunggu.php?id=<?= $id ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 14px; border-radius: 8px; text-decoration: none;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">payments</span> Lihat Status &amp; Bayar
                        </a>
                        <?php if ($lunas && ($r['no_invoice'] ?? '') !== ''): ?>
                            <a href="/pages/bayar/struk.php?no=<?= urlencode($r['no_invoice']) ?>" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 14px; border-radius: 8px; text-decoration: none;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">receipt_long</span> Lihat Struk
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</section>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
