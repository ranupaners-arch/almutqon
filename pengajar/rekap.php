<?php
// pengajar/rekap.php — rekap absensi sesuai hari yang disepakati murid & guru.
// Kelola hari sepakat (per peserta privat / per program kelompok-dauroh) +
// tabel rekap per peserta dalam rentang tanggal (sesi, hadir %, di luar jadwal).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

$HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$NO_HARI = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7];

$program = $_GET['program'] ?? $_POST['program'] ?? 'offline';
if (!in_array($program, ['offline', 'online', 'dauroh'], true)) {
    $program = 'offline';
}
$dari = $_GET['dari'] ?? $_POST['dari'] ?? date('Y-m-d', strtotime('-29 days'));
$sampai = $_GET['sampai'] ?? $_POST['sampai'] ?? date('Y-m-d');
foreach (['dari' => &$dari, 'sampai' => &$sampai] as &$t) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) !== 1 || strtotime($t) === false) {
        $t = date('Y-m-d');
    }
}
if ($dari > $sampai) {
    [$dari, $sampai] = [$sampai, $dari];
}

// Simpan hari kesepakatan (checkbox Sen..Min → CSV).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_jadwal'])) {
    require_csrf();
    $scope = $_POST['scope'] ?? '';
    $hari = array_values(array_intersect($_POST['hari'] ?? [], $HARI));
    $csv = implode(',', $hari);
    if ($scope === 'program') {
        $cek = $pdo->prepare("SELECT id FROM jadwal_sepakatan WHERE program = ? AND pendaftar_id IS NULL LIMIT 1");
        $cek->execute([$program]);
        $ada = $cek->fetch();
        if ($ada) {
            $pdo->prepare("UPDATE jadwal_sepakatan SET hari = ? WHERE id = ?")->execute([$csv, (int) $ada['id']]);
        } else {
            $pdo->prepare("INSERT INTO jadwal_sepakatan (pendaftar_id, program, hari) VALUES (NULL, ?, ?)")->execute([$program, $csv]);
        }
        flash('Hari sepakat program ' . ucfirst($program) . ' disimpan: ' . ($csv !== '' ? $csv : '—'));
    } else {
        $pid = (int) $scope;
        $cek = $pdo->prepare("SELECT id, program FROM pendaftar WHERE id = ? AND status_pembayaran = 'lunas' LIMIT 1");
        $cek->execute([$pid]);
        $pr = $cek->fetch();
        if (!$pr) {
            flash_error('Peserta tidak valid.');
        } else {
            $cek2 = $pdo->prepare("SELECT id FROM jadwal_sepakatan WHERE pendaftar_id = ? LIMIT 1");
            $cek2->execute([$pid]);
            $ada = $cek2->fetch();
            if ($ada) {
                $pdo->prepare("UPDATE jadwal_sepakatan SET hari = ? WHERE id = ?")->execute([$csv, (int) $ada['id']]);
            } else {
                $pdo->prepare("INSERT INTO jadwal_sepakatan (pendaftar_id, program, hari) VALUES (?, ?, ?)")->execute([$pid, $pr['program'], $csv]);
            }
            flash('Hari sepakat peserta disimpan: ' . ($csv !== '' ? $csv : '—'));
        }
    }
    header('Location: rekap.php?program=' . $program . '&dari=' . $dari . '&sampai=' . $sampai);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nama, metode FROM pendaftar WHERE program = ? AND status_pembayaran = 'lunas' ORDER BY nama ASC");
$stmt->execute([$program]);
$peserta = $stmt->fetchAll();

$ids = array_column($peserta, 'id');
$absen = [];
if (count($ids) > 0) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT pendaftar_id, tanggal, status FROM absensi WHERE pendaftar_id IN ($ph) AND tanggal BETWEEN ? AND ?");
    $stmt->execute([...$ids, $dari, $sampai]);
    foreach ($stmt->fetchAll() as $a) {
        $absen[$a['pendaftar_id']][] = $a;
    }
}

$stmt = $pdo->prepare("SELECT pendaftar_id, hari FROM jadwal_sepakatan WHERE program = ? OR pendaftar_id IN (SELECT id FROM pendaftar WHERE program = ?)");
$stmt->execute([$program, $program]);
$jadwalProg = '';
$jadwalPeserta = [];
foreach ($stmt->fetchAll() as $j) {
    if ($j['pendaftar_id'] === null) {
        $jadwalProg = (string) $j['hari'];
    } else {
        $jadwalPeserta[(int) $j['pendaftar_id']] = (string) $j['hari'];
    }
}

// Daftar tanggal dalam rentang per nomor hari (1=Senin..7=Minggu).
$tglNo = [];
for ($t = strtotime($dari); $t <= strtotime($sampai); $t += 86400) {
    $tglNo[date('Y-m-d', $t)] = (int) date('N', $t);
}

pesan_flash();

