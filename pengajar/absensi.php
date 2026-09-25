<?php
// pengajar/absensi.php — catat kehadiran peserta LUNAS per program & tanggal.
// Satu peserta satu baris per tanggal (upsert). Pencatat tercatat (pemantauan Direktur).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

$program = $_GET['program'] ?? $_POST['program'] ?? 'offline';
if (!in_array($program, ['offline', 'online', 'dauroh'], true)) {
    $program = 'offline';
}
$tanggal = $_GET['tanggal'] ?? $_POST['tanggal'] ?? date('Y-m-d');
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) !== 1 || strtotime($tanggal) === false) {
    $tanggal = date('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_absensi'])) {
    require_csrf();
    $hadir = $_POST['hadir'] ?? [];
    $oleh = (($_SESSION['pengajar']['name'] ?? 'Pengajar')) . ' (' . (($_SESSION['pengajar']['staff_id'] ?? $_SESSION['pengajar']['id_pengajar'] ?? '-')) . ')';
    $oleh = mb_strimwidth($oleh, 0, 100, '');
    $valid = ['hadir', 'izin', 'sakit', 'alpa'];
    $n = 0;
    // Hanya peserta LUNAS program tsb yang boleh dicatat (cegah ID siluman).
    $stmt = $pdo->prepare("SELECT id FROM pendaftar WHERE program = ? AND status_pembayaran = 'lunas'");
    $stmt->execute([$program]);
    $boleh = array_flip(array_column($stmt->fetchAll(), 'id'));
    $up = $pdo->prepare("INSERT INTO absensi (pendaftar_id, tanggal, status, dicatat_oleh) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), dicatat_oleh = VALUES(dicatat_oleh)");
    foreach ($hadir as $pid => $st) {
        $pid = (int) $pid;
        if (!isset($boleh[$pid]) || !in_array($st, $valid, true)) {
            continue;
        }
        $up->execute([$pid, $tanggal, $st, $oleh]);
        $n++;
    }
    flash("Absensi {$tanggal} tersimpan ({$n} peserta).");
    header('Location: absensi.php?program=' . $program . '&tanggal=' . $tanggal);
    exit;
}

$stmt = $pdo->prepare("SELECT id, enrollment_id, nama FROM pendaftar WHERE program = ? AND status_pembayaran = 'lunas' ORDER BY nama ASC");
$stmt->execute([$program]);
$peserta = $stmt->fetchAll();

$isi = [];
if (count($peserta) > 0) {
    $stmt = $pdo->prepare("SELECT pendaftar_id, status FROM absensi WHERE tanggal = ? AND pendaftar_id IN (SELECT id FROM pendaftar WHERE program = ?)");
    $stmt->execute([$tanggal, $program]);
    $isi = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

pesan_flash();

$pengajar_title = 'Absensi';
$pengajar_aktif = 'absensi';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__card">
    <div class="admin__card-title">Absensi Kehadiran</div>
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
        <div style="flex:0 0 170px;">
            <label class="admin__form-label" for="f_program">Program</label>
            <select id="f_program" name="program" class="admin__input" onchange="this.form.submit()">
                <?php foreach (['offline' => 'Offline', 'online' => 'Online', 'dauroh' => 'Dauroh'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $program === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:0 0 170px;">
            <label class="admin__form-label" for="f_tanggal">Tanggal</label>
            <input type="date" id="f_tanggal" name="tanggal" class="admin__input" value="<?= htmlspecialchars($tanggal) ?>" onchange="this.form.submit()">
        </div>
    </form>
</div>

<div class="admin__card">
    <div class="admin__card-title">Peserta <?= htmlspecialchars(ucfirst($program)) ?> — <?= date('d M Y', strtotime($tanggal)) ?></div>

    <?php if (empty($peserta)): ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">groups</span>
            Belum ada peserta lunas pada program ini.
        </div>
    <?php else: ?>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="program" value="<?= htmlspecialchars($program) ?>">
            <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
            <div class="admin__table-wrap">
                <table class="admin__table">
                    <thead>
                        <tr>
                            <th>Nama Peserta</th>
                            <th style="text-align:center">Hadir</th>
                            <th style="text-align:center">Izin</th>
                            <th style="text-align:center">Sakit</th>
                            <th style="text-align:center">Alpa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peserta as $p): ?>
                        <?php $st = $isi[$p['id']] ?? 'hadir'; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['nama']) ?></strong><br><span class="sub">Enrollment ID: <code><?= htmlspecialchars($p['enrollment_id'] ?: '-') ?></code></span></td>
                            <?php foreach (['hadir' => 'H', 'izin' => 'I', 'sakit' => 'S', 'alpa' => 'A'] as $val => $singkat): ?>
                                <td style="text-align:center" title="<?= ucfirst($val) ?>">
                                    <input type="radio" name="hadir[<?= (int) $p['id'] ?>]" value="<?= $val ?>" <?= $st === $val ? 'checked' : '' ?> aria-label="<?= ucfirst($val) ?> untuk <?= htmlspecialchars($p['nama']) ?>" style="width:18px; height:18px; accent-color:#166534;">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" name="simpan_absensi" value="1" class="admin__btn admin__btn--primary" style="margin-top:14px;">Simpan Absensi</button>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
