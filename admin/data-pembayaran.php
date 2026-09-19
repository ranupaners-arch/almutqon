<?php
// admin/data-pembayaran.php — rekap pembayaran kursus: status, nominal, konfirmasi lunas, struk PDF
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pembayaran.php';

require_admin();
require_once __DIR__ . '/../config/pendaftar-admin-actions.php';

$statusFilter = $_GET['status'] ?? 'all';
if (!in_array($statusFilter, ['all', 'belum', 'pending', 'lunas'], true)) $statusFilter = 'all';

if ($statusFilter === 'all') {
    $pendaftar = $pdo->query("SELECT * FROM pendaftar ORDER BY status_pembayaran = 'belum' DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT * FROM pendaftar WHERE status_pembayaran = ? ORDER BY created_at DESC");
    $stmt->execute([$statusFilter]);
    $pendaftar = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$semua = $pdo->query("SELECT status_pembayaran, biaya FROM pendaftar")->fetchAll(PDO::FETCH_ASSOC);
$counts = ['belum' => 0, 'pending' => 0, 'lunas' => 0];
$nominal = 0;
foreach ($semua as $s) {
    $counts[$s['status_pembayaran']] = ($counts[$s['status_pembayaran']] ?? 0) + 1;
    if ($s['status_pembayaran'] === 'lunas') $nominal += (int) $s['biaya'];
}
$totalAll = count($semua);

pesan_flash();

$admin_title = 'Pembayaran Kursus';
$admin_aktif = 'pembayaran';

$tabs = [
    'all'    => ['label' => 'Semua',      'count' => $totalAll],
    'belum'  => ['label' => 'Belum',      'count' => $counts['belum']],
    'pending' => ['label' => 'Pending',    'count' => $counts['pending']],
    'lunas'  => ['label' => 'Lunas',      'count' => $counts['lunas']],
];

include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">check_circle</span></div>
        <div><div class="admin__stat-num"><?= $counts['lunas'] ?></div><div class="admin__stat-label">Lunas</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">schedule</span></div>
        <div><div class="admin__stat-num"><?= $counts['pending'] ?></div><div class="admin__stat-label">Pending</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">hourglass_empty</span></div>
        <div><div class="admin__stat-num"><?= $counts['belum'] ?></div><div class="admin__stat-label">Belum Bayar</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--blue"><span class="material-symbols-rounded">savings</span></div>
        <div><div class="admin__stat-num"><?= rupiah($nominal) ?></div><div class="admin__stat-label">Terkumpul (Lunas)</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">Pembayaran Kursus
        <a class="admin__btn admin__btn--green admin__btn--sm" href="export-pembayaran.php" title="Unduh data LUNAS sebagai CSV">
            <span class="material-symbols-rounded" style="font-size:16px; vertical-align:-3px;">download</span> Unduh CSV
        </a>
    </div>

    <div class="admin__tabs">
        <?php foreach ($tabs as $k => $t): ?>
            <a class="admin__tab <?= $statusFilter === $k ? 'active' : '' ?>"
               href="data-pembayaran.php?status=<?= $k ?>">
                <?= $t['label'] ?> <span class="admin__tab-count"><?= $t['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (count($pendaftar) > 0): ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Nama / Program</th>
                        <th>Email</th>
                        <th>Nominal</th>
                        <th>Metode Bayar</th>
                        <th>Tanggal Bayar</th>
                        <th>Status</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendaftar as $p): ?>
                    <?php
                    $p_lunas = $p['status_pembayaran'] === 'lunas';
                    $plabel  = match ($p['program']) {
                        'offline' => 'Offline' . ($p['metode'] ? ' ' . $p['metode'] : ''),
                        'online'  => 'Online' . ($p['metode'] ? ' ' . $p['metode'] : ''),
                        'dauroh'  => 'Dauroh' . ($p['metode'] ? ' ' . $p['metode'] : ''),
                    };
                    $mbelum   = $p['metode_pembayaran'] === 'bank' ? 'Bank' : ($p['metode_pembayaran'] === 'qris' ? 'QRIS' : ($p['metode_pembayaran'] === 'ewallet' ? 'E-Wallet' : '-'));
                    $kanalLabel = donasi_kanal_label((string) $p['metode_pembayaran'], (string) ($p['kanal_pembayaran'] ?? ''));
                    if ($kanalLabel !== '') $mbelum .= ' · ' . $kanalLabel;
                    $pmeta = [];
                    if (($p['nama_panggilan'] ?? '') !== '') $pmeta[] = 'Panggilan: ' . $p['nama_panggilan'];
                    if (($p['jenis_kelamin'] ?? '') !== '') $pmeta[] = $p['jenis_kelamin'];
                    if ((int) ($p['usia'] ?? 0) > 0) $pmeta[] = (int) $p['usia'] . ' th';
                    ?>
                    <tr>
                        <td><code><?= htmlspecialchars($p['no_invoice'] !== '' ? $p['no_invoice'] : '—') ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($p['nama']) ?></strong><br>
                            <span class="sub"><?= htmlspecialchars($plabel) ?></span>
                            <?php if ($pmeta): ?><br><span class="sub"><?= htmlspecialchars(implode(' · ', $pmeta)) ?></span><?php endif; ?>
                        </td>
                        <td>
                            <?php if (trim((string) $p['user_email']) !== ''): ?>
                                <a href="mailto:<?= htmlspecialchars($p['user_email']) ?>" style="color:var(--admin-primary);word-break:break-all"><?= htmlspecialchars($p['user_email']) ?></a>
                            <?php else: ?>
                                <span class="sub">—</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= $p['biaya'] > 0 ? rupiah((int) $p['biaya']) : '-' ?></strong></td>
                        <td><?= htmlspecialchars($mbelum) ?></td>
                        <td><?= $p['tanggal_bayar'] ? date('d M Y', strtotime($p['tanggal_bayar'])) : '-' ?></td>
                        <td>
                            <span class="admin__badge admin__badge--<?= $p_lunas ? 'green' : ($p['status_pembayaran'] === 'pending' ? 'blue' : 'gray') ?>">
                                <?= $p_lunas ? 'LUNAS' : ($p['status_pembayaran'] === 'pending' ? 'Pending' : 'Belum') ?>
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap">
                            <?php if (!$p_lunas): ?>
                                <form method="POST" style="display:inline-flex; gap:6px; align-items:center"
                                      onsubmit="return confirm('Konfirmasi pembayaran ' + this.querySelector('select').value + ' sudah diterima?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="lunas" value="<?= (int) $p['id'] ?>">
                                    <select name="metode_pembayaran" class="admin__select-sm" aria-label="Metode bayar">
                                        <option value="bank" <?= $p['metode_pembayaran'] === 'bank' ? 'selected' : '' ?>>Bank</option>
                                        <option value="qris" <?= $p['metode_pembayaran'] === 'qris' ? 'selected' : '' ?>>QRIS</option>
                                        <option value="ewallet" <?= $p['metode_pembayaran'] === 'ewallet' ? 'selected' : '' ?>>E-Wallet</option>
                                    </select>
                                    <button type="submit" class="admin__btn admin__btn--amber admin__btn--sm">Konfirmasi Lunas</button>
                                </form>
                            <?php else: ?>
                                <?php if ($p['no_invoice'] !== ''): ?>
                                <a class="admin__btn admin__btn--primary admin__btn--sm" target="_blank"
                                   href="/pages/bayar/struk.php?no=<?= urlencode($p['no_invoice']) ?>">Struk</a>
                                <a class="admin__btn admin__btn--green admin__btn--sm"
                                   href="/pages/bayar/struk-pdf.php?no=<?= urlencode($p['no_invoice']) ?>">PDF</a>
                                <?php endif; ?>
                                <?php if (empty($p['verif_token'] ?? '') && $p['no_invoice'] !== ''): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="verifikasi_ulang" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="admin__btn admin__btn--amber admin__btn--sm" title="Daftarkan struk ke verifikasi dokumen">Verifikasi</button>
                                </form>
                                <?php elseif (!empty($p['verif_token'] ?? '')): ?>
                                <span class="admin__badge admin__badge--green" title="Terverifikasi: <?= htmlspecialchars($p['verif_code'] ?? '') ?>">✓ <?= htmlspecialchars($p['verif_code'] ?? '') ?></span>
                                <?php endif; ?>
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="batal_lunas" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="admin__btn admin__btn--danger-light admin__btn--sm" title="Batalkan status LUNAS">Batal</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="display:inline"
                                  data-nama="<?= htmlspecialchars($p['nama'], ENT_QUOTES) ?>"
                                  onsubmit="return confirm('Hapus permanen data pendaftar ' + (this.dataset.nama || '') + '? Tindakan ini tidak bisa dibatalkan.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hapus_pendaftar" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="admin__btn admin__btn--danger admin__btn--sm" title="Hapus data pendaftar">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">receipt_long</span>
            Tidak ada pengajuan berstatus "<?= $tabs[$statusFilter]['label'] ?>".
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>