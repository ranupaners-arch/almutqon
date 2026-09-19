<?php
// pengajar/agenda.php — agenda mendatang untuk pengajar (semua cakupan, read-only).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$agenda = $pdo->query("SELECT * FROM agenda WHERE tanggal >= CURDATE() ORDER BY tanggal ASC, jam_mulai ASC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

$warna = ['global' => 'blue', 'offline' => 'green', 'online' => 'amber', 'dauroh' => 'red'];
$label = ['global' => 'Global', 'offline' => 'Offline', 'online' => 'Online', 'dauroh' => 'Dauroh'];

$pengajar_title = 'Agenda';
$pengajar_aktif = 'agenda';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<div class="admin__card">
    <div class="admin__card-title">Agenda Mendatang</div>

    <?php if (empty($agenda)): ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">event_upcoming</span>
            Belum ada agenda mendatang.
        </div>
    <?php else: ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Agenda</th>
                        <th>Cakupan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agenda as $a): ?>
                    <tr>
                        <td style="white-space:nowrap"><strong><?= date('d M Y', strtotime($a['tanggal'])) ?></strong><br><span class="sub"><?= ($a['jam_mulai'] ? substr($a['jam_mulai'], 0, 5) : '') . ($a['jam_selesai'] ? '–' . substr($a['jam_selesai'], 0, 5) : '') ?></span></td>
                        <td><strong><?= htmlspecialchars($a['judul']) ?></strong><?php if (($a['lokasi'] ?? '') !== ''): ?><br><span class="sub"><?= htmlspecialchars($a['lokasi']) ?></span><?php endif; ?><?php if (($a['deskripsi'] ?? '') !== ''): ?><br><span class="sub"><?= htmlspecialchars(mb_strimwidth($a['deskripsi'], 0, 120, '…')) ?></span><?php endif; ?></td>
                        <td><span class="admin__badge admin__badge--<?= $warna[$a['cakupan']] ?? 'gray' ?>"><?= $label[$a['cakupan']] ?? $a['cakupan'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
