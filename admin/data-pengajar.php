<?php
// admin/data-pengajar.php — kelola tenaga pengajar: tambah, ubah peran, revoke/aktifkan akses, hapus.
// Peran: Direktur + 4 Koordinator Marhalah + Pengajar. Staff ID baru dan ID PGJ legacy dibuat otomatis.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pengajar.php';
require_once __DIR__ . '/../config/verif-client.php';

require_admin();

$roles = peran_pengajar_options();
$selfId = (int) ($_SESSION['user']['id'] ?? 0);

// Tambah pengajar baru — atau naikkan peran bila email sudah terdaftar.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pengajar'])) {
    require_csrf();
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $peran = $_POST['peran'] ?? '';
    if ($nama === '' || mb_strlen($nama) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || mb_strlen($email) > 150 || !isset($roles[$peran])) {
        flash_error('Isian tidak valid. Periksa nama, email, dan peran.');
    } else {
        $stmt = $pdo->prepare("SELECT id, member_id, staff_id, id_pengajar FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $ada = $stmt->fetch();
        if ($ada) {
            $identityOk = false;
            try {
                $pdo->beginTransaction();
                $idPengajar = $ada['id_pengajar'] ?: buat_id_pengajar($pdo);
                $memberId = ensure_user_member_id($pdo, (int) $ada['id']);
                $currentStaffId = (string) ($ada['staff_id'] ?? '');
                if ($currentStaffId !== '' && !identity_is_staff_id($currentStaffId)) {
                    throw new RuntimeException('Staff ID existing tidak valid dan memerlukan review manual.');
                }
                $staffId = $currentStaffId !== '' ? $currentStaffId : generate_staff_id($pdo, (int) date('Y'));
                $up = $pdo->prepare("UPDATE users SET name = ?, peran = ?, id_pengajar = ?, staff_id = ?, aktif = 1 WHERE id = ?");
                $up->execute([$nama, $peran, $idPengajar, $staffId, (int) $ada['id']]);
                $pdo->commit();
                $identityOk = true;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('[staff-identity] ' . $e->getMessage());
                flash_error('Identitas staf tidak dapat dibuat. Data pengajar tidak diubah.');
            }
            if ($identityOk) {
                $msg = "{$email} kini berperan {$roles[$peran]} (Staff ID: {$staffId}).";
                $su = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                $su->execute([(int) $ada['id']]);
                $vs = verif_sync_pengajar($pdo, $su->fetch() ?: [], 'upsert');
                $msg .= $vs['ok'] ? ' Staff verifikasi tersinkron.' : (!$vs['skipped'] ? ' (Sinkron verifikasi gagal, dicatat di log.)' : '');
                flash($msg);
            }
        } else {
            if (strlen($password) < 6) {
                flash_error('Password baru minimal 6 karakter.');
            } else {
                $identityOk = false;
                try {
                    $pdo->beginTransaction();
                    $memberId = generate_member_id($pdo);
                    $staffId = generate_staff_id($pdo, (int) date('Y'));
                    $idPengajar = buat_id_pengajar($pdo);
                    $ins = $pdo->prepare("INSERT INTO users (member_id, staff_id, name, email, password, peran, id_pengajar, aktif) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([$memberId, $staffId, $nama, $email, password_hash($password, PASSWORD_BCRYPT), $peran, $idPengajar]);
                    $newUserId = (int) $pdo->lastInsertId();
                    $pdo->commit();
                    $identityOk = true;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log('[staff-identity] ' . $e->getMessage());
                    flash_error('Pengajar tidak dapat ditambahkan karena nomor identitas gagal dibuat.');
                }
                if ($identityOk) {
                    $msg = "Pengajar {$nama} ditambahkan (Staff ID: {$staffId}).";
                    $su = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                    $su->execute([$newUserId]);
                    $vs = verif_sync_pengajar($pdo, $su->fetch() ?: [], 'upsert');
                    $msg .= $vs['ok'] ? ' Staff verifikasi tersinkron.' : (!$vs['skipped'] ? ' (Sinkron verifikasi gagal, dicatat di log.)' : '');
                    flash($msg);
                }
            }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Revoke (nonaktifkan) / aktifkan kembali akses login. Akun sendiri dilindungi.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_aktif'])) {
    require_csrf();
    $id = (int) $_POST['toggle_aktif'];
    if ($id === $selfId) {
        flash_error('Tidak bisa menonaktifkan akun sendiri.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND peran != 'user' LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_error('Data pengajar tidak ditemukan.');
        } else {
            $baru = ((int) $row['aktif'] === 1) ? 0 : 1;
            $pdo->prepare("UPDATE users SET aktif = ? WHERE id = ?")->execute([$baru, $id]);
            $row['aktif'] = $baru;
            $msg = $baru === 1
                ? "Akses {$row['name']} diaktifkan kembali."
                : "Akses {$row['name']} dicabut (revoke). Ia tidak bisa login sampai diaktifkan lagi.";
            $vs = verif_sync_pengajar($pdo, $row, 'toggle');
            $msg .= $vs['ok'] ? ' Staff verifikasi tersinkron.' : (!$vs['skipped'] ? ' (Sinkron verifikasi gagal — dicatat di log.)' : '');
            flash($msg);
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Reset password pengajar: buat acak 8 karakter, tampilkan SEKALI via flash.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_pass'])) {
    require_csrf();
    $id = (int) $_POST['reset_pass'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND peran != 'user' LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_error('Data pengajar tidak ditemukan.');
    } else {
        $baru = bin2hex(random_bytes(4));
        $newHash = password_hash($baru, PASSWORD_DEFAULT);
        $sync = verif_sync_password($pdo, $row, $newHash);
        if (!$sync['ok']) {
            error_log('[admin-reset-password] sinkronisasi verif gagal: ' . $sync['error']);
            flash_error('Password tidak diubah karena sinkronisasi verifikasi gagal.');
        } else {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $id]);
            flash("Password {$row['name']} direset menjadi: {$baru} — sampaikan ke pengajar, password ini hanya tampil sekali.");
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Hapus permanen. Akun sendiri dilindungi.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pengajar'])) {
    require_csrf();
    $id = (int) $_POST['hapus_pengajar'];
    if ($id === $selfId) {
        flash_error('Tidak bisa menghapus akun sendiri.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND peran != 'user' LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_error('Data pengajar tidak ditemukan.');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $msg = "Pengajar \"{$row['name']}\" dihapus permanen.";
            $vs = verif_sync_pengajar($pdo, $row, 'lepas');
            $msg .= $vs['ok'] ? ' Staff verifikasi ikut dinonaktifkan.' : (!$vs['skipped'] ? ' (Sinkron verifikasi gagal — dicatat di log.)' : '');
            flash($msg);
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$pengajar = $pdo->query("SELECT id, staff_id, name, email, peran, id_pengajar, aktif, verif_staff_id, created_at FROM users WHERE peran != 'user' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

pesan_flash();
$totalAktif = count(array_filter($pengajar, fn($p) => (int) $p['aktif'] === 1));
$totalDirektur = count(array_filter($pengajar, fn($p) => $p['peran'] === 'direktur'));
$totalKoor = count(array_filter($pengajar, fn($p) => str_starts_with($p['peran'], 'koor_')));

$badgePeran = ['direktur' => 'red', 'pengajar' => 'blue'];
$admin_title = 'Data Pengajar';
$admin_aktif = 'pengajar';
include __DIR__ . '/../layouts/admin-header.php';
?>

<?php if ($pesan_sukses): ?>
    <div class="admin__alert admin__alert--<?= $jenis_pesan === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($pesan_sukses) ?>
    </div>
<?php endif; ?>

<div class="admin__stats">
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--teal"><span class="material-symbols-rounded">supervisor_account</span></div>
        <div><div class="admin__stat-num"><?= count($pengajar) ?></div><div class="admin__stat-label">Tenaga Pengajar</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--green"><span class="material-symbols-rounded">check_circle</span></div>
        <div><div class="admin__stat-num"><?= $totalAktif ?></div><div class="admin__stat-label">Akses Aktif</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--amber"><span class="material-symbols-rounded">stars</span></div>
        <div><div class="admin__stat-num"><?= $totalDirektur ?></div><div class="admin__stat-label">Direktur</div></div>
    </div>
    <div class="admin__stat">
        <div class="admin__stat-icon admin__stat-icon--blue"><span class="material-symbols-rounded">groups</span></div>
        <div><div class="admin__stat-num"><?= $totalKoor ?></div><div class="admin__stat-label">Koordinator</div></div>
    </div>
</div>

<div class="admin__card">
    <div class="admin__card-title">Tambah Pengajar</div>
    <p style="font-size:13px; color:var(--admin-muted); margin:4px 0 0;">Staff ID dibuat otomatis. ID Pengajar lama tetap disimpan untuk kompatibilitas. Bila email sudah terdaftar, perannya dinaikkan.</p>
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
        <?= csrf_field() ?>
        <div style="flex:2; min-width:180px;">
            <label class="admin__form-label" for="pg_nama">Nama Lengkap</label>
            <input type="text" id="pg_nama" name="nama" class="admin__input" required maxlength="100" placeholder="Contoh: Ustaz Ahmad">
        </div>
        <div style="flex:2; min-width:200px;">
            <label class="admin__form-label" for="pg_email">Email</label>
            <input type="email" id="pg_email" name="email" class="admin__input" required maxlength="150" placeholder="nama@email.com">
        </div>
        <div style="flex:1; min-width:150px;">
            <label class="admin__form-label" for="pg_pass">Password</label>
            <input type="password" id="pg_pass" name="password" class="admin__input" minlength="6" placeholder="Min. 6 karakter">
        </div>
        <div style="flex:1; min-width:200px;">
            <label class="admin__form-label" for="pg_peran">Peran</label>
            <select id="pg_peran" name="peran" class="admin__input admin__select" aria-label="Peran pengajar">
                <?php foreach ($roles as $val => $label): ?>
                    <option value="<?= $val ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="tambah_pengajar" value="1" class="admin__btn admin__btn--primary">Tambah</button>
    </form>
</div>

<div class="admin__card">
    <div class="admin__card-title">Daftar Tenaga Pengajar</div>

    <?php if (count($pengajar) > 0): ?>
        <div class="admin__table-wrap">
            <table class="admin__table">
                <thead>
                    <tr>
                        <th>Identitas</th>
                        <th>Nama / Email</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pengajar as $p): ?>
                    <?php $aktif = (int) $p['aktif'] === 1; ?>
                    <tr>
                        <td>
                            <strong>Staff ID:</strong> <code><?= htmlspecialchars($p['staff_id'] ?: '-') ?></code><br>
                            <span class="sub">ID Pengajar lama: <code><?= htmlspecialchars($p['id_pengajar'] ?: '-') ?></code></span>
                        </td>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong><br><span class="sub"><?= htmlspecialchars($p['email']) ?></span></td>
                        <td><span class="admin__badge admin__badge--<?= $badgePeran[$p['peran']] ?? 'amber' ?>"><?= htmlspecialchars(peran_label($p['peran'])) ?></span></td>
                        <td><span class="admin__badge admin__badge--<?= $aktif ? 'green' : 'gray' ?>"><?= $aktif ? 'Aktif' : 'Dicabut' ?></span>
                        <?php if ($p['peran'] === 'direktur'): ?>
                            <span class="sub" title="Peran direktur disinkron manual di dashboard verifikasi">manual</span>
                        <?php elseif (!empty($p['verif_staff_id'])): ?>
                            <span class="admin__badge admin__badge--green" title="Tersinkron ke staff verifikasi">✓</span>
                        <?php elseif (!empty($p['id_pengajar'])): ?>
                            <span class="admin__badge admin__badge--gray" title="Belum tersinkron ke verifikasi">…</span>
                        <?php endif; ?></td>
                        <td style="text-align:right; white-space:nowrap">
                            <form method="POST" style="display:inline" onsubmit="return confirm('<?= $aktif ? 'Cabut' : 'Aktifkan kembali' ?> akses <?= htmlspecialchars(addslashes($p['name'])) ?>?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="toggle_aktif" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="admin__btn <?= $aktif ? 'admin__btn--danger-light' : 'admin__btn--green' ?> admin__btn--sm"><?= $aktif ? 'Revoke' : 'Aktifkan' ?></button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Reset password <?= htmlspecialchars(addslashes($p['name'])) ?>? Password baru hanya tampil sekali.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="reset_pass" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="admin__btn admin__btn--amber admin__btn--sm" title="Reset password pengajar">Reset</button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus permanen <?= htmlspecialchars(addslashes($p['name'])) ?>? Tindakan ini tidak bisa dibatalkan.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hapus_pengajar" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="admin__btn admin__btn--danger admin__btn--sm" title="Hapus data pengajar">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin__empty">
            <span class="material-symbols-rounded">supervisor_account</span>
            Belum ada tenaga pengajar. Tambahkan lewat formulir di atas.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
