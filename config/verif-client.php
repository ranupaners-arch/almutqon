<?php
// config/verif-client.php — klien API internal verif-almt v1 (Fase 2)
// Konfigurasi dari .env: VERIF_API_URL + VERIF_API_TOKEN (JAGA RAHASIA).
// Prinsip: gagal API TIDAK PERNAH fatal — selalu kembalikan array + error_log,
// alur LUNAS di sisi almutqon tetap jalan (verifikasi bisa diulang dari admin).

// Parser .env ringan (cermin config/database.php) agar klien tetap berfungsi di
// konteks CLI/cron yang tidak me-require database.php. Tidak menimpa env eksisting.
function verif_load_env(): void
{
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

function verif_config(): array
{
    verif_load_env();
    $url = getenv('VERIF_API_URL');
    $token = getenv('VERIF_API_TOKEN');
    $url = ($url !== false) ? rtrim(trim((string) $url), '/') : '';
    $token = ($token !== false) ? trim((string) $token) : '';
    return ['url' => $url, 'token' => $token, 'enabled' => ($url !== '' && $token !== '')];
}

// Panggil API v1. $pdfPath opsional (path file PDF lokal untuk field `pdf`).
// $endpoint: 'dokumen' (struk) atau 'staf' (personel).
// Return: ['ok'=>bool, 'skipped'=>bool, 'http'=>int, 'data'=>array|null, 'error'=>string]
function verif_api(string $action, array $fields, ?string $pdfPath = null, string $endpoint = 'dokumen'): array
{
    $cfg = verif_config();
    if (!$cfg['enabled']) {
        return ['ok' => false, 'skipped' => true, 'http' => 0, 'data' => null, 'error' => 'verifikasi tidak dikonfigurasi'];
    }

    $post = array_merge(['action' => $action], $fields);
    if ($pdfPath !== null) {
        if (!is_file($pdfPath)) {
            error_log('[verif-api] file PDF tidak ada: ' . $pdfPath);
            return ['ok' => false, 'skipped' => false, 'http' => 0, 'data' => null, 'error' => 'file PDF tidak ada'];
        }
        $post['pdf'] = new CURLFile($pdfPath, 'application/pdf', 'struk.pdf');
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $cfg['url'] . '/api/v1/' . $endpoint . '.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $cfg['token']],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        error_log('[verif-api] curl ' . $action . ': ' . $err);
        return ['ok' => false, 'skipped' => false, 'http' => 0, 'data' => null, 'error' => 'jaringan: ' . $err];
    }
    $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $dec = json_decode((string) $raw, true);
    if (!is_array($dec)) {
        error_log('[verif-api] respons bukan JSON (' . $action . ' HTTP ' . $http . ')');
        return ['ok' => false, 'skipped' => false, 'http' => $http, 'data' => null, 'error' => 'respons tak dikenal'];
    }
    if ($http >= 200 && $http < 300 && ($dec['success'] ?? false)) {
        return ['ok' => true, 'skipped' => false, 'http' => $http, 'data' => $dec['data'] ?? null, 'error' => ''];
    }
    $msg = (string) ($dec['message'] ?? ('HTTP ' . $http));
    error_log('[verif-api] ' . $action . ' gagal: ' . $msg);
    return ['ok' => false, 'skipped' => false, 'http' => $http, 'data' => $dec['data'] ?? null, 'error' => $msg];
}

// Daftarkan struk LUNAS sebagai dokumen terverifikasi + simpan rujukan ke pendaftar.
// $row = baris pendaftar (wajib: id, no_invoice, nama, tanggal_bayar). $pdfBytes = isi PDF struk.
// Return: ['ok'=>bool, 'skipped'=>bool, 'code'=>string, 'url'=>string, 'error'=>string]
function verif_register_doc(PDO $pdo, int $pendaftarId, array $row, string $adminNama, string $pdfBytes): array
{
    $noInvoice = (string) ($row['no_invoice'] ?? '');
    if ($noInvoice === '') {
        return ['ok' => false, 'skipped' => true, 'code' => '', 'url' => '', 'error' => 'tanpa invoice'];
    }
    $label = match ($row['program'] ?? '') {
        'offline' => 'Offline ' . ($row['metode'] ?? ''),
        'online' => 'Online ' . ($row['metode'] ?? ''),
        'dauroh' => 'Dauroh ' . ($row['metode'] ?? ''),
        default => 'Kursus',
    };
    $tglBayar = !empty($row['tanggal_bayar']) ? date('Y-m-d', strtotime($row['tanggal_bayar'])) : date('Y-m-d');
    $fields = [
        'no_surat' => $noInvoice,
        'jenis' => 'invoice',
        'perihal' => 'Struk pembayaran kursus ' . trim($label),
        'penerima' => (string) ($row['nama'] ?? ''),
        'tanggal_dokumen' => $tglBayar,
        'jabatan_ttd' => 'Bagian Keuangan',
        'penandatangan' => $adminNama !== '' ? $adminNama : 'Admin Almutqon',
    ];

    $tmp = tempnam(sys_get_temp_dir(), 'amqstruk');
    $tmpPdf = $tmp . '.pdf';
    if (!@rename($tmp, $tmpPdf) || file_put_contents($tmpPdf, $pdfBytes) === false) {
        @unlink($tmp);
        error_log('[verif-api] gagal menyiapkan PDF sementara');
        return ['ok' => false, 'skipped' => false, 'code' => '', 'url' => '', 'error' => 'gagal menyiapkan PDF'];
    }
    try {
        $res = verif_api('create', $fields, $tmpPdf);
    } finally {
        @unlink($tmpPdf);
    }

    if (!$res['ok']) {
        return ['ok' => false, 'skipped' => $res['skipped'], 'code' => '', 'url' => '', 'error' => $res['error']];
    }
    $data = is_array($res['data']) ? $res['data'] : [];
    $stmt = $pdo->prepare('UPDATE pendaftar SET verif_token = ?, verif_code = ?, verif_url = ? WHERE id = ?');
    $stmt->execute([(string) ($data['token'] ?? ''), (string) ($data['code_6digit'] ?? ''), (string) ($data['verify_url'] ?? ''), $pendaftarId]);
    return ['ok' => true, 'skipped' => false, 'code' => (string) ($data['code_6digit'] ?? ''), 'url' => (string) ($data['verify_url'] ?? ''), 'error' => ''];
}

// Cerminkan personel (baris users ber-peran pengajar) ke staff verif (best-effort).
// $mode: 'upsert' (tambah/ubah) atau 'toggle' (aktif/nonaktif) atau 'lepas' (hapus user:
// nonaktifkan pasangan + putuskan tautan). Peran direktur SENGAJA dilewati
// (hanya manual di dashboard verif, menjaga proteksi direktur-terakhir).
// Return: ['ok'=>bool, 'skipped'=>bool, 'error'=>string]
function verif_sync_pengajar(PDO $pdo, array $user, string $mode): array
{
    $nip = trim((string) ($user['id_pengajar'] ?? ''));
    if ($nip === '') {
        return ['ok' => false, 'skipped' => true, 'error' => 'tanpa ID pengajar'];
    }
    if (($user['peran'] ?? '') === 'direktur') {
        error_log('[verif-api] skip sinkron direktur (manual): ' . $nip);
        return ['ok' => false, 'skipped' => true, 'error' => 'direktur manual'];
    }
    if ($mode === 'upsert') {
        require_once __DIR__ . '/pengajar.php';
        $res = verif_api('upsert', [
            'nip' => $nip,
            'nama_lengkap' => (string) ($user['name'] ?? ''),
            'jabatan' => peran_label((string) ($user['peran'] ?? '')),
            'email' => (string) ($user['email'] ?? ''),
            'role' => peran_to_verif_role((string) ($user['peran'] ?? '')),
            'is_active' => ((int) ($user['aktif'] ?? 1) === 1) ? 1 : 0,
            'almutqon_user_id' => (int) ($user['id'] ?? 0),
        ], null, 'staf');
        if ($res['ok'] && is_array($res['data']) && isset($res['data']['id'])) {
            $stmt = $pdo->prepare('UPDATE users SET verif_staff_id = ? WHERE id = ?');
            $stmt->execute([(int) $res['data']['id'], (int) ($user['id'] ?? 0)]);
        }
        return ['ok' => $res['ok'], 'skipped' => $res['skipped'], 'error' => $res['error']];
    }
    if ($mode === 'toggle') {
        $res = verif_api('toggle', [
            'nip' => $nip,
            'is_active' => ((int) ($user['aktif'] ?? 1) === 1) ? 1 : 0,
        ], null, 'staf');
        return ['ok' => $res['ok'], 'skipped' => $res['skipped'], 'error' => $res['error']];
    }
    if ($mode === 'lepas') {
        $res = verif_api('toggle', ['nip' => $nip, 'is_active' => 0, 'hapus_tautan' => 1], null, 'staf');
        return ['ok' => $res['ok'], 'skipped' => $res['skipped'], 'error' => $res['error']];
    }
    return ['ok' => false, 'skipped' => true, 'error' => 'mode tak dikenal'];
}
// Cabut dokumen verifikasi milik pendaftar (dipakai saat batal_lunas).
// Rujukan lokal (token/url) SENGAJA dipertahankan: struk tetap menaut ke halaman
// verifikasi yang jujur menampilkan status "dicabut".
// Return: ['ok'=>bool, 'skipped'=>bool, 'error'=>string]
function verif_revoke_doc(PDO $pdo, int $pendaftarId): array
{
    $stmt = $pdo->prepare('SELECT no_invoice FROM pendaftar WHERE id = ?');
    $stmt->execute([$pendaftarId]);
    $noInvoice = (string) ($stmt->fetchColumn() ?: '');
    if ($noInvoice === '') {
        return ['ok' => false, 'skipped' => true, 'error' => 'tanpa invoice'];
    }
    $res = verif_api('revoke', ['no_surat' => $noInvoice]);
    return ['ok' => $res['ok'], 'skipped' => $res['skipped'], 'error' => $res['error']];
}
