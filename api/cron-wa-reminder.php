<?php
// api/cron-wa-reminder.php — kirim pengingat batas bayar via WhatsApp.
// Jalankan tiap 1 jam via Cron Job server, contoh:
//   0 * * * * /usr/bin/php /path/ke/almutqon/api/cron-wa-reminder.php >> /tmp/amq-wa-cron.log 2>&1
// Boleh juga dipicu via HTTP, WAJIB sertakan kunci: /api/cron-wa-reminder.php?key=CRON_SECRET
// Sasaran: status 'belum'/'pending', batas_bayar tersisa < 3 jam (belum lewat),
// dan belum pernah diingatkan (wa_reminder_at IS NULL) → tidak ada pesan berulang.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pembayaran.php';
require_once __DIR__ . '/../config/whatsapp.php';

header('Content-Type: text/plain; charset=utf-8');

if (php_sapi_name() !== 'cli') {
    $secret = getenv('CRON_SECRET');
    $key = (string) ($_GET['key'] ?? '');
    if ($secret === false || $secret === '' || !hash_equals($secret, $key)) {
        http_response_code(403);
        echo "Akses ditolak.\n";
        exit;
    }
}

$rows = $pdo->query(
    "SELECT id, nama, whatsapp, program, metode, biaya, batas_bayar FROM pendaftar
     WHERE status_pembayaran IN ('belum','pending')
       AND batas_bayar IS NOT NULL
       AND batas_bayar > NOW()
       AND batas_bayar <= DATE_ADD(NOW(), INTERVAL 3 HOUR)
       AND wa_reminder_at IS NULL
     ORDER BY batas_bayar ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$appUrl = getenv('APP_URL');
$appUrl = ($appUrl !== false && trim($appUrl) !== '') ? rtrim(trim($appUrl), '/') : 'http://almutqon.test';

$terkirim = 0;
foreach ($rows as $r) {
    $sisaDetik = max(0, strtotime($r['batas_bayar']) - time());
    $sisaJam = intdiv($sisaDetik, 3600);
    $sisaMenit = intdiv($sisaDetik % 3600, 60);
    $sisaTeks = $sisaJam > 0 ? "{$sisaJam} jam {$sisaMenit} menit" : "{$sisaMenit} menit";
    $label = match ($r['program']) {
        'offline' => 'Offline ' . ($r['metode'] ?: ''),
        'online'  => 'Online '  . ($r['metode'] ?: ''),
        'dauroh'  => 'Dauroh '  . ($r['metode'] ?: ''),
        default   => (string) $r['program'],
    };
    $statusUrl = $appUrl . '/pages/bayar/tunggu.php?id=' . (int) $r['id'];
    $pesan = "Assalamu'alaikum {$r['nama']},\n\n"
        . "Waktu pembayaran program {$label} Anda TERSISA {$sisaTeks} (batas: " . date('d M Y, H:i', strtotime($r['batas_bayar'])) . " WIB).\n\n"
        . "Total bayar : " . rupiah((int) $r['biaya']) . "\n\n"
        . "Segera selesaikan & salin nomor rekening di sini:\n{$statusUrl}\n\n"
        . "-- Administrasi Al-Mutqon";
    try {
        $wa = send_whatsapp((string) $r['whatsapp'], $pesan);
        if ($wa['ok']) {
            $up = $pdo->prepare("UPDATE pendaftar SET wa_reminder_at = NOW() WHERE id = ?");
            $up->execute([(int) $r['id']]);
            $terkirim++;
        } elseif (!$wa['skipped']) {
            error_log('[wa-reminder] gagal ke ' . $r['whatsapp'] . ' (id ' . $r['id'] . '): ' . $wa['error']);
        }
    } catch (\Throwable $t) {
        error_log('[wa-reminder] ' . $t->getMessage());
    }
}

echo "Selesai: {$terkirim} dari " . count($rows) . " pengingat terkirim.\n";
