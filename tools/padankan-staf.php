<?php
require_once __DIR__ . '/cli-only.php';

// tools/padankan-staf.php — pairing satu-kali ID pengajar almutqon ↔ staff verif.almutqon.
// Aturan (diputuskan 18 Sep, format ID TIDAK diubah):
//   - Pasangan eksplisit: users.id=1 (direktur PGJ-1520) ↔ staff.id=2 (NIP 202501001
//     diubah menjadi PGJ-1520; login lama mati, password tak berubah).
//   - Personel lain ber-id_pengajar: cocokkan staff by NIP; bila tak ada, buatkan baris
//     baru (NIP = id_pengajar, role by peran_to_verif_role, password acak → aktivasi
//     mandiri via reset oleh direktur/admin). Cocok email saja (NIP beda) = ambigu,
//     dilaporkan & dilewati (butuh keputusan eksplisit seperti kasus direktur).
//   - Tak disentuh: user murni, staff SYSTEM-*, password/role baris existing.
// Aman diulang: default DRY-RUN; pakai --eksekusi untuk menerapkan.
//   php tools/padankan-staf.php [--eksekusi]
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pengajar.php';

// Tool ini hanya untuk pairing legacy PGJ-XXXX. Setelah schema identitas baru aktif,
// menjalankannya kembali dapat mengembalikan NIP ke format lama.
$identitySchema = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'staff_id'"
)->fetchColumn();
if ((int) $identitySchema > 0) {
    fwrite(STDERR, "Tool pairing legacy dinonaktifkan. Gunakan tools/sinkron-identitas-staf.php.\n");
    exit(2);
}

$eksekusi = in_array('--eksekusi', $argv, true);

$ver = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=db_almutqon-verif;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$personel = $pdo->query(
    "SELECT id, name, email, peran, id_pengajar, aktif, verif_staff_id
     FROM users WHERE peran <> 'user' ORDER BY id"
)->fetchAll();
$staff = $ver->query("SELECT * FROM staff ORDER BY id")->fetchAll();
$byNip = [];
$byEmail = [];
foreach ($staff as $s) {
    $byNip[$s['nip']][] = $s;
    $byEmail[strtolower($s['email'])][] = $s;
}

$plan = [];   // ['jenis'=>..., 'teks'=>..., 'fn'=>callable-string...]
$info = [];
$konflik = [];

// 1. Pasangan eksplisit direktur.
$u1 = null;
foreach ($personel as $p) {
    if ((int) $p['id'] === 1) {
        $u1 = $p;
    }
}
$s2 = null;
foreach ($staff as $s) {
    if ((int) $s['id'] === 2) {
        $s2 = $s;
    }
}
if ($u1 && $s2) {
    if (($u1['peran'] ?? '') === 'direktur' && ($u1['id_pengajar'] ?? '') === 'PGJ-1520'
        && ($s2['role'] ?? '') === 'direktur' && !str_starts_with((string) $s2['nip'], 'SYSTEM-')) {
        if (($s2['nip'] ?? '') !== 'PGJ-1520') {
            if (isset($byNip['PGJ-1520'])) {
                $konflik[] = 'NIP PGJ-1520 sudah dipakai staff.id=' . $byNip['PGJ-1520'][0]['id'] . ' — batalkan pasangan direktur.';
            } else {
                $plan[] = ['t' => "Ubah NIP staff.id=2 '{$s2['nip']}' → 'PGJ-1520' + tautkan users.id=1 ↔ staff.id=2"];
            }
        } elseif ((int) ($u1['verif_staff_id'] ?? 0) !== 2 || (int) ($s2['almutqon_user_id'] ?? 0) !== 1) {
            $plan[] = ['t' => 'Tautkan users.id=1 ↔ staff.id=2 (NIP sudah PGJ-1520)'];
        } else {
            $info[] = 'Direktur sudah terpadankan (users.id=1 ↔ staff.id=2, NIP PGJ-1520).';
        }
    } else {
        $konflik[] = 'Pasangan eksplisit tak memenuhi syarat (peran/NIP/service). Periksa manual users.id=1 & staff.id=2.';
    }
}

