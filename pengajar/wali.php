<?php
// pengajar/wali.php — perwalian peserta: privat (1 pengajar : 1 peserta),
// kelompok non-privat (1 pengajar : 1 program), dauroh (gabungan beberapa pengajar).
// Semua pengajar bisa melihat; penunjukan & pencabutan khusus Direktur.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pengajar.php';

$isDir = is_direktur();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tunjuk_wali'])) {
    if (!$isDir) {
        http_response_code(403);
        exit('Akses khusus Direktur.');
    }
    require_csrf();
    $pengajarId = (int) ($_POST['pengajar_id'] ?? 0);
    $tipe = $_POST['tipe'] ?? '';
    $pendaftarId = (int) ($_POST['pendaftar_id'] ?? 0);
    $program = $_POST['program'] ?? '';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND peran != 'user' AND aktif = 1 LIMIT 1");
    $stmt->execute([$pengajarId]);
    $ok = (bool) $stmt->fetch() && in_array($tipe, ['privat', 'kelompok', 'dauroh'], true);
    if ($ok && $tipe === 'privat') {
        $stmt = $pdo->prepare("SELECT id FROM pendaftar WHERE id = ? AND status_pembayaran = 'lunas' LIMIT 1");
        $stmt->execute([$pendaftarId]);
        $ok = (bool) $stmt->fetch();
    }
    if ($ok && $tipe !== 'privat') {
        $ok = in_array($program, ['offline', 'online', 'dauroh'], true);
        $pendaftarId = 0;
    }
    if (!$ok) {
        flash_error('Penunjukan tidak valid. Periksa pengajar, tipe, dan target.');
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM perwalian WHERE pengajar_id = ? AND tipe = ? AND " . ($tipe === 'privat' ? "pendaftar_id = ?" : "program = ? AND pendaftar_id IS NULL"));
        $stmt->execute($tipe === 'privat' ? [$pengajarId, $tipe, $pendaftarId] : [$pengajarId, $tipe, $program]);
        if ((int) $stmt->fetchColumn() > 0) {
            flash_error('Penunjukan tersebut sudah ada.');
        } else {
            $ins = $pdo->prepare("INSERT INTO perwalian (pengajar_id, pendaftar_id, program, tipe) VALUES (?, ?, ?, ?)");
            $ins->execute([$pengajarId, $tipe === 'privat' ? $pendaftarId : null, $tipe === 'privat' ? null : $program, $tipe]);
            flash('Wali ditunjuk.');
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cabut_wali'])) {
    if (!$isDir) {
        http_response_code(403);
        exit('Akses khusus Direktur.');
    }
    require_csrf();
    $del = $pdo->prepare("DELETE FROM perwalian WHERE id = ?");
    $del->execute([(int) $_POST['cabut_wali']]);
    flash($del->rowCount() > 0 ? 'Penunjukan wali dicabut.' : 'Data tidak ditemukan.');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$wali = $pdo->query("SELECT w.*, u.name AS nama_guru, u.staff_id, u.id_pengajar, p.nama AS nama_peserta, p.enrollment_id FROM perwalian w LEFT JOIN users u ON u.id = w.pengajar_id LEFT JOIN pendaftar p ON p.id = w.pendaftar_id ORDER BY w.tipe, w.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$guru = $pdo->query("SELECT id, name, staff_id, id_pengajar, peran FROM users WHERE peran != 'user' AND aktif = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$peserta = $pdo->query("SELECT id, enrollment_id, nama, program, metode FROM pendaftar WHERE status_pembayaran = 'lunas' ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

$tipeLabel = ['privat' => 'Wali Privat', 'kelompok' => 'Wali Kelompok', 'dauroh' => 'Wali Dauroh'];

pesan_flash();

$pengajar_title = 'Perwalian';
$pengajar_aktif = 'wali';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<?php if ($isDir): ?>
<div class="admin__card">
    <div class="admin__card-title">Tunjuk Wali</div>
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
        <?= csrf_field() ?>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="w_guru">Pengajar</label>
            <select id="w_guru" name="pengajar_id" class="admin__input" required>
                <?php foreach ($guru as $g): ?>
                    <option value="<?= (int) $g['id'] ?>"><?= htmlspecialchars($g['name']) ?> (<?= htmlspecialchars($g['staff_id'] ?: $g['id_pengajar'] ?: '-') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:0 0 170px;">
            <label class="admin__form-label" for="w_tipe">Tipe Wali</label>
            <select id="w_tipe" name="tipe" class="admin__input" required>
                <option value="privat">Privat (per peserta)</option>
                <option value="kelompok">Kelompok (per program)</option>
                <option value="dauroh">Dauroh (gabungan)</option>
            </select>
        </div>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="w_peserta">Peserta (khusus privat)</label>
            <select id="w_peserta" name="pendaftar_id" class="admin__input">
                <option value="0">—</option>
                <?php foreach ($peserta as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['enrollment_id'] ?: '-') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:0 0 150px;">
            <label class="admin__form-label" for="w_program">Program (kelompok/dauroh)</label>
            <select id="w_program" name="program" class="admin__input">
                <option value="offline">Offline</option>
                <option value="online">Online</option>
                <option value="dauroh">Dauroh</option>
            </select>
        </div>
        <button type="submit" name="tunjuk_wali" value="1" class="admin__btn admin__btn--primary">Tunjuk</button>
    </form>
</div>
<?php endif; ?>

<div class="admin__card">
    <div class="admin__card-title">Daftar Perwalian</div>

    <?php if (empty($wali)): ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">group</span>
            Belum ada penunjukan wali.
        </div>
    <?php else: ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Pengajar</th>
                        <th>Tipe</th>
                        <th>Target</th>
                        <?php if ($isDir): ?><th style="text-align:right">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wali as $w): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($w['nama_guru'] ?? '-') ?></strong><br><span class="sub">Staff ID: <?= htmlspecialchars($w['staff_id'] ?: $w['id_pengajar'] ?: '') ?></span></td>
                        <td><span class="admin__badge admin__badge--blue"><?= $tipeLabel[$w['tipe']] ?? $w['tipe'] ?></span></td>
                        <td><?= $w['tipe'] === 'privat' ? htmlspecialchars(($w['nama_peserta'] ?? '-') . ' (' . ($w['enrollment_id'] ?: '-') . ')') : htmlspecialchars(ucfirst((string) $w['program'])) ?></td>
                        <?php if ($isDir): ?>
                        <td style="text-align:right; white-space:nowrap">
                            <form method="POST" style="display:inline" onsubmit="return confirm('Cabut penunjukan ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="cabut_wali" value="<?= (int) $w['id'] ?>">
                                <button type="submit" class="admin__btn admin__btn--danger-light admin__btn--sm">Cabut</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
