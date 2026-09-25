<?php
// api/upload-jurnal.php — terima unggahan jurnal ilmiah/tugas akhir peserta (POST klasik + PRG).
// Syarat: sudah login, CSRF valid, program milik sendiri berstatus LUNAS,
// file PDF/DOC/DOCX maksimal 5MB. File disimpan acak di assets/jurnal/.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';

$kembali = '/pages/akun/unggah-tugas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $kembali);
    exit;
}

require_csrf();

if (!isset($_SESSION['user'])) {
    flash_error('Silakan login dulu untuk mengunggah jurnal.');
    header('Location: /');
    exit;
}

$userEmail = (string) ($_SESSION['user']['email'] ?? '');
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$pendaftarId = (int) ($_POST['pendaftar_id'] ?? 0);
$judul = trim($_POST['judul_jurnal'] ?? '');

// Program harus milik user & sudah LUNAS (cegah user menumpang ID milik orang lain).
$stmt = $pdo->prepare("SELECT id, program FROM pendaftar WHERE id = ? AND (user_id = ? OR (user_id IS NULL AND user_email = ?)) AND status_pembayaran = 'lunas' LIMIT 1");
$stmt->execute([$pendaftarId, $userId, $userEmail]);
$pendaftar = $stmt->fetch();
if (!$pendaftar) {
    flash_error('Program tidak valid. Pilih program Anda yang sudah lunas.');
    header('Location: ' . $kembali);
    exit;
}

if ($judul === '' || mb_strlen($judul) > 255) {
    flash_error('Judul jurnal wajib diisi (maksimal 255 karakter).');
    header('Location: ' . $kembali);
    exit;
}

// Pengajar tujuan wajib aktif (nama + ID tampil agar user tahu ke siapa ia mengirim).
$pengajarId = (int) ($_POST['pengajar_id'] ?? 0);
$stmt = $pdo->prepare("SELECT id, name, staff_id, id_pengajar FROM users WHERE id = ? AND peran != 'user' AND aktif = 1 LIMIT 1");
$stmt->execute([$pengajarId]);
$guru = $stmt->fetch();
if (!$guru) {
    flash_error('Pilih pengajar tujuan yang valid.');
    header('Location: ' . $kembali);
    exit;
}

if (!isset($_FILES['file_jurnal']) || $_FILES['file_jurnal']['error'] !== UPLOAD_ERR_OK) {
    flash_error('File jurnal wajib diunggah. Coba lagi.');
    header('Location: ' . $kembali);
    exit;
}

$file = $_FILES['file_jurnal'];
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    flash_error('Ukuran file maksimal 5MB.');
    header('Location: ' . $kembali);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExt = ['pdf', 'doc', 'docx'];
if (!in_array($ext, $allowedExt, true)) {
    flash_error('Format file harus PDF, DOC, atau DOCX.');
    header('Location: ' . $kembali);
    exit;
}

// Cek MIME asli via finfo (nama/ekstensi bisa dipalsukan).
$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
$allowedMime = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
if (!in_array($mime, $allowedMime, true)) {
    flash_error('Isi file tidak sesuai format PDF/DOC/DOCX.');
    header('Location: ' . $kembali);
    exit;
}

$targetDir = __DIR__ . '/../assets/jurnal/';
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}
$newName = 'jurnal_' . uniqid('', true) . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $targetDir . $newName)) {
    flash_error('Gagal menyimpan file. Coba lagi.');
    header('Location: ' . $kembali);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO jurnal_peserta (user_email, pendaftar_id, pengajar_id, judul_jurnal, path_file) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$userEmail, $pendaftarId, $pengajarId, $judul, 'assets/jurnal/' . $newName]);

flash('Tugas "' . mb_strimwidth($judul, 0, 60, '…') . '" terkirim ke ' . $guru['name'] . '. Status review: Pending.');
header('Location: ' . $kembali);
exit;
