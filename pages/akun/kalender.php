<?php
// pages/akun/kalender.php — kalender agenda untuk user: agenda GLOBAL + agenda program
// LUNAS miliknya. Wajib login (guard di layouts/akun-header.php).
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$email = (string) ($_SESSION['user']['email'] ?? '');

// Program LUNAS milik user → cakupan yang boleh dilihat (selalu termasuk 'global').
$stmt = $pdo->prepare("SELECT DISTINCT program FROM pendaftar WHERE user_email = ? AND status_pembayaran = 'lunas'");
$stmt->execute([$email]);
$cakupan = array_merge(['global'], $stmt->fetchAll(PDO::FETCH_COLUMN));

$ph = implode(',', array_fill(0, count($cakupan), '?'));
$stmt = $pdo->prepare("SELECT id, judul, deskripsi, tanggal, jam_mulai, jam_selesai, lokasi, cakupan FROM agenda WHERE cakupan IN ($ph) ORDER BY tanggal ASC, jam_mulai ASC");
$stmt->execute($cakupan);
$semua = $stmt->fetchAll();

$hariIni = date('Y-m-d');
$mendatang = array_values(array_filter($semua, fn($a) => $a['tanggal'] >= $hariIni));
$mendatang = array_slice($mendatang, 0, 10);

$sisaTeks = function (string $tgl): string {
    $diff = (int) ((strtotime($tgl) - strtotime(date('Y-m-d'))) / 86400);
    if ($diff <= 0) {
        return 'Hari ini';
    }
    if ($diff === 1) {
        return 'Besok';
    }
    return "H-{$diff}";
};
$jamTeks = function (array $a): string {
    $m = ($a['jam_mulai'] ?? '') !== '' ? substr($a['jam_mulai'], 0, 5) : '';
    $s = ($a['jam_selesai'] ?? '') !== '' ? substr($a['jam_selesai'], 0, 5) : '';
    return $m !== '' ? $m . ($s !== '' ? '–' . $s : ' WIB') : '';
};
$warna = ['global' => '#2563EB', 'offline' => '#16a34a', 'online' => '#D97706', 'dauroh' => '#DC2626'];
$labelCakupan = ['global' => 'Global', 'offline' => 'Offline', 'online' => 'Online', 'dauroh' => 'Dauroh'];

$akun_title = 'Kalender Agenda';
$akun_aktif = 'kalender';
include __DIR__ . '/../../layouts/akun-header.php';
?>

<div class="admin__card">
    <div class="admin__card-title">Agenda Terdekat</div>

    <?php if (empty($mendatang)): ?>
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
                        <th style="text-align:right">Hitung Mundur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mendatang as $a): ?>
                    <tr>
                        <td style="white-space:nowrap"><strong><?= date('d M Y', strtotime($a['tanggal'])) ?></strong><br><span class="sub"><?= htmlspecialchars($jamTeks($a)) ?></span></td>
                        <td><strong><?= htmlspecialchars($a['judul']) ?></strong><?php if (($a['lokasi'] ?? '') !== ''): ?><br><span class="sub"><?= htmlspecialchars($a['lokasi']) ?></span><?php endif; ?><?php if (($a['deskripsi'] ?? '') !== ''): ?><br><span class="sub"><?= htmlspecialchars(mb_strimwidth($a['deskripsi'], 0, 120, '…')) ?></span><?php endif; ?></td>
                        <td><span class="admin__badge" style="background:<?= $warna[$a['cakupan']] ?? '#64748B' ?>1A; color:<?= $warna[$a['cakupan']] ?? '#64748B' ?>; border:1px solid <?= $warna[$a['cakupan']] ?? '#64748B' ?>55;"><?= $labelCakupan[$a['cakupan']] ?? $a['cakupan'] ?></span></td>
                        <td style="text-align:right; white-space:nowrap"><strong><?= htmlspecialchars($sisaTeks($a['tanggal'])) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="admin__card">
    <div class="admin__card-title">Kalender Bulan Ini</div>

    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin:12px 0;">
        <button type="button" class="admin__btn admin__btn--outline admin__btn--sm" id="kalPrev" aria-label="Bulan sebelumnya">‹</button>
        <strong id="kalJudul" style="font-size:15px;"></strong>
        <button type="button" class="admin__btn admin__btn--outline admin__btn--sm" id="kalNext" aria-label="Bulan berikutnya">›</button>
    </div>
    <div class="admin__table-wrap">
        <table class="admin__table" id="kalGrid" aria-label="Kalender agenda">
            <thead>
                <tr><th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th></tr>
            </thead>
            <tbody id="kalBody"></tbody>
        </table>
    </div>
    <div id="kalDetail" style="margin-top:12px; font-size:13.5px;"></div>
