<?php
// pages/akun/materi.php — dashboard peserta: Materi Kursus sesuai program LUNAS yang diikuti.
// Wajib login (guard di layouts/akun-header.php).
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$email = (string) ($_SESSION['user']['email'] ?? '');

$stmt = $pdo->prepare("SELECT id, program, nama, metode FROM pendaftar WHERE user_email = ? AND status_pembayaran = 'lunas' ORDER BY id DESC");
$stmt->execute([$email]);
$lunas = $stmt->fetchAll();

$materi = [];
if (count($lunas) > 0) {
    $programs = array_values(array_unique(array_column($lunas, 'program')));
    $ph = implode(',', array_fill(0, count($programs), '?'));
    $stmt = $pdo->prepare("SELECT id, program, judul, created_at FROM materi_kursus WHERE program IN ($ph) ORDER BY created_at DESC");
    $stmt->execute($programs);
    $materi = $stmt->fetchAll();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM jurnal_peserta WHERE user_email = ?");
$stmt->execute([$email]);
$totalTugas = (int) $stmt->fetchColumn();

// Rekap kehadiran per program LUNAS milik user (hadir / total sesi tercatat).
$hadir = [];
if (count($lunas) > 0) {
    $ids = array_column($lunas, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT pendaftar_id, status FROM absensi WHERE pendaftar_id IN ($ph)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $a) {
        $hadir[$a['pendaftar_id']][] = $a['status'];
    }
}
$persenHadir = function (int $pid) use ($hadir): ?int {
    if (empty($hadir[$pid])) {
        return null;
    }
    $total = count($hadir[$pid]);
    $h = count(array_filter($hadir[$pid], fn($s) => $s === 'hadir'));
    return (int) round($h * 100 / $total);
};

$akun_title = 'Materi Kursus';
$akun_aktif = 'materi';
include __DIR__ . '/../../layouts/akun-header.php';
?>

<?php if (empty($lunas)): ?>
    <div class="admin__card" style="text-align:center; padding:48px 24px;">
        <span class="material-symbols-rounded" style="font-size:40px; color:#CBD5E1;">lock</span>
        <p style="font-size:16px; font-weight:800; margin:12px 0 6px;">Halaman khusus peserta aktif</p>
        <p style="font-size:14px; color:var(--admin-muted); margin:0 0 20px;">Selesaikan pembayaran program Anda terlebih dahulu untuk mulai belajar.</p>
        <a class="admin__btn admin__btn--primary" href="/pages/akun/pendaftaran-saya.php">Lihat Pendaftaran Saya</a>
    </div>
<?php else: ?>

    <div class="admin__stats">
        <div class="admin__stat">
            <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">school</span></div>
            <div><div class="admin__stat-num"><?= count($lunas) ?></div><div class="admin__stat-label">Program Aktif</div></div>
        </div>
        <div class="admin__stat">
            <div class="admin__stat-icon admin__stat-icon--blue"><span class="material-symbols-rounded">folder_open</span></div>
            <div><div class="admin__stat-num"><?= count($materi) ?></div><div class="admin__stat-label">Materi Tersedia</div></div>
        </div>
        <div class="admin__stat">
            <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">upload_file</span></div>
            <div><div class="admin__stat-num"><?= $totalTugas ?></div><div class="admin__stat-label">Tugas Diunggah</div></div>
        </div>
    </div>

    <div class="admin__card">
        <div class="admin__card-title">Kehadiran Saya</div>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Program</th>
                        <th>Sesi Tercatat</th>
                        <th style="text-align:right">Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lunas as $p): ?>
                    <?php $pct = $persenHadir((int) $p['id']); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(ucfirst($p['program'])) ?> <?= htmlspecialchars($p['metode']) ?></strong></td>
                        <td><?= count($hadir[(int) $p['id']] ?? []) ?> sesi</td>
                        <td style="text-align:right"><?= $pct === null ? '<span class="sub">Belum ada sesi</span>' : '<strong>' . $pct . '% hadir</strong>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin__card">
        <div class="admin__card-title">Materi Kursus</div>
        <p style="font-size:13px; color:var(--admin-muted); margin:4px 0 0;">Sesuai program yang Anda ikuti. Klik Unduh untuk menyimpan.</p>

        <?php if (empty($materi)): ?>
            <div class="admin__empty">
                <span class="material-symbols-rounded">folder_open</span>
                Belum ada materi untuk program Anda. Silakan cek kembali nanti.
            </div>
        <?php else: ?>
            <div class="admin__table-wrap">
                <table class="admin__table">
                    <thead>
                        <tr>
                            <th>Materi</th>
                            <th>Program</th>
                            <th>Tanggal</th>
                            <th style="text-align:right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materi as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['judul']) ?></strong></td>
                            <td><?= htmlspecialchars(ucfirst($m['program'])) ?></td>
                            <td><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                            <td style="text-align:right; white-space:nowrap">
                                <a class="admin__btn admin__btn--outline admin__btn--sm" href="/api/unduh-materi.php?id=<?= (int) $m['id'] ?>">
                                    <span class="material-symbols-rounded" style="font-size:16px; vertical-align:-3px;">download</span> Unduh
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../../layouts/akun-footer.php'; ?>
