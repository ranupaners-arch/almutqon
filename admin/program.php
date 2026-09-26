<?php
// Kelola harga, sesi, dan kode diskon program.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pembayaran.php';
require_admin();

function program_redirect(): never { header('Location: program.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (isset($_POST['simpan_program'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $harga = (int) ($_POST['harga'] ?? -1);
        $durasiLabel = trim((string) ($_POST['durasi_label'] ?? ''));
        $minPeserta = max(1, (int) ($_POST['min_peserta'] ?? 1));
        if ($id < 1 || $harga < 0 || mb_strlen($durasiLabel) > 120) {
            flash_error('Nilai harga atau durasi tidak valid.');
        } else {
            $st = $pdo->prepare('UPDATE program_prices SET harga = ?, durasi_label = ?, min_peserta = ? WHERE id = ?');
            $st->execute([$harga, $durasiLabel, $minPeserta, $id]);
            flash('Konfigurasi program diperbarui. Pendaftar lama tetap memakai harga snapshot.');
        }
        program_redirect();
    }
    if (isset($_POST['tambah_diskon'])) {
        $kode = strtoupper(trim((string) ($_POST['kode'] ?? '')));
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $tipe = $_POST['tipe'] ?? '';
        $nilai = (int) ($_POST['nilai'] ?? 0);
        $program = $_POST['program'] ?? 'semua';
        $metode = trim((string) ($_POST['metode'] ?? ''));
        $mulai = trim((string) ($_POST['mulai'] ?? '')) ?: null;
        $berakhir = trim((string) ($_POST['berakhir'] ?? '')) ?: null;
        $batas = trim((string) ($_POST['batas_penggunaan'] ?? ''));
        $validKode = preg_match('/^[A-Z0-9_-]{3,40}$/', $kode) === 1;
        $validTipe = in_array($tipe, ['persen', 'nominal'], true) && $nilai > 0 && ($tipe !== 'persen' || $nilai <= 100);
        if (!$validKode || $nama === '' || mb_strlen($nama) > 120 || !$validTipe || !in_array($program, ['semua', 'offline', 'online', 'dauroh'], true) || mb_strlen($metode) > 30) {
            flash_error('Data diskon tidak valid.');
        } else {
            try {
                $st = $pdo->prepare('INSERT INTO program_discounts (kode, nama, tipe, nilai, program, metode, mulai, berakhir, batas_penggunaan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $st->execute([$kode, $nama, $tipe, $nilai, $program, $metode, $mulai, $berakhir, $batas === '' ? null : max(1, (int) $batas)]);
                flash('Diskon berhasil dibuat.');
            } catch (PDOException $e) {
                flash_error($e->getCode() === '23000' ? 'Kode diskon sudah digunakan.' : 'Diskon tidak dapat disimpan.');
            }
        }
        program_redirect();
    }
    if (isset($_POST['ubah_status_diskon'])) {
        $id = (int) $_POST['ubah_status_diskon'];
        $pdo->prepare('UPDATE program_discounts SET aktif = IF(aktif = 1, 0, 1) WHERE id = ?')->execute([$id]);
        flash('Status diskon diperbarui.');
        program_redirect();
    }
}

$rows = $pdo->query('SELECT * FROM program_prices ORDER BY FIELD(program, "offline", "online", "dauroh"), id')->fetchAll(PDO::FETCH_ASSOC);
$discounts = $pdo->query('SELECT * FROM program_discounts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
pesan_flash();
$admin_title = 'Harga & Diskon Program';
$admin_aktif = 'program';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?><div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($pesan_sukses) ?></div><?php endif; ?>

<div class="admin__card">
    <div class="admin__card-title">Harga dan Sesi</div>
    <p class="sub" style="margin:6px 0 16px">Perubahan berlaku untuk pendaftaran baru. Harga pendaftar lama tidak berubah.</p>
    <div class="admin__table-wrap"><table class="admin__table"><thead><tr><th>Program</th><th>Harga final</th><th>Durasi / keterangan</th><th>Min. peserta</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($rows as $r): $formId = 'program-' . (int) $r['id']; ?><tr><td><strong><?= htmlspecialchars(ucfirst($r['program'])) ?> <?= htmlspecialchars($r['metode']) ?></strong><input form="<?= $formId ?>" type="hidden" name="id" value="<?= (int) $r['id'] ?>"></td><td><input form="<?= $formId ?>" class="admin__input" style="width:140px" type="number" min="0" name="harga" value="<?= (int) $r['harga'] ?>" required></td><td><input form="<?= $formId ?>" class="admin__input" style="width:210px" type="text" name="durasi_label" value="<?= htmlspecialchars($r['durasi_label']) ?>" placeholder="Contoh: 2 jam / sesi"></td><td><input form="<?= $formId ?>" class="admin__input" style="width:90px" type="number" min="1" name="min_peserta" value="<?= (int) $r['min_peserta'] ?>"></td><td><form id="<?= $formId ?>" method="POST"><?= csrf_field() ?></form><button form="<?= $formId ?>" class="admin__btn admin__btn--primary admin__btn--sm" name="simpan_program" value="1">Simpan</button></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>

<div class="admin__card"><div class="admin__card-title">Buat Diskon</div>
<form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:12px">
<?= csrf_field() ?><div><label class="admin__form-label">Kode</label><input class="admin__input" name="kode" required maxlength="40" placeholder="RAMADAN26"></div><div style="flex:1;min-width:180px"><label class="admin__form-label">Nama diskon</label><input class="admin__input" name="nama" required maxlength="120" placeholder="Promo Ramadan"></div><div><label class="admin__form-label">Jenis</label><select class="admin__input" name="tipe"><option value="persen">Persen</option><option value="nominal">Nominal</option></select></div><div><label class="admin__form-label">Nilai</label><input class="admin__input" type="number" name="nilai" min="1" required></div><div><label class="admin__form-label">Program</label><select class="admin__input" name="program"><option value="semua">Semua</option><option value="offline">Offline</option><option value="online">Online</option><option value="dauroh">Dauroh</option></select></div><div><label class="admin__form-label">Metode (opsional)</label><input class="admin__input" name="metode" maxlength="30" placeholder="Privat"></div><div><label class="admin__form-label">Mulai</label><input class="admin__input" type="datetime-local" name="mulai"></div><div><label class="admin__form-label">Berakhir</label><input class="admin__input" type="datetime-local" name="berakhir"></div><div><label class="admin__form-label">Batas pakai</label><input class="admin__input" type="number" min="1" name="batas_penggunaan" placeholder="Tanpa batas"></div><button class="admin__btn admin__btn--primary" name="tambah_diskon" value="1">Buat Diskon</button>
</form></div>

<div class="admin__card"><div class="admin__card-title">Daftar Diskon</div><div class="admin__table-wrap"><table class="admin__table"><thead><tr><th>Kode</th><th>Potongan</th><th>Cakupan</th><th>Masa berlaku</th><th>Pemakaian</th><th>Status</th><th></th></tr></thead><tbody>
<?php if (!$discounts): ?><tr><td colspan="7">Belum ada diskon.</td></tr><?php endif; ?>
<?php foreach ($discounts as $d): ?><tr><td><strong><?= htmlspecialchars($d['kode']) ?></strong><br><span class="sub"><?= htmlspecialchars($d['nama']) ?></span></td><td><?= $d['tipe'] === 'persen' ? (int) $d['nilai'] . '%' : 'Rp' . number_format((int) $d['nilai'], 0, ',', '.') ?></td><td><?= htmlspecialchars(ucfirst($d['program']) . ($d['metode'] ? ' · ' . $d['metode'] : '')) ?></td><td class="sub"><?= htmlspecialchars(($d['mulai'] ?: 'sekarang') . ' — ' . ($d['berakhir'] ?: 'selamanya')) ?></td><td><?= (int) $d['terpakai'] ?> / <?= $d['batas_penggunaan'] ? (int) $d['batas_penggunaan'] : '∞' ?></td><td><span class="admin__badge admin__badge--<?= $d['aktif'] ? 'green' : 'gray' ?>"><?= $d['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><form method="POST"><?= csrf_field() ?><button class="admin__btn admin__btn--sm" name="ubah_status_diskon" value="<?= (int) $d['id'] ?>"><?= $d['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
