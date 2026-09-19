<?php
// pages/akun/unggah-tugas.php — dashboard peserta: form Unggah Tugas (jurnal ilmiah).
// Wajib login (guard di layouts/akun-header.php). Hanya program LUNAS milik sendiri.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/csrf.php';

$email = (string) ($_SESSION['user']['email'] ?? '');

$stmt = $pdo->prepare("SELECT id, program, nama, metode FROM pendaftar WHERE user_email = ? AND status_pembayaran = 'lunas' ORDER BY id DESC");
$stmt->execute([$email]);
$lunas = $stmt->fetchAll();

$labelProgram = function (array $r): string {
    return match ($r['program']) {
        'offline' => 'Offline ' . ($r['metode'] ?: ''),
        'online'  => 'Online '  . ($r['metode'] ?: ''),
        'dauroh'  => 'Dauroh '  . ($r['metode'] ?: ''),
        default   => ucfirst((string) $r['program']),
    };
};

$stmt = $pdo->prepare("SELECT COUNT(*) FROM jurnal_peserta WHERE user_email = ?");
$stmt->execute([$email]);
$totalTugas = (int) $stmt->fetchColumn();

// Daftar pengajar aktif sebagai tujuan pengiriman (nama + ID tampil jelas).
$guru = $pdo->query("SELECT id, name, id_pengajar, peran FROM users WHERE peran != 'user' AND aktif = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../config/pengajar.php';

pesan_flash();

$akun_title = 'Unggah Tugas';
$akun_aktif = 'unggah';
include __DIR__ . '/../../layouts/akun-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<?php if (empty($lunas)): ?>
    <div class="admin__card" style="text-align:center; padding:48px 24px;">
        <span class="material-symbols-rounded" style="font-size:40px; color:#CBD5E1;">lock</span>
        <p style="font-size:16px; font-weight:800; margin:12px 0 6px;">Halaman khusus peserta aktif</p>
        <p style="font-size:14px; color:var(--admin-muted); margin:0 0 20px;">Selesaikan pembayaran program Anda terlebih dahulu untuk mengunggah tugas.</p>
        <a class="admin__btn admin__btn--primary" href="/pages/akun/pendaftaran-saya.php">Lihat Pendaftaran Saya</a>
    </div>
<?php else: ?>

    <div class="admin__stats">
        <div class="admin__stat">
            <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">school</span></div>
            <div><div class="admin__stat-num"><?= count($lunas) ?></div><div class="admin__stat-label">Program Aktif</div></div>
        </div>
        <div class="admin__stat">
            <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">upload_file</span></div>
            <div><div class="admin__stat-num"><?= $totalTugas ?></div><div class="admin__stat-label">Tugas Diunggah</div></div>
        </div>
    </div>

    <div class="admin__card">
        <div class="admin__card-title">Unggah Tugas</div>
        <p style="font-size:13px; color:var(--admin-muted); margin:4px 0 0;">Format PDF / DOC / DOCX, maksimal 5MB.</p>

        <form method="POST" action="/api/upload-jurnal.php" enctype="multipart/form-data" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
            <?= csrf_field() ?>
            <div style="flex:1; min-width:180px;">
                <label class="admin__form-label" for="jurnal_program">Program</label>
                <select id="jurnal_program" name="pendaftar_id" class="admin__input" required>
                    <?php foreach ($lunas as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($labelProgram($p)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:2; min-width:200px;">
                <label class="admin__form-label" for="jurnal_judul">Jenis Tugas</label>
                <input type="text" id="jurnal_judul" name="judul_jurnal" class="admin__input" required maxlength="255" placeholder="Contoh: Jurnal Ilmiah, Tugas Akhir, …">
            </div>
            <div style="flex:2; min-width:220px;">
                <label class="admin__form-label" for="jurnal_guru">Kirim ke Pengajar</label>
                <?php if (empty($guru)): ?>
                    <input type="text" class="admin__input" disabled value="Belum ada pengajar aktif">
                <?php else: ?>
                    <select id="jurnal_guru" name="pengajar_id" class="admin__input" required>
                        <?php foreach ($guru as $g): ?>
                            <option value="<?= (int) $g['id'] ?>"><?= htmlspecialchars($g['name']) ?> (<?= htmlspecialchars(peran_label($g['peran'])) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div style="flex:2; min-width:200px;">
                <label class="admin__form-label" for="jurnal_file">File Tugas</label>
                <input type="file" id="jurnal_file" name="file_jurnal" class="admin__input-file" required accept=".pdf,.doc,.docx">
            </div>
            <button type="submit" class="admin__btn admin__btn--primary">Unggah Tugas</button>
        </form>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../../layouts/akun-footer.php'; ?>
