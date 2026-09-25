<?php
// pengajar/sdm.php — KHUSUS DIREKTUR: kontroling SDM + pemantauan kinerja.
// Revoke/aktifkan akses di sini; tambah/hapus/reset password tetap wewenang admin.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pengajar.php';

require_direktur();

$selfId = (int) ($_SESSION['pengajar']['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_aktif'])) {
    require_csrf();
    $id = (int) $_POST['toggle_aktif'];
    if ($id === $selfId) {
        flash_error('Tidak bisa menonaktifkan akun sendiri.');
    } else {
        $stmt = $pdo->prepare("SELECT aktif, name FROM users WHERE id = ? AND peran != 'user' LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_error('Data tidak ditemukan.');
        } else {
            $baru = ((int) $row['aktif'] === 1) ? 0 : 1;
            $pdo->prepare("UPDATE users SET aktif = ? WHERE id = ?")->execute([$baru, $id]);
            flash($baru === 1 ? "Akses {$row['name']} diaktifkan kembali." : "Akses {$row['name']} dicabut (revoke).");
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Pemantauan kinerja (agregat): sebaran status tugas, materi per program, agenda mendatang.
$kinTugas = $pdo->query("SELECT status_review, COUNT(*) c FROM jurnal_peserta GROUP BY status_review")->fetchAll(PDO::FETCH_KEY_PAIR);
$kinMateri = $pdo->query("SELECT program, COUNT(*) c FROM materi_kursus GROUP BY program")->fetchAll(PDO::FETCH_KEY_PAIR);
$kinAgenda = (int) $pdo->query("SELECT COUNT(*) FROM agenda WHERE tanggal >= CURDATE()")->fetchColumn();
$kinPendaftar = $pdo->query("SELECT status_pembayaran, COUNT(*) c FROM pendaftar GROUP BY status_pembayaran")->fetchAll(PDO::FETCH_KEY_PAIR);

$sdm = $pdo->query("SELECT id, staff_id, name, email, peran, id_pengajar, aktif FROM users WHERE peran != 'user' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Tugas yang dinilai per pengajar (kunci = format yang ditulis saat menilai: "Nama (ID)").
$dinilai = $pdo->query("SELECT dinilai_oleh, COUNT(*) c FROM jurnal_peserta WHERE dinilai_oleh IS NOT NULL GROUP BY dinilai_oleh")->fetchAll(PDO::FETCH_KEY_PAIR);

pesan_flash();

$badgePeran = ['direktur' => 'red', 'pengajar' => 'blue'];

$pengajar_title = 'SDM & Kinerja';
$pengajar_aktif = 'sdm';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">pending_actions</span></div>
        <div><div class="admin__stat-num"><?= (int) ($kinTugas['pending'] ?? 0) ?></div><div class="admin__stat-label">Menunggu Penilaian</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">verified</span></div>
        <div><div class="admin__stat-num"><?= (int) ($kinTugas['acc'] ?? 0) ?></div><div class="admin__stat-label">Tugas ACC</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--blue"><span class="material-symbols-rounded">folder_open</span></div>
        <div><div class="admin__stat-num"><?= array_sum($kinMateri) ?></div><div class="admin__stat-label">Materi (<?= count($kinMateri) ?> program)</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">calendar_month</span></div>
        <div><div class="admin__stat-num"><?= $kinAgenda ?></div><div class="admin__stat-label">Agenda Mendatang</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--slate"><span class="material-symbols-rounded">payments</span></div>
        <div><div class="admin__stat-num"><?= (int) ($kinPendaftar['lunas'] ?? 0) ?>/<?= array_sum($kinPendaftar) ?></div><div class="admin__stat-label">Pendaftar Lunas/Total</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">Kontroling SDM</div>

    <?php if (empty($sdm)): ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">supervisor_account</span>
            Belum ada tenaga pengajar.
        </div>
    <?php else: ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama / Email</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th>Tugas Dinilai</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sdm as $p): ?>
                    <?php $aktif = (int) $p['aktif'] === 1; ?>
                    <tr>
                        <td><code><?= htmlspecialchars($p['staff_id'] ?: '-') ?></code></td>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong><br><span class="sub"><?= htmlspecialchars($p['email']) ?></span></td>
                        <td><span class="admin__badge admin__badge--<?= $badgePeran[$p['peran']] ?? 'amber' ?>"><?= htmlspecialchars(peran_label($p['peran'])) ?></span></td>
                        <td><span class="admin__badge admin__badge--<?= $aktif ? 'green' : 'gray' ?>"><?= $aktif ? 'Aktif' : 'Dicabut' ?></span></td>
                        <td><strong><?= (int) ($dinilai[$p['name'] . ' (' . ($p['id_pengajar'] ?: '-') . ')'] ?? 0) ?></strong></td>
                        <td style="text-align:right; white-space:nowrap">
                            <?php if ((int) $p['id'] === $selfId): ?>
                                <span class="sub">Anda</span>
                            <?php else: ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('<?= $aktif ? 'Cabut' : 'Aktifkan kembali' ?> akses <?= htmlspecialchars(addslashes($p['name'])) ?>?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="toggle_aktif" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="admin__btn <?= $aktif ? 'admin__btn--danger-light' : 'admin__btn--green' ?> admin__btn--sm"><?= $aktif ? 'Revoke' : 'Aktifkan' ?></button>
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
