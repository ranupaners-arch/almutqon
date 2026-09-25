<?php
// pengajar/materi.php — daftar seluruh materi kursus + form kirim materi baru.
// Pengunggah tercatat di kolom diunggah_oleh (untuk pemantauan Direktur).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_materi'])) {
    require_csrf();
    $judul = trim($_POST['judul'] ?? '');
    $program = $_POST['program'] ?? '';
    if ($judul === '' || mb_strlen($judul) > 255 || !in_array($program, ['offline', 'online', 'dauroh'], true)) {
        flash_error('Judul (maks 255 karakter) dan program materi wajib valid.');
    } elseif (!isset($_FILES['file_materi']) || $_FILES['file_materi']['error'] !== UPLOAD_ERR_OK) {
        flash_error('File materi wajib diunggah.');
    } elseif ($_FILES['file_materi']['size'] > 10 * 1024 * 1024) {
        flash_error('Ukuran file materi maksimal 10MB.');
    } else {
        $ext = strtolower(pathinfo($_FILES['file_materi']['name'], PATHINFO_EXTENSION));
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['file_materi']['tmp_name']);
        $ok = in_array($ext, ['pdf', 'doc', 'docx'], true) && in_array($mime, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true);
        if (!$ok) {
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
                $oleh = ($_SESSION['pengajar']['name'] ?? 'Pengajar') . ' (' . ($_SESSION['pengajar']['staff_id'] ?? $_SESSION['pengajar']['id_pengajar'] ?? '-') . ')';
                $stmt = $pdo->prepare("INSERT INTO materi_kursus (program, judul, path_file, diunggah_oleh) VALUES (?, ?, ?, ?)");
                $stmt->execute([$program, $judul, 'assets/materi/' . $newName, mb_strimwidth($oleh, 0, 100, '')]);
                flash('Materi "' . mb_strimwidth($judul, 0, 60, '…') . '" terkirim.');
            }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$materi = $pdo->query("SELECT * FROM materi_kursus ORDER BY program, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

pesan_flash();

$pengajar_title = 'Materi';
$pengajar_aktif = 'materi';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__card">
    <div class="admin__card-title">Kirim Materi Baru</div>
    <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
        <?= csrf_field() ?>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="mt_judul">Judul Materi</label>
            <input type="text" id="mt_judul" name="judul" class="admin__input" required maxlength="255" placeholder="Contoh: Modul Nahwu Bab 3">
        </div>
        <div style="flex:0 0 170px;">
            <label class="admin__form-label" for="mt_program">Program</label>
            <select id="mt_program" name="program" class="admin__input">
                <option value="offline">Offline</option>
                <option value="online">Online</option>
                <option value="dauroh">Dauroh</option>
            </select>
        </div>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="mt_file">File (PDF/DOC/DOCX, maks 10MB)</label>
            <input type="file" id="mt_file" name="file_materi" class="admin__input" required accept=".pdf,.doc,.docx">
        </div>
        <button type="submit" name="kirim_materi" value="1" class="admin__btn admin__btn--primary">Kirim</button>
    </form>
</div>

<div class="admin__card">
    <div class="admin__card-title">Materi Kursus</div>

    <?php if (empty($materi)): ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">folder_open</span>
            Belum ada materi kursus.
        </div>
    <?php else: ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Materi</th>
                        <th>Program</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materi as $m): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['judul']) ?></strong><br><span class="sub"><?= date('d M Y', strtotime($m['created_at'])) ?><?= ($m['diunggah_oleh'] ?? '') !== '' ? ' · oleh ' . htmlspecialchars($m['diunggah_oleh']) : '' ?></span></td>
                        <td><?= htmlspecialchars(ucfirst($m['program'])) ?></td>
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

<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
