<?php
// pages/bayar/struk-pdf.php — unduh struk sebagai file PDF (generator murni PHP, tanpa library)
// Akses: /pages/bayar/struk-pdf.php?no=INV-YYYYMMDD-XXXX
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/pembayaran.php';
require_once __DIR__ . '/../../config/pdf.php';

if (session_status() === PHP_SESSION_NONE) {
    ob_start(); // amankan dari output tak sengaja sebelum PDF
}

$no  = trim($_GET['no'] ?? '');
$row = $no !== '' ? cari_pendaftar_by_invoice($pdo, $no) : false;

if ($row === false) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(404);
    echo 'Struk tidak ditemukan. Periksa nomor invoice Anda.';
    exit;
}

$adminNama = $_SESSION['user']['name'] ?? 'Admin Almutqon';

$struk = new StrukPdf();
$pdf   = $struk->struk($row, pembayaran_info(), $adminNama);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="struk-' . preg_replace('/[^A-Za-z0-9.-]/', '_', $no) . '.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Pragma: no-cache');
echo $pdf;
exit;