$pengajar_title = 'Rekap Absensi';
$pengajar_aktif = 'rekap';
include __DIR__ . '/../layouts/pengajar-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__card">
    <div class="admin__card-title">Filter Rekap</div>
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
        <div style="flex:0 0 170px;">
            <label class="admin__form-label" for="f_program">Program</label>
            <select id="f_program" name="program" class="admin__input" onchange="this.form.submit()">
                <?php foreach (['offline' => 'Offline', 'online' => 'Online', 'dauroh' => 'Dauroh'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $program === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:0 0 160px;">
            <label class="admin__form-label" for="f_dari">Dari</label>
            <input type="date" id="f_dari" name="dari" class="admin__input" value="<?= htmlspecialchars($dari) ?>" onchange="this.form.submit()">
        </div>
        <div style="flex:0 0 160px;">
            <label class="admin__form-label" for="f_sampai">Sampai</label>
            <input type="date" id="f_sampai" name="sampai" class="admin__input" value="<?= htmlspecialchars($sampai) ?>" onchange="this.form.submit()">
        </div>
    </form>
</div>

<div class="admin__card">
    <div class="admin__card-title">Hari yang Disepakati</div>
    <p style="font-size:13px; color:var(--admin-muted); margin:4px 0 0;">Program: <strong><?= $jadwalProg !== '' ? htmlspecialchars($jadwalProg) : '—' ?></strong> (berlaku kelompok/dauroh &amp; privat tanpa jadwal khusus).</p>
    <form method="POST" style="margin-top:12px;">
        <?= csrf_field() ?>
        <input type="hidden" name="program" value="<?= htmlspecialchars($program) ?>">
        <input type="hidden" name="dari" value="<?= htmlspecialchars($dari) ?>">
        <input type="hidden" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
        <input type="hidden" name="scope" value="program">
        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
            <?php $cekHari = $jadwalProg !== '' ? explode(',', $jadwalProg) : []; ?>
            <?php foreach ($HARI as $h): ?>
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:14px;"><input type="checkbox" name="hari[]" value="<?= $h ?>" <?= in_array($h, $cekHari, true) ? 'checked' : '' ?> style="width:17px; height:17px; accent-color:#166534;"> <?= $h ?></label>
            <?php endforeach; ?>
        </div>
        <button type="submit" name="simpan_jadwal" value="1" class="admin__btn admin__btn--primary admin__btn--sm">Simpan Hari Program</button>
    </form>
    <?php if (!empty($peserta)): ?>
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:16px; padding-top:16px; border-top:1px solid var(--admin-border);">
        <?= csrf_field() ?>
        <input type="hidden" name="program" value="<?= htmlspecialchars($program) ?>">
        <input type="hidden" name="dari" value="<?= htmlspecialchars($dari) ?>">
        <input type="hidden" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
        <div style="flex:1; min-width:200px;">
            <label class="admin__form-label" for="j_peserta">Jadwal khusus peserta privat</label>
            <select id="j_peserta" name="scope" class="admin__input" required>
                <?php foreach ($peserta as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?><?= isset($jadwalPeserta[(int) $p['id']]) && $jadwalPeserta[(int) $p['id']] !== '' ? ' (' . htmlspecialchars($jadwalPeserta[(int) $p['id']]) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:2; min-width:220px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <?php foreach ($HARI as $h): ?>
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:14px;"><input type="checkbox" name="hari[]" value="<?= $h ?>" style="width:17px; height:17px; accent-color:#166534;"> <?= substr($h, 0, 3) ?></label>
            <?php endforeach; ?>
        </div>
        <button type="submit" name="simpan_jadwal" value="1" class="admin__btn admin__btn--outline admin__btn--sm">Simpan</button>
    </form>
    <?php endif; ?>
</div>

<div class="admin__card">
    <div class="admin__card-title">Rekap <?= htmlspecialchars(ucfirst($program)) ?> (<?= date('d M Y', strtotime($dari)) ?> – <?= date('d M Y', strtotime($sampai)) ?>)</div>

    <?php if (empty($peserta)): ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">bar_chart</span>
            Belum ada peserta lunas pada program ini.
        </div>
    <?php else: ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Peserta</th>
                        <th>Jadwal Sepakat</th>
                        <th style="text-align:center">Sesi</th>
                        <th style="text-align:center">Hadir</th>
                        <th style="text-align:center">Izin/Sakit/Alpa</th>
                        <th style="text-align:right">% Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peserta as $p): ?>
                    <?php
                    $pid = (int) $p['id'];
                    $jk = $jadwalPeserta[$pid] ?? $jadwalProg;
                    $jkArr = $jk !== '' ? explode(',', $jk) : [];
                    $jkNo = array_map(fn($h) => $NO_HARI[$h] ?? 0, $jkArr);
                    $rows = $absen[$pid] ?? [];
                    $total = count($rows);
                    $h = count(array_filter($rows, fn($r) => $r['status'] === 'hadir'));
                    $i = count(array_filter($rows, fn($r) => $r['status'] === 'izin'));
                    $s = count(array_filter($rows, fn($r) => $r['status'] === 'sakit'));
                    $a = count(array_filter($rows, fn($r) => $r['status'] === 'alpa'));
                    $diLuar = 0;
                    if (!empty($jkNo)) {
                        foreach ($rows as $r) {
                            if (!in_array((int) date('N', strtotime($r['tanggal'])), $jkNo, true)) {
                                $diLuar++;
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['nama']) ?></strong></td>
                        <td><?= $jk !== '' ? htmlspecialchars($jk) : '<span class="sub">—</span>' ?></td>
                        <td style="text-align:center"><?= $total ?></td>
                        <td style="text-align:center"><strong><?= $h ?></strong></td>
                        <td style="text-align:center"><?= $i ?>/<?= $s ?>/<?= $a ?></td>
                        <td style="text-align:right"><?= $total > 0 ? '<strong>' . (int) round($h * 100 / $total) . '%</strong>' . ($diLuar > 0 ? '<br><span class="sub">' . $diLuar . ' di luar jadwal</span>' : '') : '<span class="sub">—</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/pengajar-footer.php'; ?>
