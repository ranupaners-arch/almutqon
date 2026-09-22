<?php
// api/pendaftar.php — simpan pengajuan program offline/online/dauroh ke DB
// Dipanggil via fetch dari app.js (submitOffline/Online/Dauroh).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/pembayaran.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/whatsapp.php';

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

$program = $_POST['program'] ?? '';
$nama = trim($_POST['nama'] ?? '');
$nama_panggilan = trim($_POST['nama_panggilan'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$usia = (int) ($_POST['usia'] ?? 0);
$whatsapp = trim($_POST['whatsapp'] ?? '');
$metode = trim($_POST['metode'] ?? '');
$metodeBayar = $_POST['metode_bayar'] ?? '';
$bankTujuan = trim($_POST['bank_tujuan'] ?? '');
$ewalletTujuan = trim($_POST['ewallet_tujuan'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$instansi = trim($_POST['instansi'] ?? '');
$jumlah_peserta = (int) ($_POST['jumlah_peserta'] ?? 0);
$catatan = trim($_POST['catatan'] ?? '');

if (!in_array($program, ['offline', 'online', 'dauroh'], true)) {
    echo json_encode(['success' => false, 'message' => 'Program tidak dikenali.']);
    exit;
}

if ($metodeBayar === '' || !in_array($metodeBayar, ['bank', 'qris', 'ewallet'], true)) {
    echo json_encode(['success' => false, 'message' => 'Pilih metode pembayaran terlebih dahulu.']);
    exit;
}

// Kanal spesifik: bank (BSI/BRI/KROM/SEABANK) atau e-wallet (ShopeePay/DANA/GoPay).
$kanal = '';
if ($metodeBayar === 'bank') {
    if ($bankTujuan === '' || !isset(donasi_bank_channels()[$bankTujuan])) {
        echo json_encode(['success' => false, 'message' => 'Pilih bank tujuan pembayaran.']);
        exit;
    }
    $kanal = donasi_resolve_bank($bankTujuan);
} elseif ($metodeBayar === 'ewallet') {
    if ($ewalletTujuan === '' || !isset(donasi_ewallet_channels()[$ewalletTujuan])) {
        echo json_encode(['success' => false, 'message' => 'Pilih e-wallet tujuan pembayaran.']);
        exit;
    }
    $kanal = donasi_resolve_ewallet($ewalletTujuan);
}

if ($nama === '' || $whatsapp === '') {
    echo json_encode(['success' => false, 'message' => 'Nama dan nomor WhatsApp wajib diisi.']);
    exit;
}

// Batasi panjang sesuai kolom DB agar tidak memicu PDOException (Data too long).
if (mb_strlen($nama) > 120) {
    echo json_encode(['success' => false, 'message' => 'Nama maksimal 120 karakter.']);
    exit;
}
if (mb_strlen($nama_panggilan) > 100) {
    echo json_encode(['success' => false, 'message' => 'Nama panggilan maksimal 100 karakter.']);
    exit;
}
if (mb_strlen($whatsapp) > 30) {
    echo json_encode(['success' => false, 'message' => 'Nomor WhatsApp maksimal 30 karakter.']);
    exit;
}
if (mb_strlen($alamat) > 255) {
    echo json_encode(['success' => false, 'message' => 'Alamat maksimal 255 karakter.']);
    exit;
}
if (mb_strlen($instansi) > 120) {
    echo json_encode(['success' => false, 'message' => 'Instansi maksimal 120 karakter.']);
    exit;
}
if (mb_strlen($catatan) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Informasi tambahan maksimal 5000 karakter.']);
    exit;
}

// Jenis kelamin wajib; opsi berbeda untuk dauroh (Umum/Banin/Banat).
$genderAllowed = $program === 'dauroh' ? ['Umum', 'Banin', 'Banat'] : ['Laki-laki', 'Perempuan'];
if (!in_array($jenis_kelamin, $genderAllowed, true)) {
    echo json_encode([
        'success' => false,
        'message' => $program === 'dauroh'
            ? 'Pilih jenis kelamin (Umum / Banin / Banat).'
            : 'Pilih jenis kelamin (Laki-laki / Perempuan).',
    ]);
    exit;
}

if ($usia < 1 || $usia > 120) {
    echo json_encode(['success' => false, 'message' => 'Usia harus antara 1-120 tahun.']);
    exit;
}

if ($program === 'dauroh') {
    $min = biaya_kursus_config()['dauroh']['min_peserta'];
    if ($jumlah_peserta < $min) {
        echo json_encode(['success' => false, 'message' => "Jumlah peserta minimal {$min} orang."]);
        exit;
    }
} elseif ($jumlah_peserta < 1) {
    $jumlah_peserta = 1;
}

if (($program === 'offline' || $program === 'online') && !in_array($metode, ['Privat', 'Non-Privat'], true)) {
    echo json_encode(['success' => false, 'message' => 'Pilih metode pembelajaran (Privat / Non-Privat).']);
    exit;
}

if ($program === 'dauroh' && !in_array($metode, ['Offline', 'Online'], true)) {
    echo json_encode(['success' => false, 'message' => 'Pilih metode Dauroh (Offline / Online).']);
    exit;
}

$hitung = hitung_biaya_kursus($program, $metode, $jumlah_peserta);
$biaya = $hitung['biaya'];
$user_email = $_SESSION['user']['email'] ?? '';

$stmt = $pdo->prepare("INSERT INTO pendaftar
    (program, nama, nama_panggilan, jenis_kelamin, usia, whatsapp, metode, alamat, instansi, jumlah_peserta, catatan, user_email, biaya, metode_pembayaran, kanal_pembayaran, batas_bayar)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
try {
    $stmt->execute([$program, $nama, $nama_panggilan, $jenis_kelamin, $usia, $whatsapp, $metode, $alamat, $instansi, $jumlah_peserta, $catatan, $user_email, $biaya, $metodeBayar, $kanal]);
} catch (PDOException $e) {
    error_log('[pendaftar] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Data tidak dapat disimpan. Periksa kembali isian Anda.']);
    exit;
}

$id = (int) $pdo->lastInsertId();

$target = donasi_target($metodeBayar, $kanal);

// Link halaman status (countdown + salin rekening) — dikirim via email & bisa dibuka ulang kapan saja.
$host = $_SERVER['HTTP_HOST'] ?? 'almutqon.test';
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$statusUrl = $scheme . '://' . $host . '/pages/bayar/tunggu.php?id=' . $id;
$waUrl = 'https://wa.me/6282258442647?text=' . rawurlencode('Halo, saya ' . $nama . ' — ingin konfirmasi pembayaran program ' . $hitung['label'] . ' (ID pengajuan: ' . $id . ')');

// Notifikasi email ke pendaftar. Kegagalan kirim TIDAK menggagalkan pengajuan (hanya dicatat di log).
$emailTerkirim = false;
if ($user_email !== '' && filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    try {
        $kanalLabel = donasi_kanal_label($metodeBayar, $kanal);
        $metodeLabel = match ($metodeBayar) {
            'bank' => 'Transfer Bank' . ($kanalLabel !== '' ? ' · ' . $kanalLabel : ''),
            'qris' => 'QRIS',
            'ewallet' => 'E-Wallet' . ($kanalLabel !== '' ? ' · ' . $kanalLabel : ''),
            default => $metodeBayar,
        };
        $body = "Yth. {$nama},\n\n"
            . "Pengajuan program {$hitung['label']} Anda telah kami terima.\n\n"
            . "Total yang harus dibayar : " . rupiah($biaya) . "\n"
            . "Metode pembayaran        : {$metodeLabel}\n"
            . "Batas pembayaran         : " . date('d M Y, H:i', time() + 86400) . " WIB (24 jam)\n\n"
            . "Buka halaman status untuk melihat countdown & menyalin nomor rekening/tujuan transfer:\n{$statusUrl}\n\n"
            . "Simpan link di atas — Anda dapat membukanya kembali kapan saja untuk menyalin data pembayaran. Setelah membayar, konfirmasi via WhatsApp:\n{$waUrl}\n\n"
            . "-- Administrasi Al-Mutqon\nadmin.almutqon@gmail.com";
        $mail = send_mail($user_email, 'Pengajuan Program Diterima — Selesaikan Pembayaran', $body);
        $emailTerkirim = $mail['ok'];
        if (!$mail['ok']) {
            error_log('[pendaftar-email] gagal ke ' . $user_email . ': ' . $mail['error']);
        }
    } catch (\Throwable $t) {
        error_log('[pendaftar-email] ' . $t->getMessage());
    }
}

// Notifikasi WhatsApp: tagihan + batas waktu + link status.
// Kegagalan kirim TIDAK menggagalkan pengajuan (hanya dicatat di log).
$waTerkirim = false;
try {
    $pesanWa = "Assalamu'alaikum {$nama},\n\n"
        . "Pengajuan program {$hitung['label']} Anda telah kami terima.\n\n"
        . "Total bayar : " . rupiah($biaya) . "\n"
        . "ID Pengajuan : {$id}\n"
        . "Batas bayar : " . date('d M Y, H:i', time() + 86400) . " WIB (24 jam dari sekarang)\n\n"
        . "Lihat countdown & salin nomor rekening di sini:\n{$statusUrl}\n\n"
        . "Simpan pesan ini. Setelah membayar, konfirmasi ke admin: https://wa.me/6282258442647\n\n"
        . "-- Administrasi Al-Mutqon";
    $wa = send_whatsapp($whatsapp, $pesanWa);
    $waTerkirim = $wa['ok'];
    if (!$wa['ok'] && !$wa['skipped']) {
        error_log('[pendaftar-wa] gagal ke ' . $whatsapp . ': ' . $wa['error']);
    }
} catch (\Throwable $t) {
    error_log('[pendaftar-wa] ' . $t->getMessage());
}

echo json_encode([
    'success' => true,
    'message' => 'Pengajuan Program Terkirim, Silahkan Menyelesaikan Pembayaran',
    'id' => $id,
    'redirect' => '/pages/bayar/tunggu.php?id=' . $id,
    'status_url' => $statusUrl,
    'wa_url' => $waUrl,
    'email_terkirim' => $emailTerkirim,
    'wa_terkirim' => $waTerkirim,
    'biaya' => $biaya,
    'biaya_rupiah' => rupiah($biaya),
    'program_label' => $hitung['label'],
    'metode_bayar' => $metodeBayar,
    'kanal' => $kanal,
    'target' => $target,
], JSON_UNESCAPED_UNICODE);