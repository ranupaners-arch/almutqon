<?php
session_start();
require_once '../config/database.php';
require_once '../config/csrf.php';
require_once '../config/auth.php';
require_once '../config/identity.php';

header('Content-Type: application/json');

if (!csrf_check()) {
    echo json_encode(['success' => false, 'message' => 'Token tidak valid. Muat ulang halaman lalu coba lagi.']);
    exit;
}

 $name = trim($_POST['name'] ?? '');
 $email = trim($_POST['email'] ?? '');
 $password = $_POST['password'] ?? '';

if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
    exit;
}

if (mb_strlen($name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Nama maksimal 100 karakter.']);
    exit;
}

if (mb_strlen($email) > 150) {
    echo json_encode(['success' => false, 'message' => 'Email maksimal 150 karakter.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']);
    exit;
}

// Cek email sudah terdaftar
 $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
 $stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar. Silakan masuk.']);
    exit;
}

 $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();
    $memberId = generate_member_id($pdo);
    $stmt = $pdo->prepare("INSERT INTO users (member_id, name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$memberId, $name, $email, $hashedPassword]);
    $userId = (int) $pdo->lastInsertId();
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[register] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Data tidak dapat disimpan. Periksa kembali nama/email Anda.']);
    exit;
}

 $user = [
    'id' => (int) $userId,
    'name' => $name,
    'email' => $email,
    'avatar' => null
];

regenerate_session();

 $_SESSION['user'] = $user;

echo json_encode(['success' => true, 'user' => $user]);
