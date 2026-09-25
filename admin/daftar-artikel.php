<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

require_admin();

pesan_flash();

// === Dashboard stats ===
$totalArtikel     = (int) $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$totalPendaftar   = (int) $pdo->query("SELECT COUNT(*) FROM pendaftar")->fetchColumn();
$donasiPending    = (int) $pdo->query("SELECT COUNT(*) FROM donasi WHERE status='pending'")->fetchColumn();
$donasiDiterima   = (int) $pdo->query("SELECT SUM(jumlah) FROM donasi WHERE status='diterima'")->fetchColumn() ?: 0;

// ID artikel berikutnya (public: auto_increment)
$nextId = (int) $pdo->query(
    "SELECT AUTO_INCREMENT FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'articles'"
)->fetchColumn() ?: ($totalArtikel + 1);

// Hapus artikel — WAJIB POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    require_csrf();
    $id = (int) $_POST['hapus'];
    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    flash('Artikel berhasil dihapus.');
    header("Location: daftar-artikel.php");
    exit;
}

// Reset ID artikel — WAJIB POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_id'])) {
    require_csrf();
    if ($totalArtikel > 0) {
        flash_error('Reset ID hanya berfungsi saat tabel artikel kosong. Hapus dulu semua artikel.');
    } else {
        $pdo->exec("ALTER TABLE articles AUTO_INCREMENT = 1");
        $nextId = 1;
        flash('ID artikel berhasil di-reset ke 1.');
    }
    header("Location: daftar-artikel.php");
    exit;
}

// === Artikel terbaru ===
$articles = $pdo->query("SELECT * FROM articles ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$admin_title = 'Artikel';
$admin_aktif = 'artikel';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">article</span></div>
        <div><div class="admin__stat-num"><?= $totalArtikel ?></div><div class="admin__stat-label">Total Artikel</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">groups</span></div>
        <div><div class="admin__stat-num"><?= $totalPendaftar ?></div><div class="admin__stat-label">Pendaftar</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">hourglass_top</span></div>
        <div><div class="admin__stat-num"><?= $donasiPending ?></div><div class="admin__stat-label">Donasi Pending</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--slate"><span class="material-symbols-rounded">check_circle</span></div>
        <div><div class="admin__stat-num">Rp<?= number_format($donasiDiterima, 0, ',', '.') ?></div><div class="admin__stat-label">Donasi Diterima</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">
        <span style="display:inline-flex;align-items:center;gap:10px;">Daftar Artikel <span class="admin__badge admin__badge--blue">ID berikutnya: <?= $nextId ?></span></span>
        <span class="admin__actions">
            <form method="POST" onsubmit="return confirm('Reset ID artikel ke 1? (Efektif hanya bila tidak ada artikel)')">
                <?= csrf_field() ?>
                <input type="hidden" name="reset_id" value="1">
                <button type="submit" class="admin__btn admin__btn--amber admin__btn--sm">
                    <span class="material-symbols-rounded">restart_alt</span> Reset ID
                </button>
            </form>
            <a href="tambah-artikel.php" class="admin__btn admin__btn--primary admin__btn--sm">
                <span class="material-symbols-rounded">add</span> Tulis Baru
            </a>
        </span>
    </div>

    <?php if (count($articles) > 0): ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul</th>
                        <th>Author</th>
                        <th>Tanggal</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $art): ?>
                    <tr>
                        <td><?= $art['id'] ?></td>
                        <td><strong><?= htmlspecialchars($art['judul']) ?></strong></td>
                        <td><?= htmlspecialchars($art['author']) ?></td>
                        <td><?= date('d M Y', strtotime($art['tanggal'])) ?></td>
                        <td style="text-align:right">
                            <div class="admin__actions">
                                <a href="../pages/artikel/detail.php?id=<?= (int) $art['id'] ?>" class="admin__btn admin__btn--info admin__btn--sm" target="_blank">
                                    <span class="material-symbols-rounded">open_in_new</span> Lihat
                                </a>
                                <form method="POST" onsubmit="return confirm('Hapus artikel \'<?= htmlspecialchars(addslashes($art['judul'])) ?>\'?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="hapus" value="<?= (int) $art['id'] ?>">
                                    <button type="submit" class="admin__btn admin__btn--danger admin__btn--sm">Hapus</button>
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
            <span class="material-symbols-rounded">article</span>
            Belum ada artikel. Silakan tulis artikel pertamamu.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
