<?php
// auth/google-login.php — mulai alur "Masuk dengan Google" (OAuth code + PKCE)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/google.php';

if (isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

$clientId = google_client_id();
if ($clientId === '') {
    google_error_page(
        'Google Login Belum Aktif',
        "Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di file .env,\nlalu daftarkan redirect URI " . google_redirect_uri() . " di Google Cloud Console."
    );
}

// State (anti-CSRF) + PKCE code_verifier/code_challenge
$state = bin2hex(random_bytes(16));
$codeVerifier = google_base64url_encode(random_bytes(64));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_verifier'] = $codeVerifier;

$params = [
    'client_id' => $clientId,
    'redirect_uri' => google_redirect_uri(),
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'code_challenge' => google_base64url_encode(hash('sha256', $codeVerifier, true)),
    'code_challenge_method' => 'S256',
    'prompt' => 'select_account',
    'access_type' => 'online',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;