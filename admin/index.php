<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_admin();

$summary = $pdo->query("SELECT
    COUNT(*) AS total_pendaftar,
    SUM(status = 'baru') AS belum_dihubungi,
    SUM(status_pembayaran = 'pending') AS pembayaran_pending,
    SUM(status_pembayaran = 'belum') AS belum_bayar,
    SUM(status_pembayaran = 'lunas') AS pembayaran_lunas,
    COALESCE(SUM(CASE WHEN status_pembayaran = 'lunas' THEN biaya ELSE 0 END), 0) AS pemasukan
    FROM pendaftar")->fetch() ?: [];

$pending_tasks = (int) $pdo->query("SELECT COUNT(*) FROM jurnal_peserta WHERE status_review = 'pending' AND (nilai IS NULL OR nilai = '') AND dinilai_pada IS NULL")->fetchColumn();
$upcoming_agenda = (int) $pdo->query("SELECT COUNT(*) FROM agenda WHERE tanggal >= CURDATE()")->fetchColumn();

$program_rows = $pdo->query("SELECT program,
    COUNT(*) AS pendaftar,
    SUM(status_pembayaran = 'lunas') AS lunas,
    COALESCE(SUM(CASE WHEN status_pembayaran = 'lunas' THEN biaya ELSE 0 END), 0) AS pemasukan
    FROM pendaftar GROUP BY program")->fetchAll();
$programs = [
    'offline' => ['label' => 'Offline', 'pendaftar' => 0, 'lunas' => 0, 'pemasukan' => 0],
    'online' => ['label' => 'Online', 'pendaftar' => 0, 'lunas' => 0, 'pemasukan' => 0],
    'dauroh' => ['label' => 'Dauroh', 'pendaftar' => 0, 'lunas' => 0, 'pemasukan' => 0],
];
foreach ($program_rows as $row) {
    if (isset($programs[$row['program']])) {
        $programs[$row['program']]['pendaftar'] = (int) $row['pendaftar'];
        $programs[$row['program']]['lunas'] = (int) $row['lunas'];
        $programs[$row['program']]['pemasukan'] = (int) $row['pemasukan'];
    }
}

$payment_queue = $pdo->query("SELECT id, nama, program, biaya, status_pembayaran, created_at FROM pendaftar WHERE status_pembayaran IN ('pending', 'belum') ORDER BY status_pembayaran = 'pending' DESC, created_at ASC LIMIT 5")->fetchAll();

$activity = [];
foreach ($pdo->query("SELECT nama, program, created_at FROM pendaftar ORDER BY created_at DESC LIMIT 4")->fetchAll() as $item) {
    $activity[] = ['time' => $item['created_at'], 'icon' => 'person_add', 'text' => $item['nama'] . ' mendaftar program ' . ucfirst($item['program']), 'href' => 'data-pendaftar.php'];
}
foreach ($pdo->query("SELECT j.judul_jurnal, j.diunggah_pada, p.nama FROM jurnal_peserta j LEFT JOIN pendaftar p ON p.id = j.pendaftar_id ORDER BY j.diunggah_pada DESC LIMIT 4")->fetchAll() as $item) {
    $activity[] = ['time' => $item['diunggah_pada'], 'icon' => 'upload_file', 'text' => ($item['nama'] ?: 'Peserta') . ' mengunggah tugas “' . $item['judul_jurnal'] . '”', 'href' => 'data-jurnal.php'];
}
usort($activity, static fn(array $a, array $b): int => strcmp($b['time'], $a['time']));
$activity = array_slice($activity, 0, 6);

$admin_title = 'Dashboard';
$admin_aktif = 'dashboard';
include __DIR__ . '/../layouts/admin-header.php';
?>

<div class="admin__stats">
    <div class="admin__stat"><div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">groups</span></div><div><div class="admin__stat-num"><?= (int) ($summary['total_pendaftar'] ?? 0) ?></div><div class="admin__stat-label">Total Pendaftar</div></div></div>
    <div class="admin__stat"><div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">mark_email_unread</span></div><div><div class="admin__stat-num"><?= (int) ($summary['belum_dihubungi'] ?? 0) ?></div><div class="admin__stat-label">Belum Dihubungi</div></div></div>
    <div class="admin__stat"><div class="admin__stat-icon admin__stat-icon--blue"><span class="material-symbols-rounded">schedule</span></div><div><div class="admin__stat-num"><?= (int) ($summary['pembayaran_pending'] ?? 0) ?></div><div class="admin__stat-label">Pembayaran Pending</div></div></div>
    <div class="admin__stat"><div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">savings</span></div><div><div class="admin__stat-num">Rp<?= number_format((int) ($summary['pemasukan'] ?? 0), 0, ',', '.') ?></div><div class="admin__stat-label">Pemasukan Lunas</div></div></div>
</div>

<section class="admin-dashboard-grid" aria-label="Prioritas operasional">
    <div class="admin__card">
        <div class="admin__card-title"><span>Perlu Ditindaklanjuti</span><span class="admin__badge admin__badge--amber"><?= (int) ($summary['belum_dihubungi'] ?? 0) + (int) ($summary['pembayaran_pending'] ?? 0) + $pending_tasks ?> item</span></div>
        <div class="admin__action-list">
            <a class="admin__action-item" href="data-pendaftar.php"><span class="admin__action-icon admin__action-icon--amber material-symbols-rounded">mark_email_unread</span><span><strong><?= (int) ($summary['belum_dihubungi'] ?? 0) ?> pendaftar belum dihubungi</strong><small>Hubungi dan tandai statusnya</small></span><span class="material-symbols-rounded admin__action-arrow">chevron_right</span></a>
            <a class="admin__action-item" href="data-pembayaran.php?status=pending"><span class="admin__action-icon admin__action-icon--blue material-symbols-rounded">payments</span><span><strong><?= (int) ($summary['pembayaran_pending'] ?? 0) ?> pembayaran pending</strong><small>Periksa dan konfirmasi bila sudah diterima</small></span><span class="material-symbols-rounded admin__action-arrow">chevron_right</span></a>
            <a class="admin__action-item" href="data-jurnal.php"><span class="admin__action-icon admin__action-icon--green material-symbols-rounded">rate_review</span><span><strong><?= $pending_tasks ?> tugas perlu dinilai</strong><small>Berikan nilai atau minta revisi</small></span><span class="material-symbols-rounded admin__action-arrow">chevron_right</span></a>
            <a class="admin__action-item" href="data-agenda.php"><span class="admin__action-icon admin__action-icon--slate material-symbols-rounded">calendar_month</span><span><strong><?= $upcoming_agenda ?> agenda mendatang</strong><small>Pastikan jadwal dan cakupan peserta tepat</small></span><span class="material-symbols-rounded admin__action-arrow">chevron_right</span></a>
        </div>
    </div>
    <div class="admin__card">
        <div class="admin__card-title">Akses Cepat</div>
        <div class="admin__quick-actions">
            <a href="tambah-artikel.php"><span class="material-symbols-rounded">edit_note</span><span>Tulis Artikel</span></a>
            <a href="data-pendaftar.php"><span class="material-symbols-rounded">groups</span><span>Data Pendaftar</span></a>
            <a href="data-pembayaran.php?status=pending"><span class="material-symbols-rounded">payments</span><span>Konfirmasi Bayar</span></a>
            <a href="data-jurnal.php"><span class="material-symbols-rounded">assignment</span><span>Nilai Tugas</span></a>
            <a href="data-agenda.php"><span class="material-symbols-rounded">event_available</span><span>Tambah Agenda</span></a>
        </div>
    </div>
</section>

<section class="admin__card">
    <div class="admin__card-title">Ringkasan Program</div>
    <div class="admin__table-wrap">
        <table class="admin__table"><thead><tr><th>Program</th><th>Pendaftar</th><th>Sudah Lunas</th><th>Konversi</th><th>Pemasukan Lunas</th></tr></thead><tbody>
        <?php foreach ($programs as $program): $conversion = $program['pendaftar'] ? round(($program['lunas'] / $program['pendaftar']) * 100) : 0; ?>
            <tr><td><strong><?= htmlspecialchars($program['label']) ?></strong></td><td><?= $program['pendaftar'] ?></td><td><?= $program['lunas'] ?></td><td><?= $conversion ?>%</td><td><strong>Rp<?= number_format($program['pemasukan'], 0, ',', '.') ?></strong></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</section>

<section class="admin-dashboard-grid admin-dashboard-grid--bottom">
    <div class="admin__card">
        <div class="admin__card-title">Aktivitas Terbaru</div>
        <?php if ($activity): ?><div class="admin__activity-list"><?php foreach ($activity as $item): ?><a class="admin__activity" href="<?= htmlspecialchars($item['href']) ?>"><span class="material-symbols-rounded"><?= htmlspecialchars($item['icon']) ?></span><span><strong><?= htmlspecialchars($item['text']) ?></strong><small><?= htmlspecialchars(date('d M Y, H:i', strtotime($item['time']))) ?></small></span></a><?php endforeach; ?></div><?php else: ?><div class="admin__empty">Belum ada aktivitas yang tercatat.</div><?php endif; ?>
    </div>
    <div class="admin__card">
        <div class="admin__card-title">Antrian Pembayaran</div>
        <?php if ($payment_queue): ?><div class="admin__mini-list"><?php foreach ($payment_queue as $item): ?><a href="data-pembayaran.php?status=<?= htmlspecialchars($item['status_pembayaran']) ?>"><span><strong><?= htmlspecialchars($item['nama']) ?></strong><small><?= htmlspecialchars(ucfirst($item['program'])) ?> · Rp<?= number_format((int) $item['biaya'], 0, ',', '.') ?></small></span><span class="admin__badge admin__badge--<?= $item['status_pembayaran'] === 'pending' ? 'blue' : 'gray' ?>"><?= htmlspecialchars(ucfirst($item['status_pembayaran'])) ?></span></a><?php endforeach; ?></div><?php else: ?><div class="admin__empty">Tidak ada pembayaran dalam antrian.</div><?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
