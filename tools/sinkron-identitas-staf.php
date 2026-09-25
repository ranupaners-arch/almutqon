<?php
// Sinkronkan Staff ID baru ke layanan verifikasi tanpa membuat baris staff ganda.
// Default dry-run. Jalankan dengan --execute setelah kedua service diperbarui.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/verif-client.php';
require_once __DIR__ . '/../config/identity.php';

$execute = in_array('--execute', $argv, true);
$rows = $pdo->query("SELECT * FROM users WHERE peran != 'user' ORDER BY id")->fetchAll();
$stats = ['scanned' => count($rows), 'synced' => 0, 'skipped' => 0, 'failed' => 0];

echo $execute ? "Mode EXECUTE\n" : "Mode DRY-RUN (tambahkan --execute untuk menerapkan)\n";
foreach ($rows as $row) {
    if (!identity_is_staff_id((string) ($row['staff_id'] ?? ''))) {
        $stats['failed']++;
        echo "  FAIL users.id={$row['id']}: Staff ID belum valid\n";
        continue;
    }
    echo "  " . ($execute ? 'SYNC' : 'PLAN') . " users.id={$row['id']} {$row['id_pengajar']} -> {$row['staff_id']}\n";
    if (!$execute) {
        continue;
    }
    $result = verif_sync_pengajar($pdo, $row, 'upsert');
    if ($result['ok']) {
        $stats['synced']++;
    } elseif ($result['skipped']) {
        $stats['skipped']++;
    } else {
        $stats['failed']++;
        echo "    gagal: {$result['error']}\n";
    }
}

echo "Hasil: scanned={$stats['scanned']} synced={$stats['synced']} skipped={$stats['skipped']} failed={$stats['failed']}\n";
exit($stats['failed'] > 0 ? 1 : 0);
