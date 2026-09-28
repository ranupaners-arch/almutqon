<?php
// api/unduh-materi.php — unduh file materi kursus (GET).
// Akses: pemilik program LUNAS yang sesuai kolom `program` materi, pengajar, atau admin.
// File TIDAK ditautkan langsung agar tidak bisa ditebak/diakses tanpa hak.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/storage.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM materi_kursus WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$materi = $stmt->fetch();

$allowed = false;
if ($materi) {
    if (is_admin() || is_pengajar()) {
        $allowed = true;
    } elseif (isset($_SESSION['user'])) {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM pendaftar WHERE (user_id = ? OR (user_id IS NULL AND user_email = ?)) AND program = ? AND status_pembayaran = 'lunas'");
        $cek->execute([(int) $_SESSION['user']['id'], (string) $_SESSION['user']['email'], $materi['program']]);
        $allowed = ((int) $cek->fetchColumn()) > 0;
    }
}

if (!$materi || !$allowed) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Materi tidak ditemukan atau Anda tidak memiliki akses.';
    exit;
}

// Resolver hanya menerima basename dan mendukung lokasi legacy assets/materi.
$full = private_storage_resolve('materi', (string) $materi['path_file']);
if ($full === false) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File materi tidak tersedia di server.';
    exit;
}

$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    default => 'application/octet-stream',
};
$nama = preg_replace('/[^A-Za-z0-9 _.-]/', '_', $materi['judul']) . '.' . $ext;

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $nama . '"');
header('Content-Length: ' . filesize($full));
header('Pragma: no-cache');
readfile($full);
exit;
