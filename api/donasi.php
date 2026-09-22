<?php
// api/donasi.php — catat intent donasi (Opsi B: transfer manual), kembalikan instruksi transfer
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/donasi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

if (!csrf_check()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token tidak valid. Muat ulang halaman lalu coba lagi.']);
    exit;
}

$nama = trim($_POST['nama'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$jumlah = (int) ($_POST['jumlah'] ?? 0);
$metode = $_POST['metode'] ?? '';
$bankTujuan = trim($_POST['bank_tujuan'] ?? '');
$ewalletTujuan = trim($_POST['ewallet_tujuan'] ?? '');

if ($nama === '' || $whatsapp === '') {
    echo json_encode(['success' => false, 'message' => 'Nama dan nomor WhatsApp wajib diisi.']);
    exit;
}

if ($jumlah < 10000) {
    echo json_encode(['success' => false, 'message' => 'Nominal donasi minimal Rp10.000.']);
    exit;
}

if (!in_array($metode, ['bank', 'qris', 'ewallet'], true)) {
    echo json_encode(['success' => false, 'message' => 'Pilih metode pembayaran terlebih dahulu.']);
    exit;
}

$kanal = '';
if ($metode === 'bank') {
    if ($bankTujuan === '' || !isset(donasi_bank_channels()[$bankTujuan])) {
        echo json_encode(['success' => false, 'message' => 'Pilih bank tujuan donasi.']);
        exit;
    }
    $kanal = donasi_resolve_bank($bankTujuan);
} elseif ($metode === 'ewallet') {
    if ($ewalletTujuan === '' || !isset(donasi_ewallet_channels()[$ewalletTujuan])) {
        echo json_encode(['success' => false, 'message' => 'Pilih e-wallet tujuan donasi.']);
        exit;
    }
    $kanal = donasi_resolve_ewallet($ewalletTujuan);
}

$user_email = $_SESSION['user']['email'] ?? '';

$stmt = $pdo->prepare('INSERT INTO donasi (user_email, nama, whatsapp, jumlah, metode, kanal_pembayaran) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$user_email, $nama, $whatsapp, $jumlah, $metode, $kanal]);

$id = (int) $pdo->lastInsertId();
$unique = sprintf('%03d', $id % 1000);

$target = donasi_target($metode, $kanal);

echo json_encode([
    'success' => true,
    'message' => 'Donasi tercatat. Silakan transfer sesuai instruksi.',
    'id'      => $id,
    'jumlah'  => number_format($jumlah, 0, ',', '.'),
    'unique'  => $unique,
    'kanal'   => $kanal,
    'target'  => $target,
]);