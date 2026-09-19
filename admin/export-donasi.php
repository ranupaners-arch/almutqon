<?php
// admin/export-donasi.php — unduh CSV donasi berstatus DITERIMA untuk diolah di spreadsheet.
// Akses: HANYA admin (require_admin). Nominal diekspor sebagai angka murni (tanpa Rp/titik)
// agar bisa langsung dijumlahkan dengan formula. Pemisah ';' + BOM UTF-8 supaya rapi
// dibuka di Excel Indonesia.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/donasi.php';

require_admin();

$rows = $pdo->query("SELECT created_at, nama, whatsapp, metode, kanal_pembayaran, jumlah FROM donasi WHERE status = 'diterima' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$filename = 'Laporan_Donasi_Diterima_' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8: Excel membaca karakter Indonesia dengan benar
fputcsv($out, ['Tanggal', 'Nama', 'WhatsApp', 'Metode', 'Kanal Pembayaran', 'Jumlah'], ';', '"', '');

foreach ($rows as $r) {
    $metodeLabel = match ($r['metode']) {
        'bank' => 'Transfer Bank', 'qris' => 'QRIS', 'ewallet' => 'E-Wallet', default => (string) $r['metode'],
    };
    $kanalLabel = donasi_kanal_label((string) $r['metode'], (string) ($r['kanal_pembayaran'] ?? ''));
    fputcsv($out, [
        date('Y-m-d H:i:s', strtotime($r['created_at'])),
        (string) $r['nama'],
        (string) $r['whatsapp'],
        $metodeLabel,
        $kanalLabel !== '' ? $kanalLabel : '-',
        (int) $r['jumlah'],
    ], ';', '"', '');
}
fclose($out);
exit;
