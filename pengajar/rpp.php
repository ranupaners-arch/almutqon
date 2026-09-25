<?php
// pengajar/rpp.php — Rencana Pelaksanaan Pembelajaran: semua pengajar bisa menambah,
// melihat & mengunduh ide satu sama lain. Hapus: pemilik atau Direktur.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

$pgId = (int) ($_SESSION['pengajar']['id'] ?? 0);
$isDir = is_direktur();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_rpp'])) {
    require_csrf();
    $judul = trim($_POST['judul'] ?? '');
    $program = $_POST['program'] ?? '';
    $materiId = (int) ($_POST['materi_id'] ?? 0);
    $tujuan = trim($_POST['tujuan'] ?? '');
    $kegiatan = trim($_POST['kegiatan'] ?? '');
    $durasi = (int) ($_POST['durasi'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');
    if ($judul === '' || mb_strlen($judul) > 255 || !in_array($program, ['offline', 'online', 'dauroh'], true)
        || mb_strlen($tujuan) > 5000 || mb_strlen($kegiatan) > 10000
        || ($tanggal !== '' && (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) !== 1 || strtotime($tanggal) === false))) {
        flash_error('Isian RPP tidak valid. Periksa judul, program, dan tanggal.');
    } else {
        if ($materiId > 0) {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM materi_kursus WHERE id = ?");
            $cek->execute([$materiId]);
            if ((int) $cek->fetchColumn() === 0) {
                $materiId = 0;
            }
        }
        $ins = $pdo->prepare("INSERT INTO rpp (pengajar_id, materi_id, program, judul, tujuan, kegiatan, durasi, tanggal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$pgId, $materiId > 0 ? $materiId : null, $program, $judul, $tujuan !== '' ? $tujuan : null, $kegiatan !== '' ? $kegiatan : null, $durasi > 0 ? $durasi : null, $tanggal !== '' ? $tanggal : null]);
        flash('RPP "' . mb_strimwidth($judul, 0, 60, '…') . '" tersimpan.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_rpp'])) {
    require_csrf();
    $id = (int) $_POST['hapus_rpp'];
    $stmt = $pdo->prepare("SELECT pengajar_id FROM rpp WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_error('RPP tidak ditemukan.');
    } elseif ((int) $row['pengajar_id'] !== $pgId && !$isDir) {
        flash_error('Hanya pemilik atau Direktur yang boleh menghapus.');
    } else {
        $pdo->prepare("DELETE FROM rpp WHERE id = ?")->execute([$id]);
        flash('RPP dihapus.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$daftar = $pdo->query("SELECT r.*, u.name AS nama_guru, u.staff_id, u.id_pengajar, m.judul AS judul_materi FROM rpp r LEFT JOIN users u ON u.id = r.pengajar_id LEFT JOIN materi_kursus m ON m.id = r.materi_id ORDER BY r.id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
$materi = $pdo->query("SELECT id, judul, program FROM materi_kursus ORDER BY program, judul ASC")->fetchAll(PDO::FETCH_ASSOC);
$lihat = null;
if (isset($_GET['lihat'])) {
    foreach ($daftar as $r) {
        if ((int) $r['id'] === (int) $_GET['lihat']) {
            $lihat = $r;
            break;
        }
    }
}

pesan_flash();

$pengajar_title = 'RPP Materi';
$pengajar_aktif = 'rpp';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<?php if ($lihat): ?>
<div class="admin__card">
    <div class="admin__card-title"><?= htmlspecialchars($lihat['judul']) ?></div>
    <p style="font-size:13px; color:var(--admin-muted); margin:4px 0 12px;">
        <?= htmlspecialchars(ucfirst($lihat['program'])) ?>
        <?= $lihat['judul_materi'] ? ' · Materi: ' . htmlspecialchars($lihat['judul_materi']) : '' ?>
        <?= $lihat['tanggal'] ? ' · ' . date('d M Y', strtotime($lihat['tanggal'])) : '' ?>
        <?= $lihat['durasi'] ? ' · ' . (int) $lihat['durasi'] . ' menit' : '' ?>
        <br>Oleh <?= htmlspecialchars($lihat['nama_guru'] ?? '-') ?> (Staff ID: <?= htmlspecialchars($lihat['staff_id'] ?: $lihat['id_pengajar'] ?: '-') ?>)
    </p>
    <?php if (($lihat['tujuan'] ?? '') !== ''): ?>
        <h4 style="margin:12px 0 4px; font-size:14px;">Tujuan Pembelajaran</h4>
        <p style="font-size:14px; white-space:pre-wrap; margin:0;"><?= htmlspecialchars($lihat['tujuan']) ?></p>
    <?php endif; ?>
    <?php if (($lihat['kegiatan'] ?? '') !== ''): ?>
        <h4 style="margin:12px 0 4px; font-size:14px;">Langkah Kegiatan</h4>
        <p style="font-size:14px; white-space:pre-wrap; margin:0;"><?= htmlspecialchars($lihat['kegiatan']) ?></p>
    <?php endif; ?>
    <div style="margin-top:14px;"><a class="admin__btn admin__btn--outline admin__btn--sm" href="rpp.php">← Kembali ke daftar</a></div>
</div>
<?php endif; ?>

<div class="admin__card">
    <div class="admin__card-title">Tulis RPP Baru</div>
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
        <?= csrf_field() ?>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="rpp_judul">Judul RPP</label>
            <input type="text" id="rpp_judul" name="judul" class="admin__input" required maxlength="255" placeholder="Contoh: RPP Nahwu Bab 3 — Pertemuan 1">
        </div>
        <div style="flex:0 0 150px;">
            <label class="admin__form-label" for="rpp_program">Program</label>
            <select id="rpp_program" name="program" class="admin__input">
                <option value="offline">Offline</option>
                <option value="online">Online</option>
                <option value="dauroh">Dauroh</option>
            </select>
        </div>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="rpp_materi">Terkait Materi (opsional)</label>
            <select id="rpp_materi" name="materi_id" class="admin__input">
                <option value="0">— Umum —</option>
                <?php foreach ($materi as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars($m['judul']) ?> (<?= htmlspecialchars(ucfirst($m['program'])) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:0 0 120px;">
            <label class="admin__form-label" for="rpp_durasi">Durasi (mnt)</label>
            <input type="number" id="rpp_durasi" name="durasi" class="admin__input" min="0" max="1440" placeholder="90">
        </div>
        <div style="flex:0 0 160px;">
            <label class="admin__form-label" for="rpp_tanggal">Tanggal (opsional)</label>
            <input type="date" id="rpp_tanggal" name="tanggal" class="admin__input">
        </div>
        <div style="flex:2; min-width:220px;">
            <label class="admin__form-label" for="rpp_tujuan">Tujuan Pembelajaran</label>
            <input type="text" id="rpp_tujuan" name="tujuan" class="admin__input" maxlength="5000" placeholder="Contoh: Peserta mampu membaca …">
        </div>
        <div style="flex:1 1 100%;">
            <label class="admin__form-label" for="rpp_kegiatan">Langkah Kegiatan</label>
            <textarea id="rpp_kegiatan" name="kegiatan" class="admin__input" rows="3" maxlength="10000" placeholder="1. Pembukaan & doa (10 menit)&#10;2. …"></textarea>
        </div>
        <button type="submit" name="tambah_rpp" value="1" class="admin__btn admin__btn--primary">Simpan RPP</button>
    </form>
</div>

<div class="admin__card">
    <div class="admin__card-title">Daftar RPP</div>

    <?php if (empty($daftar)): ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">edit_note</span>
            Belum ada RPP. Tulis yang pertama lewat formulir di atas.
        </div>
    <?php else: ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>RPP</th>
                        <th>Program</th>
                        <th>Penulis</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['judul']) ?></strong><br><span class="sub"><?= $r['tanggal'] ? date('d M Y', strtotime($r['tanggal'])) . ' · ' : '' ?><?= $r['durasi'] ? (int) $r['durasi'] . ' mnt' : '' ?></span></td>
                        <td><?= htmlspecialchars(ucfirst($r['program'])) ?></td>
                        <td><?= htmlspecialchars($r['nama_guru'] ?? '-') ?></td>
                        <td style="text-align:right; white-space:nowrap">
                            <a class="admin__btn admin__btn--outline admin__btn--sm" href="rpp.php?lihat=<?= (int) $r['id'] ?>">Lihat</a>
                            <?php if ((int) $r['pengajar_id'] === $pgId || $isDir): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus RPP ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="hapus_rpp" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="admin__btn admin__btn--danger-light admin__btn--sm">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
