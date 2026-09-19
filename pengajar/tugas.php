<?php
// pengajar/tugas.php — tugas masuk untuk dinilai: yang ditujukan ke pengajar ini
// (+ yang tanpa tujuan, untuk kompatibilitas). Direktur melihat semuanya.
// Pengajar dapat mengubah status review; penilai & waktu tercatat (pemantauan Direktur).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pengajar.php';

$pgId = (int) ($_SESSION['pengajar']['id'] ?? 0);
$pgNama = (string) ($_SESSION['pengajar']['name'] ?? 'Pengajar');
$isDir = is_direktur();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nilai_tugas'])) {
    require_csrf();
    $id = (int) $_POST['nilai_tugas'];
    $status = $_POST['status_review'] ?? '';
    $nilai = trim($_POST['nilai'] ?? '');
    $nilaiValid = nilai_huruf_options();
    if (!in_array($status, ['pending', 'acc', 'revisi'], true) || ($nilai !== '' && !in_array($nilai, $nilaiValid, true))) {
        flash_error('Status review / nilai tidak valid.');
    } else {
        // Boleh menilai bila direktur, atau tugas ditujukan kepadanya / tanpa tujuan.
        $stmt = $pdo->prepare("SELECT id FROM jurnal_peserta WHERE id = ?" . ($isDir ? '' : " AND (pengajar_id = ? OR pengajar_id IS NULL)"));
        $stmt->execute($isDir ? [$id] : [$id, $pgId]);
        if (!$stmt->fetch()) {
            flash_error('Tugas tidak ditemukan atau bukan wewenang Anda.');
        } else {
            $oleh = $pgNama . ' (' . (($_SESSION['pengajar']['id_pengajar'] ?? '-')) . ')';
            $up = $pdo->prepare("UPDATE jurnal_peserta SET status_review = ?, nilai = ?, dinilai_oleh = ?, dinilai_pada = NOW() WHERE id = ?");
            $up->execute([$status, $nilai !== '' ? $nilai : null, mb_strimwidth($oleh, 0, 100, ''), $id]);
            flash("Tugas ditandai {$status}" . ($nilai !== '' ? " dengan nilai {$nilai}" : '') . '.');
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Hapus untuk diri sendiri (user & admin tetap melihat; file dipertahankan).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_tugas'])) {
    require_csrf();
    $id = (int) $_POST['hapus_tugas'];
    $stmt = $pdo->prepare("SELECT id FROM jurnal_peserta WHERE id = ? AND hapus_pengajar = 0" . ($isDir ? '' : " AND (pengajar_id = ? OR pengajar_id IS NULL)"));
    $stmt->execute($isDir ? [$id] : [$id, $pgId]);
    if (!$stmt->fetch()) {
        flash_error('Tugas tidak ditemukan atau bukan wewenang Anda.');
    } else {
        $pdo->prepare("UPDATE jurnal_peserta SET hapus_pengajar = 1 WHERE id = ?")->execute([$id]);
        flash('Tugas dihapus dari daftar Anda.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($isDir) {
    $tugas = $pdo->query("SELECT j.*, p.nama AS nama_peserta, p.program AS program_peserta, u.name AS nama_guru FROM jurnal_peserta j LEFT JOIN pendaftar p ON p.id = j.pendaftar_id LEFT JOIN users u ON u.id = j.pengajar_id WHERE j.hapus_pengajar = 0 ORDER BY j.id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT j.*, p.nama AS nama_peserta, p.program AS program_peserta FROM jurnal_peserta j LEFT JOIN pendaftar p ON p.id = j.pendaftar_id WHERE (j.pengajar_id = ? OR j.pengajar_id IS NULL) AND j.hapus_pengajar = 0 ORDER BY j.id DESC LIMIT 200");
    $stmt->execute([$pgId]);
    $tugas = $stmt->fetchAll();
}

pesan_flash();

$pengajar_title = 'Tugas Masuk';
$pengajar_aktif = 'tugas';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__card">
    <div class="admin__card-title">Tugas Masuk<?= $isDir ? '' : ' — Untuk Anda' ?></div>

    <?php if (empty($tugas)): ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">assignment</span>
            Belum ada tugas dari peserta.
        </div>
    <?php else: ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Tugas</th>
                        <th>Peserta</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tugas as $t): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($t['judul_jurnal']) ?></strong><br><span class="sub"><?= htmlspecialchars(ucfirst((string) ($t['program_peserta'] ?? ''))) ?> · <?= date('d M Y', strtotime($t['diunggah_pada'])) ?></span></td>
                        <td><?= htmlspecialchars($t['nama_peserta'] ?? '-') ?><br><span class="sub"><?= htmlspecialchars($t['user_email']) ?></span></td>
                        <td><span class="admin__badge admin__badge--<?= $t['status_review'] === 'acc' ? 'green' : ($t['status_review'] === 'revisi' ? 'red' : 'amber') ?>"><?= $t['status_review'] === 'acc' ? 'ACC' : ($t['status_review'] === 'revisi' ? 'Revisi' : 'Pending') ?></span><?php if (($t['dinilai_oleh'] ?? '') !== ''): ?><br><span class="sub">oleh <?= htmlspecialchars(nama_tanpa_id($t['dinilai_oleh'])) ?></span><?php endif; ?></td>
                        <td><?= ($t['nilai'] ?? '') !== '' ? '<strong style="font-size:16px;">' . htmlspecialchars($t['nilai']) . '</strong>' : '<span class="sub">—</span>' ?></td>
                        <td style="text-align:right; white-space:nowrap">
                            <a class="admin__btn admin__btn--primary admin__btn--sm" href="/api/unduh-jurnal.php?id=<?= (int) $t['id'] ?>">
                                <span class="material-symbols-rounded" style="font-size:16px; vertical-align:-3px;">download</span> Unduh
                            </a>
                            <form method="POST" style="display:inline-flex; gap:6px; align-items:center" onsubmit="return confirm('Ubah status review & nilai tugas ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="nilai_tugas" value="<?= (int) $t['id'] ?>">
                                <select name="status_review" class="admin__select-sm" aria-label="Status review">
                                    <option value="pending" <?= $t['status_review'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="acc" <?= $t['status_review'] === 'acc' ? 'selected' : '' ?>>ACC</option>
                                    <option value="revisi" <?= $t['status_review'] === 'revisi' ? 'selected' : '' ?>>Revisi</option>
                                </select>
                                <select name="nilai" class="admin__select-sm" aria-label="Nilai huruf">
                                    <option value="">—</option>
                                    <?php foreach (nilai_huruf_options() as $nh): ?>
                                        <option value="<?= $nh ?>" <?= ($t['nilai'] ?? '') === $nh ? 'selected' : '' ?>><?= $nh ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="admin__btn admin__btn--green admin__btn--sm">Nilai</button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus tugas ini dari daftar Anda? Peserta tetap bisa melihatnya.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hapus_tugas" value="<?= (int) $t['id'] ?>">
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

<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
