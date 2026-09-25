<?php
// pengajar/index.php — beranda dashboard pengajar: profil + ringkasan.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/pengajar.php';

$pg = $_SESSION['pengajar'] ?? [];

$totalMateri = (int) $pdo->query("SELECT COUNT(*) FROM materi_kursus")->fetchColumn();
$totalAgenda = (int) $pdo->query("SELECT COUNT(*) FROM agenda WHERE tanggal >= CURDATE()")->fetchColumn();
$totalPending = (int) $pdo->query("SELECT COUNT(*) FROM jurnal_peserta WHERE status_review = 'pending'")->fetchColumn();
$totalAcc = (int) $pdo->query("SELECT COUNT(*) FROM jurnal_peserta WHERE status_review = 'acc'")->fetchColumn();

$pengajar_title = 'Dashboard';
$pengajar_aktif = 'dashboard';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<div class="admin__card" style="display:flex; align-items:center; gap:16px;">
    <span style="width:64px; height:64px; border-radius:50%; overflow:hidden; flex:none; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#166534,#14532D); color:#fff; font-size:24px; font-weight:800;">
        <?php if (!empty($_SESSION['pengajar']['avatar'])): ?>
            <img src="/<?= htmlspecialchars($_SESSION['pengajar']['avatar']) ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
        <?php else: ?>
            <?= htmlspecialchars(mb_strtoupper(mb_substr($pg['name'] ?? '?', 0, 1))) ?>
        <?php endif; ?>
    </span>
    <div>
        <div style="font-size:18px; font-weight:800;"><?= htmlspecialchars($pg['name'] ?? '-') ?></div>
        <div style="font-size:13px; color:var(--admin-muted);">Staff ID: <code><?= htmlspecialchars($pg['staff_id'] ?? '-') ?></code> · <?= htmlspecialchars(peran_label($pg['peran'] ?? 'pengajar')) ?></div>
        <div style="font-size:13px; color:var(--admin-muted);"><?= htmlspecialchars($pg['email'] ?? '') ?></div>
    </div>
</div>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--blue"><span class="material-symbols-rounded">folder_open</span></div>
        <div><div class="admin__stat-num"><?= $totalMateri ?></div><div class="admin__stat-label">Materi Kursus</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">calendar_month</span></div>
        <div><div class="admin__stat-num"><?= $totalAgenda ?></div><div class="admin__stat-label">Agenda Mendatang</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">assignment</span></div>
        <div><div class="admin__stat-num"><?= $totalPending ?></div><div class="admin__stat-label">Tugas Perlu Dinilai</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">verified</span></div>
        <div><div class="admin__stat-num"><?= $totalAcc ?></div><div class="admin__stat-label">Tugas ACC</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">Aksi Cepat</div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
        <a class="admin__btn admin__btn--primary" href="tugas.php">Nilai Tugas Masuk</a>
        <a class="admin__btn admin__btn--outline" href="materi.php">Lihat Materi</a>
        <a class="admin__btn admin__btn--outline" href="agenda.php">Lihat Agenda</a>
    </div>
</div>

<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
