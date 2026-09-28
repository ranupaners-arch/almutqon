<?php
// api/unduh-jurnal.php — unduh file jurnal peserta (GET).
// Akses: admin, pengajar (untuk menilai), atau pemilik jurnal itu sendiri.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/storage.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM jurnal_peserta WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$jurnal = $stmt->fetch();

$allowed = false;
if ($jurnal) {
    $userEmail = (string) ($_SESSION['user']['email'] ?? '');
    $allowed = is_admin() || is_pengajar() || ($userEmail !== '' && strcasecmp($userEmail, (string) $jurnal['user_email']) === 0);
}

if (!$jurnal || !$allowed) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Jurnal tidak ditemukan atau Anda tidak memiliki akses.';
    exit;
}

// Resolver hanya menerima basename dan mendukung lokasi legacy assets/jurnal.
$full = private_storage_resolve('jurnal', (string) $jurnal['path_file']);
if ($full === false) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File jurnal tidak tersedia di server.';
    exit;
}

$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    default => 'application/octet-stream',
};
$nama = preg_replace('/[^A-Za-z0-9 _.-]/', '_', $jurnal['judul_jurnal']) . '.' . $ext;

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $nama . '"');
header('Content-Length: ' . filesize($full));
header('Pragma: no-cache');
readfile($full);
exit;
