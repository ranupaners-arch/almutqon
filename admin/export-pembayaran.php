<?php
// admin/export-pembayaran.php — unduh CSV data pendaftaran LUNAS untuk diolah di spreadsheet.
// Akses: HANYA admin (require_admin). Nominal diekspor sebagai angka murni (tanpa Rp/titik)
// agar bisa langsung dijumlahkan dengan formula. Pemisah ';' + BOM UTF-8 supaya rapi
// dibuka di Excel Indonesia.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/pembayaran.php';

require_admin();

$rows = $pdo->query("SELECT no_invoice, tanggal_bayar, nama, program, metode, metode_pembayaran, kanal_pembayaran, biaya FROM pendaftar WHERE status_pembayaran = 'lunas' ORDER BY tanggal_bayar DESC")->fetchAll(PDO::FETCH_ASSOC);

$filename = 'Laporan_Pembayaran_Kursus_' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8: Excel membaca karakter Indonesia dengan benar
fputcsv($out, ['No Invoice', 'Tanggal Bayar', 'Nama', 'Program (Metode)', 'Kanal Pembayaran', 'Biaya'], ';', '"', '');

foreach ($rows as $r) {
    $program = match ($r['program']) {
        'offline' => 'Offline' . ($r['metode'] ? ' ' . $r['metode'] : ''),
        'online'  => 'Online' . ($r['metode'] ? ' ' . $r['metode'] : ''),
        'dauroh'  => 'Dauroh' . ($r['metode'] ? ' ' . $r['metode'] : ''),
        default   => (string) $r['program'],
    };
    $metodeLabel = match ($r['metode_pembayaran']) {
        'bank' => 'Bank', 'qris' => 'QRIS', 'ewallet' => 'E-Wallet', default => (string) $r['metode_pembayaran'],
    };
    $kanalLabel = donasi_kanal_label((string) $r['metode_pembayaran'], (string) ($r['kanal_pembayaran'] ?? ''));
    if ($kanalLabel !== '') {
        $metodeLabel .= ' · ' . $kanalLabel;
    }
    fputcsv($out, [
        (string) $r['no_invoice'],
        $r['tanggal_bayar'] ? date('Y-m-d H:i:s', strtotime($r['tanggal_bayar'])) : '',
        (string) $r['nama'],
        $program,
        $metodeLabel,
        (int) $r['biaya'],
    ], ';', '"', '');
}
fclose($out);
exit;
