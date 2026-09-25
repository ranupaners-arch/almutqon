<?php
// pages/akun/riwayat-tugas.php — dashboard peserta: riwayat tugas + status review.
// Wajib login (guard di layouts/akun-header.php).
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/csrf.php';

$email = (string) ($_SESSION['user']['email'] ?? '');

// Hapus untuk diri sendiri (pengajar & admin tetap melihat; file dipertahankan).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_tugas'])) {
    require_csrf();
    $id = (int) $_POST['hapus_tugas'];
    $stmt = $pdo->prepare("UPDATE jurnal_peserta SET hapus_user = 1 WHERE id = ? AND user_email = ? AND hapus_user = 0");
    $stmt->execute([$id, $email]);
    if ($stmt->rowCount() > 0) {
        flash('Tugas dihapus dari riwayat Anda.');
    } else {
        flash_error('Tugas tidak ditemukan.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pendaftar WHERE (user_id = ? OR (user_id IS NULL AND user_email = ?)) AND status_pembayaran = 'lunas'");
$stmt->execute([$userId, $email]);
$programAktif = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT j.id, j.judul_jurnal, j.status_review, j.nilai, j.diunggah_pada, j.dinilai_oleh, p.program, p.metode, p.enrollment_id, u.name AS nama_guru, u.staff_id, u.id_pengajar FROM jurnal_peserta j LEFT JOIN pendaftar p ON p.id = j.pendaftar_id LEFT JOIN users u ON u.id = j.pengajar_id WHERE j.user_email = ? AND j.hapus_user = 0 ORDER BY j.id DESC");
$stmt->execute([$email]);
$riwayat = $stmt->fetchAll();

$totalAcc = count(array_filter($riwayat, fn($j) => $j['status_review'] === 'acc'));

pesan_flash();

$akun_title = 'Riwayat Tugas Saya';
$akun_aktif = 'riwayat';
include __DIR__ . '/../../layouts/akun-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<?php if ($programAktif === 0): ?>
    <div class="admin__card" style="text-align:center; padding:48px 24px;">
        <span class="material-symbols-rounded" style="font-size:40px; color:#CBD5E1;">lock</span>
        <p style="font-size:16px; font-weight:800; margin:12px 0 6px;">Halaman khusus peserta aktif</p>
        <p style="font-size:14px; color:var(--admin-muted); margin:0 0 20px;">Selesaikan pembayaran program Anda terlebih dahulu untuk melihat riwayat tugas.</p>
        <a class="admin__btn admin__btn--primary" href="/pages/akun/pendaftaran-saya.php">Lihat Pendaftaran Saya</a>
    </div>
<?php else: ?>

    <div class="admin__stats">
        <div class="admin__stat">
            <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">upload_file</span></div>
            <div><div class="admin__stat-num"><?= count($riwayat) ?></div><div class="admin__stat-label">Tugas Diunggah</div></div>
        </div>
        <div class="admin__stat">
            <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">verified</span></div>
            <div><div class="admin__stat-num"><?= $totalAcc ?></div><div class="admin__stat-label">Disetujui (ACC)</div></div>
        </div>
    </div>

    <div class="admin__card">
        <div class="admin__card-title">Riwayat Tugas Saya</div>
        <p style="font-size:13px; color:var(--admin-muted); margin:4px 0 0;">Pantau status review dari tim kami.</p>

        <?php if (empty($riwayat)): ?>
            <div class="admin__empty">
                <span class="material-symbols-rounded">history</span>
                Belum ada tugas yang diunggah.
            </div>
        <?php else: ?>
            <div class="admin__table-wrap">
                <table class="admin__table">
                    <thead>
                        <tr>
                            <th>Tugas</th>
                            <th>Program</th>
                            <th>Dikirim ke</th>
                            <th>Status</th>
                            <th>Nilai</th>
                            <th>Diunggah</th>
                            <th style="text-align:right">File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $j): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($j['judul_jurnal']) ?></strong></td>
                            <td><?= htmlspecialchars(ucfirst((string) ($j['program'] ?? ''))) ?></td>
                            <td><?= $j['nama_guru'] ? htmlspecialchars($j['nama_guru']) : '<span class="sub">—</span>' ?></td>
                            <td>
                                <span class="admin__badge admin__badge--<?= $j['status_review'] === 'acc' ? 'green' : ($j['status_review'] === 'revisi' ? 'red' : 'amber') ?>">
                                <?= $j['status_review'] === 'acc' ? 'ACC' : ($j['status_review'] === 'revisi' ? 'Perlu Revisi' : 'Terkirim') ?>
                                </span>
                            </td>
                            <td><?= ($j['nilai'] ?? '') !== '' ? '<strong style="font-size:16px;">' . htmlspecialchars($j['nilai']) . '</strong>' : '<span class="sub">—</span>' ?></td>
                            <td><?= date('d M Y, H:i', strtotime($j['diunggah_pada'])) ?></td>
                            <td style="text-align:right; white-space:nowrap">
                                <a class="admin__btn admin__btn--outline admin__btn--sm" href="/api/unduh-jurnal.php?id=<?= (int) $j['id'] ?>">
                                    <span class="material-symbols-rounded" style="font-size:16px; vertical-align:-3px;">download</span> File
                                </a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus tugas ini dari riwayat Anda? Pengajar tetap bisa melihatnya.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="hapus_tugas" value="<?= (int) $j['id'] ?>">
                                    <button type="submit" class="admin__btn admin__btn--danger-light admin__btn--sm">Hapus</button>
                                </form>
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
