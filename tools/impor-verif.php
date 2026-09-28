<?php
require_once __DIR__ . '/cli-only.php';

// tools/impor-verif.php — impor sekali jalan: daftarkan invoice LUNAS lama yang belum
// terverifikasi ke API internal verif.almutqon v1 agar ID sinkron (no_surat = no_invoice).
// Idempoten & aman diulang: baris yang sudah punya verif_token dilewati; API create
// juga idempoten by no_surat. Gagal satu baris → dicatat, lanjut baris berikut.
// Mode --perbaiki: untuk baris yang SUDAH punya verif_token, cek dokumennya masih ada
// di verif (baca langsung lintas-DB, hanya untuk tool operator lokal); bila hilang
// (mis. terhapus dari dashboard) → daftarkan ulang.
// Cara pakai:
//   php tools/impor-verif.php --dry-run   (daftar kandidat tanpa mengubah apa pun)
//   php tools/impor-verif.php [--limit=N]
//   php tools/impor-verif.php --force     (lampaui batas percobaan 5x/baris)
//   php tools/impor-verif.php --perbaiki [--dry-run] [--force]
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pembayaran.php';
require_once __DIR__ . '/../config/pdf.php';
require_once __DIR__ . '/../config/verif-client.php';

$dryRun = in_array('--dry-run', $argv, true);
$force = in_array('--force', $argv, true);
$limit = 0;
foreach ($argv as $a) {
    if (str_starts_with($a, '--limit=')) {
        $limit = max(0, (int) substr($a, strlen('--limit=')));
    }
}

// Batas percobaan per baris: baris yang berulang gagal dilewati kecuali --force.
$MAX_ATTEMPTS = 5;
$failedEq = $force
    ? "1 = 1"
    : "COALESCE(verif_attempts, 0) < {$MAX_ATTEMPTS}";

$sql = "SELECT * FROM pendaftar
        WHERE status_pembayaran = 'lunas' AND no_invoice <> ''
          AND (verif_token IS NULL OR verif_token = '' OR verif_status = 'failed')
          AND (
            COALESCE(verif_status, '') <> 'failed'
            OR {$failedEq}
          )
        ORDER BY id ASC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if ($limit > 0 && count($rows) > $limit) {
    $rows = array_slice($rows, 0, $limit);
}
$skippedCap = 0;
if (!$force && !$dryRun) {
    $skippedCap = (int) $pdo->query(
        "SELECT COUNT(*) FROM pendaftar
         WHERE status_pembayaran = 'lunas' AND no_invoice <> ''
           AND verif_status = 'failed' AND COALESCE(verif_attempts, 0) >= {$MAX_ATTEMPTS}"
    )->fetchColumn();
}

$synced = (int) $pdo->query(
    "SELECT COUNT(*) FROM pendaftar
     WHERE status_pembayaran = 'lunas' AND no_invoice <> ''
       AND verif_token IS NOT NULL AND verif_token <> ''"
)->fetchColumn();

echo 'Kandidat impor: ' . count($rows) . ' (sudah sinkron: ' . $synced . ')' . ($skippedCap > 0 ? " — {$skippedCap} baris gagal & menunggu --force (maks {$MAX_ATTEMPTS}x)" : '') . "\n";
if ($dryRun && !in_array('--perbaiki', $argv, true)) {
    foreach ($rows as $r) {
        echo '  - ' . $r['no_invoice'] . ' | ' . $r['nama'] . ' | ' . $r['program'] . "\n";
    }
    echo "DRY-RUN: tidak ada perubahan.\n";
    exit(0);
}

// Mode perbaikan: verifikasi ulang rujukan yang sudah ada.
if (in_array('--perbaiki', $argv, true)) {
    $ver = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=db_almutqon-verif;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $punya = $pdo->query(
        "SELECT id, no_invoice, nama, program, verif_token FROM pendaftar
         WHERE status_pembayaran = 'lunas' AND no_invoice <> ''
           AND verif_token IS NOT NULL AND verif_token <> '' ORDER BY id ASC"
    )->fetchAll();
    $hilang = [];
    foreach ($punya as $r) {
        $cek = $ver->prepare('SELECT COUNT(*) FROM dokumen WHERE token = ?');
        $cek->execute([$r['verif_token']]);
        if ((int) $cek->fetchColumn() === 0) {
            $hilang[] = $r;
        }
    }
    echo 'Rujukan yatim (dokumen hilang di verif): ' . count($hilang) . "\n";
    if ($dryRun) {
        foreach ($hilang as $r) {
            echo '  - ' . $r['no_invoice'] . ' | ' . $r['nama'] . "\n";
        }
        echo "DRY-RUN: tidak ada perubahan.\n";
        exit(0);
    }
    $cfg = verif_config();
    if (!$cfg['enabled']) {
        fwrite(STDERR, "Verifikasi tidak dikonfigurasi (VERIF_API_URL/VERIF_API_TOKEN kosong).\n");
        exit(2);
    }
    $ok = 0;
    $gagal = [];
    foreach ($hilang as $r) {
        $full = $pdo->query('SELECT * FROM pendaftar WHERE id = ' . (int) $r['id'])->fetch();
        try {
            $pdfBytes = (new StrukPdf())->struk($full, pembayaran_info(), 'Admin Almutqon');
            $vr = verif_register_doc($pdo, (int) $r['id'], $full, 'Admin Almutqon', $pdfBytes);
            if ($vr['ok']) {
                $ok++;
                echo "  OK {$r['no_invoice']} didaftarkan ulang (Kode: {$vr['code']})\n";
            } else {
                $gagal[] = $r['no_invoice'] . ': ' . ($vr['error'] !== '' ? $vr['error'] : 'gagal');
                echo '  GAGAL ' . end($gagal) . "\n";
            }
        } catch (\Throwable $t) {
            $gagal[] = $r['no_invoice'] . ': ' . $t->getMessage();
            error_log('[impor-verif] ' . end($gagal));
            echo '  GAGAL ' . end($gagal) . "\n";
        }
    }
    echo "Selesai: {$ok} dipulihkan, " . count($gagal) . " gagal.\n";
    exit(count($gagal) === 0 ? 0 : 1);
}

$cfg = verif_config();
if (!$cfg['enabled']) {
    fwrite(STDERR, "Verifikasi tidak dikonfigurasi (VERIF_API_URL/VERIF_API_TOKEN kosong).\n");
    exit(2);
}

// Penandatangan historis tak tercatat; pakai label generik yang jujur.
$adminNama = 'Admin Almutqon';
$ok = 0;
$gagal = [];
foreach ($rows as $r) {
    $id = (int) $r['id'];
    try {
        $pdfBytes = (new StrukPdf())->struk($r, pembayaran_info(), $adminNama);
        $vr = verif_register_doc($pdo, $id, $r, $adminNama, $pdfBytes);
        if ($vr['ok']) {
            $ok++;
            echo "  OK {$r['no_invoice']} (Kode: {$vr['code']})\n";
        } else {
            $gagal[] = $r['no_invoice'] . ': ' . ($vr['error'] !== '' ? $vr['error'] : 'gagal');
            echo '  GAGAL ' . end($gagal) . "\n";
        }
    } catch (\Throwable $t) {
        $gagal[] = $r['no_invoice'] . ': ' . $t->getMessage();
        error_log('[impor-verif] ' . end($gagal));
        echo '  GAGAL ' . end($gagal) . "\n";
    }
}

echo "Selesai: {$ok} terdaftar, " . count($gagal) . " gagal.\n";
exit(count($gagal) === 0 ? 0 : 1);
