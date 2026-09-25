<?php
session_start();
require_once '../config/database.php';
require_once '../config/csrf.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

if (!csrf_check()) {
    echo json_encode(['success' => false, 'message' => 'Token tidak valid. Muat ulang halaman lalu coba lagi.']);
    exit;
}

 $email = trim($_POST['email'] ?? '');
 $password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email dan password wajib diisi.']);
    exit;
}

 $stmt = $pdo->prepare("SELECT id, name, email, password, avatar FROM users WHERE email = ?");
 $stmt->execute([$email]);
 $user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email atau password salah.']);
    exit;
}

// Akses dicabut (revoke) oleh admin → tolak login walau password benar.
// Dibungkus try/catch agar login tetap jalan di DB lama yang belum punya kolom `aktif`.
try {
    $cek = $pdo->prepare("SELECT aktif FROM users WHERE id = ?");
    $cek->execute([(int) $user['id']]);
    if ((int) $cek->fetchColumn() === 0) {
        echo json_encode(['success' => false, 'message' => 'Akun dinonaktifkan. Hubungi admin.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('[login] cek kolom aktif gagal: ' . $e->getMessage());
}

regenerate_session();

 $_SESSION['user'] = [
    'id' => (int) $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'avatar' => $user['avatar'] ?? null
];

remember_me();

echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
