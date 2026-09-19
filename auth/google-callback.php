<?php
// auth/google-callback.php — panggilan balik OAuth; tukar kode -> token -> login/daftar
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/google.php';

// 1) Cek state (anti-CSRF pada alur OAuth)
$expectedState = $_SESSION['oauth_state'] ?? null;
$state = $_GET['state'] ?? '';
if ($expectedState === null || $state === '' || !hash_equals($expectedState, $state)) {
    google_error_page('State tidak valid', 'Sesi OAuth tidak cocok. Silakan mulai lagi dari tombol "Masuk dengan Google".');
}
unset($_SESSION['oauth_state']);

// 2) Pengguna membatalkan / error dari Google
if (!empty($_GET['error'])) {
    google_error_page('Login dibatalkan', 'Tidak ada sesi yang dibuat. ' . htmlspecialchars((string) $_GET['error']));
}

// 3) Kode otorisasi wajib ada
$code = $_GET['code'] ?? '';
if ($code === '') {
    google_error_page('Kode tidak ada', 'Google tidak mengirimkan kode otorisasi.');
}

$codeVerifier = $_SESSION['oauth_verifier'] ?? '';
unset($_SESSION['oauth_verifier']);

$clientId = google_client_id();
$clientSecret = google_client_secret();

// 4) Tukar kode dengan token
$resp = google_http_post('https://oauth2.googleapis.com/token', [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => google_redirect_uri(),
    'grant_type' => 'authorization_code',
    'code_verifier' => $codeVerifier,
]);

$idToken = $resp['json']['id_token'] ?? '';
if ($idToken === '') {
    google_error_page('Token gagal ditukar', 'Google menolak pertukaran kode (HTTP ' . $resp['status'] . '). Coba lagi nanti.');
}

// 5) Verifikasi id_token (signature RS256 + klaim)
$claims = google_verify_id_token($idToken, $clientId);
if ($claims === null) {
    google_error_page('Token tidak valid', 'id_token dari Google tidak lolos verifikasi.');
}

// 6) Tautkan / buat akun
$email = strtolower(trim((string) $claims['email']));
$googleSub = (string) $claims['sub'];

$stmt = $pdo->prepare('SELECT id, name, email, avatar FROM users WHERE google_id = ?');
$stmt->execute([$googleSub]);
$user = $stmt->fetch();

if (!$user) {
    // Coba tautkan lewat email yang sama
    $stmt = $pdo->prepare('SELECT id, name, email, avatar, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?')->execute([$googleSub, (int) $user['id']]);
    } else {
        $name = trim((string) ($claims['name'] ?? ''));
        if ($name === '') {
            $name = strtok($email, '@') ?: 'Pengguna Google';
        }
        $pdo->prepare('INSERT INTO users (name, email, password, google_id) VALUES (?, ?, NULL, ?)')
            ->execute([$name, $email, $googleSub]);
        $user = [
            'id' => (int) $pdo->lastInsertId(),
            'name' => $name,
            'email' => $email,
            'avatar' => null,
        ];
    }
}

// 7) Sesi login
regenerate_session();

$_SESSION['user'] = [
    'id' => (int) $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'avatar' => $user['avatar'] ?? null,
];

header('Location: /');
exit;