<?php
// admin/data-jurnal.php — kelola materi kursus & nilai tugas peserta.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pengajar.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_materi'])) {
    require_csrf();
    $judul = trim($_POST['judul_materi'] ?? '');
    $program = $_POST['program_materi'] ?? '';
    if ($judul === '' || mb_strlen($judul) > 255 || !in_array($program, ['offline', 'online', 'dauroh'], true)) {
        flash_error('Judul (maks 255 karakter) dan program materi wajib valid.');
    } elseif (!isset($_FILES['file_materi']) || $_FILES['file_materi']['error'] !== UPLOAD_ERR_OK) {
        flash_error('File materi wajib diunggah.');
    } elseif ($_FILES['file_materi']['size'] > 10 * 1024 * 1024) {
        flash_error('Ukuran file materi maksimal 10MB.');
    } else {
        $ext = strtolower(pathinfo($_FILES['file_materi']['name'], PATHINFO_EXTENSION));
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['file_materi']['tmp_name']);
        $okExt = in_array($ext, ['pdf', 'doc', 'docx'], true);
        $okMime = in_array($mime, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true);
        if (!$okExt || !$okMime) {
            flash_error('File materi harus PDF, DOC, atau DOCX.');
        } else {
            $dir = __DIR__ . '/../assets/materi/';
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $newName = 'materi_' . uniqid('', true) . '.' . $ext;
            if (!move_uploaded_file($_FILES['file_materi']['tmp_name'], $dir . $newName)) {
                flash_error('Gagal menyimpan file materi.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO materi_kursus (program, judul, path_file) VALUES (?, ?, ?)");
                $stmt->execute([$program, $judul, 'assets/materi/' . $newName]);
                flash('Materi "' . mb_strimwidth($judul, 0, 60, '…') . '" ditambahkan.');
            }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_materi'])) {
    require_csrf();
    $id = (int) $_POST['hapus_materi'];
    $stmt = $pdo->prepare("SELECT path_file FROM materi_kursus WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_error('Materi tidak ditemukan.');
    } else {
        $pdo->prepare("DELETE FROM materi_kursus WHERE id = ?")->execute([$id]);
        $f = realpath(__DIR__ . '/../' . $row['path_file']);
        $base = realpath(__DIR__ . '/../assets/materi');
        if ($f && $base && strpos($f, $base) === 0 && is_file($f)) {
            @unlink($f);
        }
        flash('Materi dihapus.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$materi = $pdo->query("SELECT * FROM materi_kursus ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$jurnal = $pdo->query("SELECT j.*, p.nama AS nama_peserta, p.program AS program_peserta, p.enrollment_id, u.name AS nama_guru, u.staff_id, u.id_pengajar FROM jurnal_peserta j LEFT JOIN pendaftar p ON p.id = j.pendaftar_id LEFT JOIN users u ON u.id = j.pengajar_id ORDER BY j.id DESC")->fetchAll(PDO::FETCH_ASSOC);

pesan_flash();
$totalPending = count(array_filter($jurnal, fn($j) => $j['status_review'] === 'pending' && ($j['nilai'] ?? '') === '' && empty($j['dinilai_pada'])));

$admin_title = 'Tugas Peserta';
$admin_aktif = 'jurnal';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">menu_book</span></div>
        <div><div class="admin__stat-num"><?= count($jurnal) ?></div><div class="admin__stat-label">Total Tugas</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">mark_email_unread</span></div>
        <div><div class="admin__stat-num"><?= $totalPending ?></div><div class="admin__stat-label">Perlu Dinilai</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--blue"><span class="material-symbols-rounded">folder_open</span></div>
        <div><div class="admin__stat-num"><?= count($materi) ?></div><div class="admin__stat-label">Materi Kursus</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">Tugas Peserta</div>

    <?php if (count($jurnal) > 0): ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul Tugas</th>
                        <th>Peserta</th>
                        <th>Untuk Pengajar</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th>Diunggah</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jurnal as $j): ?>
                    <tr>
                        <td><?= (int) $j['id'] ?></td>
                        <td><strong><?= htmlspecialchars($j['judul_jurnal']) ?></strong><br><span class="sub"><?= htmlspecialchars(ucfirst((string) ($j['program_peserta'] ?? ''))) ?></span></td>
                        <td><?= htmlspecialchars($j['nama_peserta'] ?? '-') ?><br><span class="sub">Enrollment ID: <?= htmlspecialchars($j['enrollment_id'] ?: '-') ?></span></td>
                        <td><?= $j['nama_guru'] ? htmlspecialchars($j['nama_guru']) . '<br><span class="sub">Staff ID: ' . htmlspecialchars($j['staff_id'] ?: $j['id_pengajar'] ?: '-') . '</span>' : '<span class="sub">-</span>' ?></td>
                        <td>
                            <span class="admin__badge admin__badge--<?= $j['status_review'] === 'acc' ? 'green' : ($j['status_review'] === 'revisi' ? 'red' : 'amber') ?>">
                                <?= $j['status_review'] === 'acc' ? 'ACC' : ($j['status_review'] === 'revisi' ? 'Perlu Revisi' : 'Menunggu Penilaian') ?>
                            </span><?php if (($j['dinilai_oleh'] ?? '') !== ''): ?><br><span class="sub">oleh <?= htmlspecialchars(nama_tanpa_id($j['dinilai_oleh'])) ?></span><?php endif; ?><?php if ((int) ($j['hapus_user'] ?? 0) === 1 || (int) ($j['hapus_pengajar'] ?? 0) === 1): ?><br><span class="sub">dihapus <?= (int) ($j['hapus_user'] ?? 0) === 1 ? 'user' : '' ?><?= (int) ($j['hapus_user'] ?? 0) === 1 && (int) ($j['hapus_pengajar'] ?? 0) === 1 ? ' & ' : '' ?><?= (int) ($j['hapus_pengajar'] ?? 0) === 1 ? 'pengajar' : '' ?></span><?php endif; ?>
                        </td>
                        <td><?= ($j['nilai'] ?? '') !== '' ? '<strong>' . htmlspecialchars($j['nilai']) . '</strong>' : '<span class="sub">—</span>' ?></td>
                        <td><?= date('d M Y, H:i', strtotime($j['diunggah_pada'])) ?></td>
                        <td style="text-align:right; white-space:nowrap">
                            <a class="admin__btn admin__btn--primary admin__btn--sm" href="/api/unduh-jurnal.php?id=<?= (int) $j['id'] ?>">
                                <span class="material-symbols-rounded" style="font-size:16px; vertical-align:-3px;">download</span> Unduh
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">menu_book</span>
            Belum ada tugas yang diunggah peserta.
        </div>
    <?php endif; ?>
</div>

<div class="admin__card">
    <div class="admin__card-title">Tambah Materi Kursus</div>
    <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
        <?= csrf_field() ?>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="judul_materi">Judul Materi</label>
            <input type="text" id="judul_materi" name="judul_materi" class="admin__input" required maxlength="255" placeholder="Contoh: Modul Nahwu Dasar">
        </div>
        <div style="flex:0 0 170px;">
            <label class="admin__form-label" for="program_materi">Program</label>
            <select id="program_materi" name="program_materi" class="admin__input">
                <option value="offline">Offline</option>
                <option value="online">Online</option>
                <option value="dauroh">Dauroh</option>
            </select>
        </div>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="file_materi">File (PDF/DOC/DOCX, maks 10MB)</label>
            <input type="file" id="file_materi" name="file_materi" class="admin__input" required accept=".pdf,.doc,.docx">
        </div>
        <button type="submit" name="tambah_materi" value="1" class="admin__btn admin__btn--primary">Tambah</button>
    </form>
</div>

<div class="admin__card">
    <div class="admin__card-title">Daftar Materi Kursus</div>

    <?php if (count($materi) > 0): ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul</th>
                        <th>Program</th>
                        <th>Dibuat</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materi as $m): ?>
                    <tr>
                        <td><?= (int) $m['id'] ?></td>
                        <td><strong><?= htmlspecialchars($m['judul']) ?></strong><?php if (($m['diunggah_oleh'] ?? '') !== ''): ?><br><span class="sub">oleh <?= htmlspecialchars($m['diunggah_oleh']) ?></span><?php endif; ?></td>
                        <td><?= htmlspecialchars(ucfirst($m['program'])) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($m['created_at'])) ?></td>
                        <td style="text-align:right; white-space:nowrap">
                            <a class="admin__btn admin__btn--outline admin__btn--sm" href="/api/unduh-materi.php?id=<?= (int) $m['id'] ?>">Unduh</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus materi <?= htmlspecialchars($m['judul'], ENT_QUOTES) ?>?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hapus_materi" value="<?= (int) $m['id'] ?>">
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
            <span class="material-symbols-rounded">folder_open</span>
            Belum ada materi. Tambahkan lewat formulir di atas.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