// 2. Personel lain.
foreach ($personel as $p) {
    if ((int) $p['id'] === 1) {
        continue; // sudah ditangani di atas
    }
    $nip = (string) ($p['id_pengajar'] ?? '');
    if ($nip === '') {
        $info[] = "users.id={$p['id']} ({$p['name']}) tanpa id_pengajar — dilewati.";
        continue;
    }
    if (!empty($p['verif_staff_id'])) {
        $info[] = "users.id={$p['id']} sudah tertaut ke staff.id={$p['verif_staff_id']}.";
        continue;
    }
    if (isset($byNip[$nip])) {
        $s = $byNip[$nip][0];
        if (!empty($s['almutqon_user_id']) && (int) $s['almutqon_user_id'] !== (int) $p['id']) {
            $konflik[] = "NIP {$nip} tertaut ke users.id={$s['almutqon_user_id']} — bukan users.id={$p['id']}.";
        } else {
            $plan[] = ['t' => "Tautkan users.id={$p['id']} ({$p['name']}) ↔ staff.id={$s['id']} (NIP {$nip})", 'link' => [(int) $p['id'], (int) $s['id']]];
        }
        continue;
    }
    $em = strtolower((string) $p['email']);
    if (isset($byEmail[$em])) {
        $konflik[] = "users.id={$p['id']} ({$p['email']}) cocok email staff.id={$byEmail[$em][0]['id']} tapi NIP beda — butuh keputusan eksplisit.";
        continue;
    }
    $plan[] = ['t' => "Buat staff baru NIP {$nip} untuk {$p['name']} (" . peran_label($p['peran']) . ' → ' . peran_to_verif_role($p['peran']) . ') + tautkan', 'baru' => $p];
}

// 3. Laporan staf verif tanpa pasangan (info saja).
foreach ($staff as $s) {
    if (!empty($s['almutqon_user_id']) || str_starts_with((string) $s['nip'], 'SYSTEM-')) {
        continue;
    }
    $paired = false;
    foreach ($personel as $p) {
        if ((int) ($p['verif_staff_id'] ?? 0) === (int) $s['id']) {
            $paired = true;
        }
    }
    if (!$paired) {
        $info[] = "staff.id={$s['id']} ({$s['nip']}) tanpa pasangan almutqon — dibiarkan.";
    }
}

echo "=== RENCANA PADANAN ===\n";
foreach ($plan as $a) {
    echo '  + ' . $a['t'] . "\n";
}
foreach ($konflik as $k) {
    echo '  ! KONFLIK: ' . $k . "\n";
}
foreach ($info as $i) {
    echo '  = ' . $i . "\n";
}
if (count($konflik) > 0) {
    echo "Ada konflik — selesaikan manual sebelum eksekusi.\n";
    exit(2);
}
if (!$eksekusi) {
    echo 'DRY-RUN: tidak ada perubahan. Ulangi dengan --eksekusi untuk menerapkan.' . "\n";
    exit(0);
}

// --- Eksekusi ---
foreach ($plan as $a) {
    if (isset($a['baru'])) {
        $p = $a['baru'];
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        $ins = $ver->prepare('INSERT INTO staff (nip, nama_lengkap, jabatan, email, password, role, is_active) VALUES (?,?,?,?,?,?,?)');
        $ins->execute([
            (string) $p['id_pengajar'], (string) $p['name'], peran_label($p['peran']),
            (string) $p['email'], $hash, peran_to_verif_role($p['peran']), (int) $p['aktif'] ? 1 : 0,
        ]);
        $sid = (int) $ver->lastInsertId();
        $ver->prepare('UPDATE staff SET almutqon_user_id = ? WHERE id = ?')->execute([(int) $p['id'], $sid]);
        $pdo->prepare('UPDATE users SET verif_staff_id = ? WHERE id = ?')->execute([$sid, (int) $p['id']]);
        echo "  OK buat staff.id={$sid} + taut users.id={$p['id']}\n";
    } elseif (isset($a['link'])) {
        [$uid, $sid] = $a['link'];
        $ver->prepare('UPDATE staff SET almutqon_user_id = ? WHERE id = ?')->execute([$uid, $sid]);
        $pdo->prepare('UPDATE users SET verif_staff_id = ? WHERE id = ?')->execute([$sid, $uid]);
        echo "  OK taut users.id={$uid} ↔ staff.id={$sid}\n";
    } else {
        // Pasangan eksplisit direktur.
        $ver->prepare("UPDATE staff SET nip = 'PGJ-1520', almutqon_user_id = 1 WHERE id = 2")->execute();
        $pdo->prepare('UPDATE users SET verif_staff_id = 2 WHERE id = 1')->execute();
        echo "  OK NIP staff.id=2 → PGJ-1520 + taut users.id=1\n";
    }
}
echo "Selesai: " . count($plan) . " aksi diterapkan.\n";