</div>

<script>
(function () {
    const data = <?= json_encode(array_map(fn($a) => [
        'tanggal' => $a['tanggal'],
        'judul'   => $a['judul'],
        'jam'     => $jamTeks($a),
        'lokasi'  => (string) ($a['lokasi'] ?? ''),
        'cakupan' => $a['cakupan'],
    ], $semua), JSON_UNESCAPED_UNICODE) ?>;
    const warna = <?= json_encode($warna, JSON_UNESCAPED_UNICODE) ?>;
    const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const byTgl = {};
    data.forEach((a) => { (byTgl[a.tanggal] = byTgl[a.tanggal] || []).push(a); });

    const now = new Date();
    let y = now.getFullYear(), m = now.getMonth();
    const judul = document.getElementById('kalJudul');
    const body = document.getElementById('kalBody');
    const detail = document.getElementById('kalDetail');
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function pad(n) { return String(n).padStart(2, '0'); }

    function render() {
        judul.textContent = namaBulan[m] + ' ' + y;
        body.innerHTML = '';
        const first = new Date(y, m, 1);
        let start = first.getDay(); // 0 = Minggu
        const days = new Date(y, m + 1, 0).getDate();
        const today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
        let tr = document.createElement('tr');
        for (let i = 0; i < start; i++) tr.appendChild(document.createElement('td'));
        for (let d = 1; d <= days; d++) {
            if ((start + d - 1) % 7 === 0 && d !== 1) { body.appendChild(tr); tr = document.createElement('tr'); }
            const key = y + '-' + pad(m + 1) + '-' + pad(d);
            const td = document.createElement('td');
            td.style.cursor = 'pointer';
            td.style.minWidth = '44px';
            let html = '<div style="font-weight:700;' + (key === today ? 'color:#fff;background:#166534;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;' : '') + '">' + d + '</div>';
            (byTgl[key] || []).slice(0, 3).forEach((a) => {
                html += '<div title="' + esc(a.judul) + '" style="width:8px;height:8px;border-radius:50%;background:' + (warna[a.cakupan] || '#64748B') + ';display:inline-block;margin:2px 2px 0 0;"></div>';
            });
            td.innerHTML = html;
            td.addEventListener('click', () => tampilDetail(key));
            tr.appendChild(td);
        }
        body.appendChild(tr);
        tampilDetail(today);
    }

    function tampilDetail(key) {
        const list = byTgl[key] || [];
        if (list.length === 0) { detail.innerHTML = '<span style="color:#94A3B8;">Tidak ada agenda pada ' + esc(key.split('-').reverse().join('-')) + '.</span>'; return; }
        detail.innerHTML = '<strong>' + esc(key.split('-').reverse().join('-')) + '</strong><ul style="margin:8px 0 0;padding-left:18px;">'
            + list.map((a) => '<li><strong>' + esc(a.judul) + '</strong>' + (a.jam ? ' · ' + esc(a.jam) : '') + (a.lokasi ? ' · ' + esc(a.lokasi) : '') + '</li>').join('')
            + '</ul>';
    }

    document.getElementById('kalPrev').addEventListener('click', () => { m--; if (m < 0) { m = 11; y--; } render(); });
    document.getElementById('kalNext').addEventListener('click', () => { m++; if (m > 11) { m = 0; y++; } render(); });
    render();
})();
</script>

<?php include __DIR__ . '/../../layouts/akun-footer.php'; ?>
