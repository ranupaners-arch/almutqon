<?php
// config/google.php — helper "Masuk dengan Google" (OAuth 2.0 + PKCE, tanpa library)
// Mengikuti alur authorization code + PKCE (OAuth 2.1 style), verifikasi id_token
// dengan JWKS Google (RS256) + cek klaim (iss/aud/exp/email_verified).

// --- Konfigurasi ---

// Halaman sederhana bertema Almutqon untuk pesan error OAuth
function google_error_page(string $title, string $message): void
{
    http_response_code(400);
    echo '<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<title>' . htmlspecialchars($title) . ' - Almutqon</title>
<style>body{font-family:system-ui,sans-serif;background:#f4f6f9;color:#333;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.card{background:#fff;padding:40px;border-radius:12px;max-width:480px;box-shadow:0 4px 6px rgba(0,0,0,.05)}
h2{color:#0f766e;margin-top:0;border-bottom:2px solid #e2e8f0;padding-bottom:12px}
code{background:#f1f5f9;padding:2px 6px;border-radius:4px}
a{color:#0f766e}</style>
</head><body><div class="card">
<h2>' . htmlspecialchars($title) . '</h2>
<p>' . nl2br(htmlspecialchars($message)) . '</p>
<p><a href="/">&larr; Kembali ke beranda</a></p>
</div></body></html>';
    exit;
}

function google_client_id(): string
{
    $v = getenv('GOOGLE_CLIENT_ID');
    return ($v !== false && $v !== '') ? $v : '';
}

function google_client_secret(): string
{
    $v = getenv('GOOGLE_CLIENT_SECRET');
    return ($v !== false && $v !== '') ? $v : '';
}

// URI callback — override via GOOGLE_REDIRECT_URI, atau dihitung dari request
function google_redirect_uri(): string
{
    $env = getenv('GOOGLE_REDIRECT_URI');
    if ($env !== false && $env !== '') {
        return $env;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'almutqon.test';
    return ($https ? 'https' : 'http') . '://' . $host . '/auth/google-callback.php';
}

// --- Utilitas base64url + URL ---

function google_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function google_base64url_decode(string $data): string
{
    $b64 = strtr($data, '-_', '+/');
    return base64_decode($b64, true) ?: '';
}

// --- HTTP POST (curl) ---

function google_http_post(string $url, array $fields): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $json = json_decode($raw, true);
    return [
        'status' => $status,
        'raw' => $raw,
        'json' => is_array($json) ? $json : [],
    ];
}

// --- JWKS Google (cache 1 jam di file temp) ---

function google_jwks(): array
{
    $cacheFile = sys_get_temp_dir() . '/amq_google_jwks.json';
    $cached = null;
    if (is_file($cacheFile)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
    }
    if (is_array($cached) && isset($cached['fetched']) && (time() - (int) $cached['fetched']) < 3600) {
        return $cached['keys'];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.googleapis.com/oauth2/v3/certs',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);

    $json = json_decode((string) $raw, true);
    $keys = is_array($json) && isset($json['keys']) ? $json['keys'] : [];
    file_put_contents($cacheFile, json_encode(['fetched' => time(), 'keys' => $keys]));
    return $keys;
}

// --- Build PEM dari JWK RSA (n, e) ---

function google_der_len(int $len): string
{
    if ($len < 0x80) {
        return chr($len);
    }
    $bytes = '';
    while ($len > 0) {
        $bytes = chr($len & 0xff) . $bytes;
        $len >>= 8;
    }
    return chr(0x80 | strlen($bytes)) . $bytes;
}

function google_der_tlv(int $tag, string $content): string
{
    return chr($tag) . google_der_len(strlen($content)) . $content;
}

function google_jwk_to_pem(array $jwk): string
{
    $n = google_base64url_decode($jwk['n'] ?? '');
    $e = google_base64url_decode($jwk['e'] ?? '');
    if ($n === '' || $e === '') {
        return '';
    }
    $n = (ord($n[0]) & 0x80) ? "\x00" . $n : $n;
    $e = (ord($e[0]) & 0x80) ? "\x00" . $e : $e;

    $rsaPubKey = google_der_tlv(0x30, google_der_tlv(0x02, $n) . google_der_tlv(0x02, $e));
    $algoId = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
    $bitString = google_der_tlv(0x03, "\x00" . $rsaPubKey);
    $spki = google_der_tlv(0x30, $algoId . $bitString);

    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki)) . "-----END PUBLIC KEY-----";
}

// --- Verifikasi id_token (RS256) + klaim ---

function google_verify_id_token(string $idToken, string $clientId): ?array
{
    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
        return null;
    }
    [$b64Header, $b64Payload, $b64Signature] = $parts;

    $header = json_decode(google_base64url_decode($b64Header), true);
    $claims = json_decode(google_base64url_decode($b64Payload), true);
    if (!is_array($header) || !is_array($claims)) {
        return null;
    }

    // Alg wajib RS256
    if (($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
        return null;
    }

    // Cari kunci dengan kid yang cocok
    $pem = '';
    foreach (google_jwks() as $key) {
        if (($key['kid'] ?? '') === $header['kid'] && ($key['kty'] ?? '') === 'RSA') {
            $pem = google_jwk_to_pem($key);
            break;
        }
    }
    if ($pem === '') {
        return null;
    }

    $data = $b64Header . '.' . $b64Payload;
    $signature = google_base64url_decode($b64Signature);
    $pub = openssl_pkey_get_public($pem);
    if ($pub === false) {
        return null;
    }
    $ok = openssl_verify($data, $signature, $pub, OPENSSL_ALGO_SHA256);
    if ($ok !== 1) {
        return null;
    }

    // Klaim wajib
    $now = time();
    if (($claims['iss'] ?? '') !== 'https://accounts.google.com'
        && ($claims['iss'] ?? '') !== 'accounts.google.com') {
        return null;
    }
    if (($claims['aud'] ?? '') !== $clientId) {
        return null;
    }
    if (($claims['exp'] ?? 0) < $now) {
        return null;
    }
    if (($claims['email_verified'] ?? false) !== true || empty($claims['email'])) {
        return null;
    }

    return $claims;
}