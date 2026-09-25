<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pembayaran.php';

require_admin();

require_once __DIR__ . '/../config/pendaftar-admin-actions.php';

$pendaftar = $pdo->query("SELECT * FROM pendaftar ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

pesan_flash();
$totalBaru = count(array_filter($pendaftar, fn($p) => $p['status'] === 'baru'));
$totalAll  = count($pendaftar);
$totalLunas = count(array_filter($pendaftar, fn($p) => $p['status_pembayaran'] === 'lunas'));
$totalNominal = array_sum(array_map(fn($p) => $p['status_pembayaran'] === 'lunas' ? (int) $p['biaya'] : 0, $pendaftar));

$admin_title = 'Data Pendaftar';
$admin_aktif = 'pendaftar';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">groups</span></div>
        <div><div class="admin__stat-num"><?= $totalAll ?></div><div class="admin__stat-label">Total Pendaftar</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">mark_email_unread</span></div>
        <div><div class="admin__stat-num"><?= $totalBaru ?></div><div class="admin__stat-label">Belum Dihubungi</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">paid</span></div>
        <div><div class="admin__stat-num"><?= $totalLunas ?></div><div class="admin__stat-label">Pembayaran Lunas</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--blue"><span class="material-symbols-rounded">savings</span></div>
        <div><div class="admin__stat-num"><?= rupiah($totalNominal) ?></div><div class="admin__stat-label">Terkumpul (Lunas)</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">Semua Pengajuan Program</div>

    <?php if (count($pendaftar) > 0): ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Enrollment ID</th>
                        <th>Program</th>
                        <th>Nama</th>
                        <th>WhatsApp</th>
                        <th>Biaya</th>
                        <th>Panggilan</th>
                        <th>Jenis Kelamin</th>
                        <th>Usia</th>
                        <th>Metode</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendaftar as $p): ?>
                    <?php $p_lunas = $p['status_pembayaran'] === 'lunas'; ?>
                    <tr>
                        <td><code><?= htmlspecialchars($p['enrollment_id'] ?: '-') ?></code><br><span class="sub">Legacy #<?= (int) $p['id'] ?></span></td>
                        <td><strong><?= htmlspecialchars(ucfirst($p['program'])) ?></strong></td>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><a class="wa" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['whatsapp']) ?>"><?= htmlspecialchars($p['whatsapp']) ?></a></td>
                        <td>
                            <strong><?= $p['biaya'] > 0 ? rupiah((int) $p['biaya']) : '-' ?></strong>
                        </td>
                        <td><?= ($p['nama_panggilan'] ?? '') !== '' ? htmlspecialchars($p['nama_panggilan']) : '<span class="sub">—</span>' ?></td>
                        <td><?= ($p['jenis_kelamin'] ?? '') !== '' ? htmlspecialchars($p['jenis_kelamin']) : '<span class="sub">—</span>' ?></td>
                        <td><?= (int) ($p['usia'] ?? 0) > 0 ? (int) $p['usia'] . ' thn' : '<span class="sub">—</span>' ?></td>
                        <td><?= $p['metode'] !== '' ? htmlspecialchars($p['metode']) : '<span class="sub">—</span>' ?></td>
                        <td><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></td>
                        <td>
                            <span class="admin__badge admin__badge--<?= $p['status'] === 'baru' ? 'amber' : 'green' ?>"><?= $p['status'] === 'baru' ? 'Baru' : 'Dihubungi' ?></span>
                            <br>
                            <span class="admin__badge admin__badge--<?= $p_lunas ? 'green' : ($p['status_pembayaran'] === 'pending' ? 'blue' : 'gray') ?>">
                                <?= $p_lunas ? 'LUNAS' : ($p['status_pembayaran'] === 'pending' ? 'Pending' : 'Belum') ?>
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap">
                            <?php if ($p['status'] === 'baru'): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="dihubungi" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="admin__btn admin__btn--primary admin__btn--sm" title="Tandai sudah dihubungi">Tandai</button>
                                </form>
                            <?php endif; ?>

                            <?php if (!$p_lunas): ?>
                                <form method="POST" style="display:inline-flex; gap:6px; align-items:center" onsubmit="return confirm('Konfirmasi pembayaran ' + this.querySelector('select').value + ' sudah diterima?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="lunas" value="<?= (int) $p['id'] ?>">
                                    <select name="metode_pembayaran" class="admin__select-sm" aria-label="Metode bayar">
                                        <option value="bank" <?= $p['metode_pembayaran'] === 'bank' ? 'selected' : '' ?>>Bank</option>
                                        <option value="qris" <?= $p['metode_pembayaran'] === 'qris' ? 'selected' : '' ?>>QRIS</option>
                                        <option value="ewallet" <?= $p['metode_pembayaran'] === 'ewallet' ? 'selected' : '' ?>>E-Wallet</option>
                                    </select>
                                    <button type="submit" class="admin__btn admin__btn--gold admin__btn--sm">Lunas</button>
                                </form>
                            <?php else: ?>
                                <?php if ($p['no_invoice'] !== ''): ?>
                                <a class="admin__btn admin__btn--primary admin__btn--sm" target="_blank"
                                   href="/pages/bayar/struk.php?no=<?= urlencode($p['no_invoice']) ?>">Struk</a>
                                <a class="admin__btn admin__btn--green admin__btn--sm"
                                   href="/pages/bayar/struk-pdf.php?no=<?= urlencode($p['no_invoice']) ?>">PDF</a>
                                <?php else: ?>
                                <span class="admin__badge--gray" style="font-size:11px;color:#94A3B8">tanpa invoice</span>
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
            <span class="material-symbols-rounded">group_add</span>
            Belum ada pengajuan program. Form offline/online/dauroh di halaman depan akan masuk ke sini.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
