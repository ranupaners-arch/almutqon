<?php
// admin/data-agenda.php — kelola agenda kegiatan (kalender): tampil di dashboard user
// sesuai cakupan (global = semua user; offline/online/dauroh = peserta LUNAS program tsb).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_agenda'])) {
    require_csrf();
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');
    $jamMulai = trim($_POST['jam_mulai'] ?? '');
    $jamSelesai = trim($_POST['jam_selesai'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $cakupan = $_POST['cakupan'] ?? '';

    $tglOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) === 1 && strtotime($tanggal) !== false;
    $jamOk = ($jamMulai === '' || preg_match('/^\d{2}:\d{2}$/', $jamMulai) === 1)
        && ($jamSelesai === '' || preg_match('/^\d{2}:\d{2}$/', $jamSelesai) === 1);
    if ($judul === '' || mb_strlen($judul) > 255 || !$tglOk || !$jamOk
        || !in_array($cakupan, ['global', 'offline', 'online', 'dauroh'], true)
        || mb_strlen($lokasi) > 255 || mb_strlen($deskripsi) > 5000) {
        flash_error('Isian agenda tidak valid. Periksa judul, tanggal, jam, dan cakupan.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO agenda (judul, deskripsi, tanggal, jam_mulai, jam_selesai, lokasi, cakupan) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $judul,
            $deskripsi !== '' ? $deskripsi : null,
            $tanggal,
            $jamMulai !== '' ? $jamMulai : null,
            $jamSelesai !== '' ? $jamSelesai : null,
            $lokasi,
            $cakupan,
        ]);
        flash('Agenda "' . mb_strimwidth($judul, 0, 60, '…') . '" ditambahkan.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_agenda'])) {
    require_csrf();
    $id = (int) $_POST['hapus_agenda'];
    $del = $pdo->prepare("DELETE FROM agenda WHERE id = ?");
    $del->execute([$id]);
    flash($del->rowCount() > 0 ? 'Agenda dihapus.' : 'Agenda tidak ditemukan.');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$agenda = $pdo->query("SELECT * FROM agenda ORDER BY tanggal ASC, jam_mulai ASC")->fetchAll(PDO::FETCH_ASSOC);

pesan_flash();
$totalMendatang = count(array_filter($agenda, fn($a) => $a['tanggal'] >= date('Y-m-d')));

$cakupanBadge = ['global' => 'blue', 'offline' => 'green', 'online' => 'amber', 'dauroh' => 'red'];
$cakupanLabel = ['global' => 'Global', 'offline' => 'Offline', 'online' => 'Online', 'dauroh' => 'Dauroh'];

$admin_title = 'Agenda Kegiatan';
$admin_aktif = 'agenda';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">calendar_month</span></div>
        <div><div class="admin__stat-num"><?= count($agenda) ?></div><div class="admin__stat-label">Total Agenda</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">event_upcoming</span></div>
        <div><div class="admin__stat-num"><?= $totalMendatang ?></div><div class="admin__stat-label">Mendatang</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">Tambah Agenda</div>
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
        <?= csrf_field() ?>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="ag_judul">Judul Agenda</label>
            <input type="text" id="ag_judul" name="judul" class="admin__input" required maxlength="255" placeholder="Contoh: Dauroh Akbar &amp; Buka Bersama">
        </div>
        <div style="flex:0 0 160px;">
            <label class="admin__form-label" for="ag_tanggal">Tanggal</label>
            <input type="date" id="ag_tanggal" name="tanggal" class="admin__input" required>
        </div>
        <div style="flex:0 0 120px;">
            <label class="admin__form-label" for="ag_mulai">Jam Mulai</label>
            <input type="time" id="ag_mulai" name="jam_mulai" class="admin__input">
        </div>
        <div style="flex:0 0 120px;">
            <label class="admin__form-label" for="ag_selesai">Jam Selesai</label>
            <input type="time" id="ag_selesai" name="jam_selesai" class="admin__input">
        </div>
        <div style="flex:0 0 160px;">
            <label class="admin__form-label" for="ag_cakupan">Cakupan</label>
            <select id="ag_cakupan" name="cakupan" class="admin__input">
                <option value="global">Global (semua user)</option>
                <option value="offline">Offline</option>
                <option value="online">Online</option>
                <option value="dauroh">Dauroh</option>
            </select>
        </div>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="ag_lokasi">Lokasi / Link</label>
            <input type="text" id="ag_lokasi" name="lokasi" class="admin__input" maxlength="255" placeholder="Contoh: Masjid Al-Mutqon / link GMeet">
        </div>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="ag_deskripsi">Keterangan (opsional)</label>
            <input type="text" id="ag_deskripsi" name="deskripsi" class="admin__input" maxlength="5000" placeholder="Contoh: Bawa alat tulis &amp; Al-Qur'an">
        </div>
        <button type="submit" name="tambah_agenda" value="1" class="admin__btn admin__btn--primary">Tambah</button>
    </form>
</div>

<div class="admin__card">
    <div class="admin__card-title">Daftar Agenda</div>

    <?php if (count($agenda) > 0): ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Agenda</th>
                        <th>Cakupan</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agenda as $a): ?>
                    <tr>
                        <td style="white-space:nowrap"><strong><?= date('d M Y', strtotime($a['tanggal'])) ?></strong><br><span class="sub"><?= ($a['jam_mulai'] ? substr($a['jam_mulai'], 0, 5) : '') . ($a['jam_selesai'] ? '–' . substr($a['jam_selesai'], 0, 5) : '') ?></span></td>
                        <td><strong><?= htmlspecialchars($a['judul']) ?></strong><?php if (($a['lokasi'] ?? '') !== ''): ?><br><span class="sub"><?= htmlspecialchars($a['lokasi']) ?></span><?php endif; ?><?php if (($a['deskripsi'] ?? '') !== ''): ?><br><span class="sub"><?= htmlspecialchars(mb_strimwidth($a['deskripsi'], 0, 120, '…')) ?></span><?php endif; ?></td>
                        <td><span class="admin__badge admin__badge--<?= $cakupanBadge[$a['cakupan']] ?? 'gray' ?>"><?= $cakupanLabel[$a['cakupan']] ?? $a['cakupan'] ?></span></td>
                        <td style="text-align:right; white-space:nowrap">
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus agenda <?= htmlspecialchars($a['judul'], ENT_QUOTES) ?>?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hapus_agenda" value="<?= (int) $a['id'] ?>">
                                <button type="submit" class="admin__btn admin__btn--danger-light admin__btn--sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">calendar_month</span>
            Belum ada agenda. Tambahkan lewat formulir di atas.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
