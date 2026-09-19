<?php
// config/whatsapp.php — kirim pesan WhatsApp via provider HTTP POST (cURL).
// Konfigurasi di .env: WA_API_URL, WA_API_TOKEN, WA_ENABLED (true/false).
// Payload default JSON {"to": "...", "message": "..."} + header Bearer —
// SESUAIKAN blok $payload/$headers di send_whatsapp() dengan format provider
// yang dipakai (Meta Cloud API / Wablas / Fonz / dsb), kredensial tetap di .env.

require_once __DIR__ . '/database.php'; // memastikan .env sudah ter-parse + TZ WIB

// Baca konfigurasi; aktif hanya bila URL & token terisi dan tidak dimatikan.
function wa_config(): array
{
    $url = getenv('WA_API_URL');
    $token = getenv('WA_API_TOKEN');
    $enabled = getenv('WA_ENABLED');
    $url = ($url !== false) ? trim($url) : '';
    $token = ($token !== false) ? trim($token) : '';

    return [
        'enabled' => $url !== '' && $token !== '' && ($enabled === false || strtolower(trim($enabled)) !== 'false'),
        'url'     => $url,
        'token'   => $token,
        'debug'   => getenv('APP_DEBUG') === 'true',
    ];
}

// Normalisasi nomor ke format internasional Indonesia (628…).
// '0822…' → '62822…', '+62822…' → '62822…', '62822…' tetap. Return false bila tak valid.
function wa_normalize(string $no): string|false
{
    $d = preg_replace('/[^0-9]/', '', $no);
    if ($d === '' || $d === null) {
        return false;
    }
    if (str_starts_with($d, '0')) {
        $d = '62' . substr($d, 1);
    } elseif (str_starts_with($d, '8')) {
        $d = '62' . $d;
    }
    // Panjang nomor Indonesia wajar: 9–15 digit.
    if (!str_starts_with($d, '62') || strlen($d) < 9 || strlen($d) > 15) {
        return false;
    }
    return $d;
}

// Kirim pesan. Return: ['ok'=>bool, 'skipped'=>bool, 'error'=>string]
// 'skipped' = WA belum dikonfigurasi/dimatikan → pemanggil tetap lanjut, tanpa error_log.
function send_whatsapp(string $no_wa, string $pesan): array
{
    $cfg = wa_config();
    if (!$cfg['enabled']) {
        return ['ok' => false, 'skipped' => true, 'error' => 'WA belum dikonfigurasi (WA_API_URL/WA_API_TOKEN kosong).'];
    }

    $to = wa_normalize($no_wa);
    if ($to === false) {
        return ['ok' => false, 'skipped' => false, 'error' => 'Nomor WhatsApp tidak valid.'];
    }
    if (trim($pesan) === '') {
        return ['ok' => false, 'skipped' => false, 'error' => 'Isi pesan kosong.'];
    }
    if (!function_exists('curl_init')) {
        error_log('[wa] ekstensi cURL PHP tidak tersedia.');
        return ['ok' => false, 'skipped' => false, 'error' => 'cURL tidak tersedia.'];
    }

    // === Sesuaikan bagian ini dengan format provider Anda ===
    $payload = json_encode(['to' => $to, 'message' => $pesan], JSON_UNESCAPED_UNICODE);
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $cfg['token'],
    ];
    // === akhir blok provider ===

    $ch = curl_init($cfg['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        error_log('[wa] cURL gagal ke ' . $to . ': ' . $err);
        return ['ok' => false, 'skipped' => false, 'error' => 'Koneksi ke API WA gagal.'];
    }
    if ($code < 200 || $code >= 300) {
        error_log('[wa] API balas HTTP ' . $code . ' untuk ' . $to . ($cfg['debug'] ? ' : ' . substr((string) $resp, 0, 300) : ''));
        return ['ok' => false, 'skipped' => false, 'error' => 'API WA menolak pesan (HTTP ' . $code . ').'];
    }
    return ['ok' => true, 'skipped' => false, 'error' => ''];
}
