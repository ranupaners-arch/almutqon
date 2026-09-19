<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/donasi.php';

require_admin();

// Hapus donasi — WAJIB POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    require_csrf();
    $id = (int) $_POST['hapus'];
    $stmt = $pdo->prepare("DELETE FROM donasi WHERE id = ?");
    $stmt->execute([$id]);
    flash('Donasi berhasil dihapus.');
    header("Location: data-donasi.php");
    exit;
}

// Tandai donasi DITERIMA — WAJIB POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['diterima'])) {
    require_csrf();
    $id = (int) $_POST['diterima'];
    $stmt = $pdo->prepare("UPDATE donasi SET status = 'diterima' WHERE id = ?");
    $stmt->execute([$id]);
    flash('Donasi ditandai DITERIMA.');
    header("Location: data-donasi.php");
    exit;
}

$donasi = $pdo->query("SELECT * FROM donasi ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

pesan_flash();

$totalPending = array_sum(array_map(fn($d) => $d['status'] === 'pending' ? (int) $d['jumlah'] : 0, $donasi));
$totalTerima  = array_sum(array_map(fn($d) => $d['status'] === 'diterima' ? (int) $d['jumlah'] : 0, $donasi));

$admin_title = 'Data Donasi';
$admin_aktif = 'donasi';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">hourglass_top</span></div>
        <div><div class="admin__stat-num">Rp<?= number_format($totalPending, 0, ',', '.') ?></div><div class="admin__stat-label">Sedang Menunggu</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">check_circle</span></div>
        <div><div class="admin__stat-num">Rp<?= number_format($totalTerima, 0, ',', '.') ?></div><div class="admin__stat-label">Donasi Diterima</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--slate"><span class="material-symbols-rounded">payments</span></div>
        <div><div class="admin__stat-num"><?= count($donasi) ?></div><div class="admin__stat-label">Total Data</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">pending_actions</span></div>
        <div><div class="admin__stat-num"><?= count(array_filter($donasi, fn($d) => $d['status'] === 'pending')) ?></div><div class="admin__stat-label">Pending</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">Riwayat Donasi
        <a class="admin__btn admin__btn--green admin__btn--sm" href="export-donasi.php" title="Unduh donasi DITERIMA sebagai CSV">
            <span class="material-symbols-rounded" style="font-size:16px; vertical-align:-3px;">download</span> Unduh CSV
        </a>
    </div>

    <?php if (count($donasi) > 0): ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Donatur</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donasi as $d):
                        $metodeLabel = ['bank' => 'Transfer Bank', 'qris' => 'QRIS', 'ewallet' => 'E-Wallet'][$d['metode']] ?? $d['metode'];
                        $kanalLabel = donasi_kanal_label((string) $d['metode'], (string) ($d['kanal_pembayaran'] ?? ''));
                        if ($kanalLabel !== '') $metodeLabel .= ' · ' . $kanalLabel;
                        $badge = $d['status'] === 'diterima' ? 'green' : 'amber';
                        $badgeLabel = $d['status'] === 'diterima' ? 'Diterima' : 'Pending';
                    ?>
                    <tr>
                        <td><?= (int) $d['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($d['nama']) ?></strong>
                            <br><a class="wa" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $d['whatsapp']) ?>"><?= htmlspecialchars($d['whatsapp']) ?></a>
                            <?php if ($d['user_email']): ?><br><span class="sub"><?= htmlspecialchars($d['user_email']) ?></span><?php endif; ?>
                        </td>
                        <td><strong>Rp<?= number_format((int) $d['jumlah'], 0, ',', '.') ?></strong></td>
                        <td><?= $metodeLabel ?></td>
                        <td><?= date('d M Y H:i', strtotime($d['created_at'])) ?></td>
                        <td><span class="admin__badge admin__badge--<?= $badge ?>"><?= $badgeLabel ?></span></td>
                        <td style="text-align:right">
                            <div class="admin__actions" style="justify-content:flex-end">
                                <?php if ($d['status'] === 'pending'): ?>
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="diterima" value="<?= (int) $d['id'] ?>">
                                        <button type="submit" class="admin__btn admin__btn--primary admin__btn--sm">Terima</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('Hapus donasi Rp<?= number_format((int) $d['jumlah'], 0, ',', '.') ?> dari <?= htmlspecialchars(addslashes($d['nama'])) ?>?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="hapus" value="<?= (int) $d['id'] ?>">
                                    <button type="submit" class="admin__btn admin__btn--danger-light admin__btn--sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">payments</span>
            Belum ada donasi. Form donasi di beranda akan masuk ke sini.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>