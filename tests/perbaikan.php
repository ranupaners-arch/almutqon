<?php
// tests/perbaikan.php — Tes regresi untuk perbaikan yang sudah diterapkan
//
// Cara pakai:
//   php tests/perbaikan.php
//   php tests/perbaikan.php http://almutqon.test
//   AMQ_ADMIN_EMAIL=admin@email.com AMQ_ADMIN_PASS=rahasia php tests/perbaikan.php http://almutqon.test

$envBase = getenv('AMQ_BASE_URL');
$baseUrl = rtrim($argv[1] ?? ($envBase !== false && $envBase !== '' ? $envBase : 'http://almutqon.test'), '/');
$email   = getenv('AMQ_ADMIN_EMAIL') ?: 'admin.almutqon@gmail.com';
$pass    = getenv('AMQ_ADMIN_PASS') ?: '';

require __DIR__ . '/../config/sanitize.php';
require __DIR__ . '/../config/google.php';

if ($pass === '') {
    echo "Password admin untuk {$email}: ";
    $pass = trim(fgets(STDIN));
}

$passCount = 0;
$failCount = 0;

function t(string $name, bool $cond, string $detail = ''): void
{
    global $passCount, $failCount;
    if ($cond) {
        $passCount++;
        printf("  \033[32mPASS\033[0m  %s%s\n", $name, $detail !== '' ? " ($detail)" : '');
    } else {
        $failCount++;
        printf("  \033[31mFAIL\033[0m  %s%s\n", $name, $detail !== '' ? " ($detail)" : '');
    }
}

function http(string $method, string $url, ?string $cookieFile, ?array $post = null): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ]);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post ?? []);
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    $location = '';
    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
        $location = trim($m[1]);
    }

    return ['status' => $status, 'location' => $location, 'body' => $body];
}

echo "\n=== P1-2: Sanitasi HTML (config/sanitize.php) ===\n";

$clean1 = sanitize_html('<p>Sehat</p><script>alert(document.cookie)</script><p>Jalan</p>');
t('tag <script> dihapus', stripos($clean1, '<script') === false);

$clean2 = sanitize_html('<a href="javascript:alert(1)">x</a><a href="data:text/html,xx">y</a><a href="//evil.com">z</a>');
t('href javascript:/data:/proto-rel diblokir', stripos($clean2, 'javascript:') === false && stripos($clean2, 'data:text') === false && stripos($clean2, '//evil.com') === false);

$clean3 = sanitize_html('<img src="x" onerror="alert(1)">');
t('atribut onerror dihapus', stripos($clean3, 'onerror') === false);

$clean4 = sanitize_html('<div style="background:url(javascript:alert(1)); position:fixed; color:red">x</div>');
t('CSS expression/javascript diblokir', stripos($clean4, 'javascript') === false && stripos($clean4, 'position') === false && stripos($clean4, 'color: red') !== false);

$clean5 = sanitize_html('<p onclick="alert(1)">klik</p><iframe src="https://evil.com"></iframe>');
t('onclick & iframe dihapus', stripos($clean5, 'onclick') === false && stripos($clean5, 'iframe') === false);

$quill = '<h2>Judul</h2><p>Arab: <strong>العربية</strong> — emoji 🙌</p><ul><li>a</li></ul><p class="ql-align-center">tengah</p>';
$clean6 = sanitize_html($quill);
t('HTML Quill dipertahankan (h2/strong/ul/class)', strpos($clean6, '<h2>') !== false && strpos($clean6, '<strong>') !== false && strpos($clean6, '<ul>') !== false && strpos($clean6, 'ql-align-center') !== false);
t('UTF-8 (Arab & emoji) tidak rusak', strpos($clean6, 'العربية') !== false && strpos($clean6, '🙌') !== false);

echo "\n=== Google OAuth: helper crypto ===\n";

$bin = random_bytes(32);
t('base64url roundtrip', google_base64url_decode(google_base64url_encode($bin)) === $bin);

$payload = ['verifier' => google_base64url_encode(random_bytes(64))];
t('PKCE challenge 43 karakter (S256)', strlen(google_base64url_encode(hash('sha256', $payload['verifier'], true))) === 43);

$rsa = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$details = openssl_pkey_get_details($rsa);
$n = rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '=');
$e = rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '=');
$pem = google_jwk_to_pem(['n' => $n, 'e' => $e]);
$pub = openssl_pkey_get_public($pem);
openssl_sign('pesan-uji', $sig, $rsa, OPENSSL_ALGO_SHA256);
t('JWK->PEM dikenali openssl + verifikasi RS256', $pub !== false && openssl_verify('pesan-uji', $sig, $pub, OPENSSL_ALGO_SHA256) === 1);

echo "\n=== Google OAuth: endpoint google-login.php ===\n";
$r = http('GET', "{$baseUrl}/auth/google-login.php", null);
$unconfigured = $r['status'] === 400 && strpos($r['body'], 'Belum Aktif') !== false;
$configured = $r['status'] === 302 && stripos($r['location'], 'accounts.google.com') !== false;
t('redirect ke Google (jika client id diset) atau pesan belum aktif', $unconfigured || $configured, "status {$r['status']}");

echo "\n=== Integrasi verifikasi (config/verif-client.php, unit) ===\n";
require __DIR__ . '/../config/verif-client.php';
$vcfg = verif_config();
t('verif_config terbaca (url + token, enabled)', $vcfg['enabled'] === true && $vcfg['url'] !== '' && $vcfg['token'] !== '', $vcfg['url']);
$origVUrl = $vcfg['url'];
putenv('VERIF_API_URL=http://127.0.0.1:9');
$vr = verif_api('create', ['no_surat' => 'UJI-MATI']);
t('API mati -> gagal graceful tanpa fatal', $vr['ok'] === false && $vr['skipped'] === false && $vr['error'] !== '');
putenv('VERIF_API_URL=' . $origVUrl);
$vr2 = verif_api('revoke', []);
t('API tanpa identitas -> 400/404 graceful', $vr2['ok'] === false && in_array($vr2['http'], [400, 404], true), 'HTTP ' . $vr2['http']);

echo "\n=== Sinkron personel (config/pengajar.php mapping) ===\n";
require_once __DIR__ . '/../config/pengajar.php';
t('direktur -> direktur', peran_to_verif_role('direktur') === 'direktur');
t('koor_mubtadi -> staff', peran_to_verif_role('koor_mubtadi') === 'staff');
t('pengajar -> staff', peran_to_verif_role('pengajar') === 'staff');

echo "\n=== P1-1: Autentikasi Admin (admin/*.php) ===\n";
echo "Target: {$baseUrl}\n";

$jar = tempnam(sys_get_temp_dir(), 'amqtest');

$r = http('GET', "{$baseUrl}/admin/daftar-artikel.php", $jar);
t('tanpa login -> 302 ke login.php', $r['status'] === 302 && strpos($r['location'], 'login.php') !== false, "status {$r['status']} loc {$r['location']}");

// Ambil token CSRF dari halaman login (wajib untuk POST)
$r = http('GET', "{$baseUrl}/admin/login.php", $jar);
preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $m);
$csrf = $m[1] ?? '';
t('halaman login menyediakan token CSRF', $csrf !== '', 'token ' . ($csrf !== '' ? 'ditemukan' : 'kosong'));

$r = http('POST', "{$baseUrl}/admin/login.php", $jar, ['email' => $email, 'password' => $pass]);
t('POST login tanpa CSRF -> ditolak (tidak redirect)', $r['status'] === 200 && strpos($r['body'], 'token tidak valid') !== false, "status {$r['status']}");

$r = http('POST', "{$baseUrl}/admin/login.php", $jar, ['email' => $email, 'password' => 'password_salah_xyz', 'csrf_token' => $csrf]);
t('login password salah -> ditolak (200 + pesan)', $r['status'] === 200 && stripos($r['body'], 'salah') !== false, "status {$r['status']}");

$r = http('POST', "{$baseUrl}/admin/login.php", $jar, ['email' => $email, 'password' => $pass, 'csrf_token' => $csrf]);
t('login password benar -> 302 ke daftar-artikel', $r['status'] === 302 && strpos($r['location'], 'daftar-artikel.php') !== false, "status {$r['status']} loc {$r['location']}");

$r = http('GET', "{$baseUrl}/admin/daftar-artikel.php", $jar);
t('daftar-artikel setelah login -> 200', $r['status'] === 200, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/tambah-artikel.php", $jar);
t('tambah-artikel setelah login -> 200', $r['status'] === 200, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/data-jurnal.php", $jar);
$hasNoticeJ = preg_match('/\b(Notice|Warning|Deprecated)\b/i', $r['body']) === 1;
t('data-jurnal setelah login -> 200 tanpa notice', $r['status'] === 200 && !$hasNoticeJ && strpos($r['body'], 'Tugas Peserta') !== false, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/data-agenda.php", $jar);
$hasNoticeA = preg_match('/\b(Notice|Warning|Deprecated)\b/i', $r['body']) === 1;
t('data-agenda setelah login -> 200 tanpa notice', $r['status'] === 200 && !$hasNoticeA && strpos($r['body'], 'Daftar Agenda') !== false, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/export-pembayaran.php", $jar);
t('export-pembayaran admin -> CSV LUNAS + nominal murni', $r['status'] === 200 && strpos($r['body'], 'No Invoice') !== false && stripos($r['body'], 'Deprecated') === false && stripos($r['body'], 'Rp') === false, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/export-donasi.php", $jar);
t('export-donasi admin -> CSV diterima + nominal murni', $r['status'] === 200 && strpos($r['body'], 'Kanal Pembayaran') !== false && stripos($r['body'], 'Deprecated') === false, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/logout.php", $jar);
t('logout -> 302 ke login.php', $r['status'] === 302 && strpos($r['location'], 'login.php') !== false, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/daftar-artikel.php", $jar);
t('setelah logout -> 302 ke login.php', $r['status'] === 302 && strpos($r['location'], 'login.php') !== false, "status {$r['status']}");

echo "\n=== Halaman Akun: tanpa notice PHP (session double-start) ===\n";
$r = http('GET', "{$baseUrl}/admin/login.php", $jar);
preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $m2);
$csrf2 = $m2[1] ?? '';
$r = http('POST', "{$baseUrl}/admin/login.php", $jar, ['email' => $email, 'password' => $pass, 'csrf_token' => $csrf2]);
foreach (['pages/akun/pengaturan.php', 'pages/akun/ganti-password.php', 'pages/akun/profil.php', 'pages/akun/pendaftaran-saya.php'] as $pg) {
    $r = http('GET', "{$baseUrl}/{$pg}", $jar);
    $clean = $r['body'];
    // Ambil hanya bagian halaman (skip jika redirect)
    $hasNotice = preg_match('/\b(Notice|Warning|Deprecated)\b/i', $clean) === 1;
    t("{$pg} tanpa Notice/Warning/Deprecated", $r['status'] === 200 && !$hasNotice, "status {$r['status']}" . ($hasNotice ? ' -> Notice!' : ''));
}
$r = http('GET', "{$baseUrl}/admin/logout.php", $jar);

unlink($jar);

echo "\n=== Pendaftaran Saya: akses & tautan status ===\n";
$r = http('GET', "{$baseUrl}/pages/akun/pendaftaran-saya.php", null);
t('tanpa login -> 302 ke /', $r['status'] === 302 && $r['location'] === '/', "status {$r['status']} loc {$r['location']}");

echo "\n=== Tugas & Materi: akses endpoint ===\n";
$r = http('GET', "{$baseUrl}/pages/akun/jurnal-materi.php", null);
t('jurnal-materi tanpa login -> 302 ke /', $r['status'] === 302 && $r['location'] === '/', "status {$r['status']} loc {$r['location']}");

foreach (['materi.php', 'unggah-tugas.php', 'riwayat-tugas.php'] as $pg) {
    $r = http('GET', "{$baseUrl}/pages/akun/{$pg}", null);
    t("{$pg} tanpa login -> 302 ke /", $r['status'] === 302 && $r['location'] === '/', "status {$r['status']}");
}

$r = http('GET', "{$baseUrl}/pages/akun/kalender.php", null);
t('kalender tanpa login -> 302 ke /', $r['status'] === 302 && $r['location'] === '/', "status {$r['status']} loc {$r['location']}");

$r = http('GET', "{$baseUrl}/admin/data-agenda.php", null);
t('data-agenda tanpa login -> 302 ke login.php', $r['status'] === 302 && strpos($r['location'], 'login.php') !== false, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/data-pengajar.php", null);
t('data-pengajar tanpa login -> 302 ke login.php', $r['status'] === 302 && strpos($r['location'], 'login.php') !== false, "status {$r['status']}");

echo "\n=== Dashboard pengajar ===\n";
$r = http('GET', "{$baseUrl}/pengajar/", null);
t('dashboard pengajar tanpa login -> 302 ke login-pengajar', $r['status'] === 302 && strpos($r['location'], 'login-pengajar.php') !== false, "status {$r['status']} loc {$r['location']}");

$r = http('GET', "{$baseUrl}/pengajar/sdm.php", null);
t('sdm tanpa login -> 403 (khusus Direktur)', $r['status'] === 403, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/auth/login-pengajar.php", null);
t('form login pengajar -> 200', $r['status'] === 200 && strpos($r['body'], 'ID Pengajar') !== false, "status {$r['status']}");

echo "\n=== Halaman info & absensi ===\n";
foreach (['faq.php', 'kebijakan-privasi.php', 'syarat-ketentuan.php'] as $pg) {
    $r = http('GET', "{$baseUrl}/pages/info/{$pg}", null);
    t("info/{$pg} -> 200", $r['status'] === 200, "status {$r['status']}");
}
$r = http('GET', "{$baseUrl}/pengajar/absensi.php", null);
t('absensi tanpa login -> 302 ke login-pengajar', $r['status'] === 302 && strpos($r['location'], 'login-pengajar.php') !== false, "status {$r['status']}");

foreach (['wali.php', 'rpp.php', 'rekap.php'] as $pg) {
    $r = http('GET', "{$baseUrl}/pengajar/{$pg}", null);
    t("pengajar/{$pg} tanpa login -> 302", $r['status'] === 302, "status {$r['status']}");
}

$r = http('GET', "{$baseUrl}/api/upload-jurnal.php", null);
t('upload-jurnal GET -> 302 (hanya POST)', $r['status'] === 302, "status {$r['status']}");

$r = http('POST', "{$baseUrl}/api/upload-jurnal.php", null, ['judul_jurnal' => 'x']);
t('upload-jurnal tanpa CSRF -> 403', $r['status'] === 403, "status {$r['status']}");

$r = http('POST', "{$baseUrl}/pengajar/tugas.php", null, ['nilai_tugas' => '1']);
t('nilai tugas tanpa CSRF -> 403', $r['status'] === 403, "status {$r['status']}");

$r = http('POST', "{$baseUrl}/pages/akun/riwayat-tugas.php", null, ['hapus_tugas' => '1']);
t('hapus tugas user tanpa CSRF -> 403', $r['status'] === 403, "status {$r['status']}");

$r = http('POST', "{$baseUrl}/pengajar/tugas.php", null, ['hapus_tugas' => '1']);
t('hapus tugas pengajar tanpa CSRF -> 403', $r['status'] === 403, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/data-jurnal.php", null);
t('data-jurnal tanpa login -> 302 ke login.php', $r['status'] === 302 && strpos($r['location'], 'login.php') !== false, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/api/unduh-jurnal.php?id=999999", null);
t('unduh-jurnal tanpa login -> 404', $r['status'] === 404, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/api/unduh-materi.php?id=999999", null);
t('unduh-materi tanpa login -> 404', $r['status'] === 404, "status {$r['status']}");

echo "\n=== WhatsApp server-side ===\n";
require __DIR__ . '/../config/whatsapp.php';
require_once __DIR__ . '/../config/pengajar.php';t('wa_normalize 08… → 628…', wa_normalize('082258442647') === '6282258442647');
t('wa_normalize +62… → 628…', wa_normalize('+6282258442647') === '6282258442647');
t('wa_normalize 62… tetap', wa_normalize('6282258442647') === '6282258442647');
t('wa_normalize sampah/kosong → false', wa_normalize('abc') === false && wa_normalize('') === false);
if (!wa_config()['enabled']) {
    $r = send_whatsapp('082258442647', 'uji');
    t('tanpa konfigurasi → skipped tanpa error', $r['skipped'] === true && $r['ok'] === false);
} else {
    echo "  \033[33mSKIP\033[0m  WA terkonfigurasi, lewati tes skipped (hindari kirim nyata)\n";
}

$r = http('GET', "{$baseUrl}/api/cron-wa-reminder.php", null);
t('cron tanpa key -> 403', $r['status'] === 403, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/api/cron-wa-reminder.php?key=salah", null);
t('cron key salah -> 403', $r['status'] === 403, "status {$r['status']}");

echo "\n=== Data pengajar: peran & ID ===\n";
t('6 peran pengajar terdaftar', count(peran_pengajar_options()) === 6);
t('label peran koordinator', peran_label('koor_mubtadi') === "Koordinator Marhalah Mubtadi'" && peran_label('direktur') === 'Direktur');

echo "\n=== Ekspor CSV finansial ===\n";$r = http('GET', "{$baseUrl}/admin/export-pembayaran.php", null);
t('export-pembayaran tanpa login -> 302 ke login.php', $r['status'] === 302 && strpos($r['location'], 'login.php') !== false, "status {$r['status']}");

$r = http('GET', "{$baseUrl}/admin/export-donasi.php", null);
t('export-donasi tanpa login -> 302 ke login.php', $r['status'] === 302 && strpos($r['location'], 'login.php') !== false, "status {$r['status']}");

echo "\n=== P1-2 End-to-end: XSS lewat DB (detail.php) ===\n";
$pdo = null;
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=db_almutqon-website;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo "  \033[33mSKIP\033[0m  DB tidak bisa diakses, lewati tes e2e\n";
}

if ($pdo) {
    $payload = '<p>Sehat</p><script>alert(document.cookie)</script><img src="x" onerror="alert(1)"><a href="javascript:alert(1)">link</a>';
    $pdo->prepare('INSERT INTO articles (judul, author, kategori, gambar, konten) VALUES (?, ?, ?, ?, ?)')
        ->execute(['UJI-XSS-PERBAIKAN', 'tester', 'Pendidikan', '', $payload]);
    $id = (int) $pdo->lastInsertId();

    $r = http('GET', "{$baseUrl}/pages/artikel/detail.php?id={$id}", null);
    t('konten artikel XSS tampil bersih di halaman detail (status 200)', $r['status'] === 200 && stripos($r['body'], '<script>alert') === false && stripos($r['body'], 'onerror=') === false && stripos($r['body'], 'javascript:') === false, "status {$r['status']}");

    $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
    echo "  (artikel uji sudah dibersihkan)\n";

    // Baris pendaftar milik admin (user_email = email admin) untuk uji halaman riwayat.
    $pdo->prepare("INSERT INTO pendaftar (program, nama, whatsapp, metode, user_email, biaya, metode_pembayaran, kanal_pembayaran, batas_bayar) VALUES ('offline','UJI-RIWAYAT','08123456789','Privat',?,636000,'bank','BSI',DATE_ADD(NOW(), INTERVAL 24 HOUR))")->execute([$email]);
    $pid = (int) $pdo->lastInsertId();

    // Login kembali sebagai admin (= pemilik baris uji).
    $jar2 = tempnam(sys_get_temp_dir(), 'amqtest');
    $r = http('GET', "{$baseUrl}/admin/login.php", $jar2);
    preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $m3);
    $csrf3 = $m3[1] ?? '';
    $r = http('POST', "{$baseUrl}/admin/login.php", $jar2, ['email' => $email, 'password' => $pass, 'csrf_token' => $csrf3]);
    t('login ulang admin untuk uji riwayat -> 302', $r['status'] === 302 && strpos($r['location'], 'daftar-artikel.php') !== false, "status {$r['status']}");

    $r = http('GET', "{$baseUrl}/pages/akun/pendaftaran-saya.php", $jar2);
    t('pemilik melihat baris + tautan Lihat Status', $r['status'] === 200 && strpos($r['body'], 'tunggu.php?id=' . $pid) !== false && strpos($r['body'], 'Lihat Status') !== false, "status {$r['status']}");

    $r = http('GET', "{$baseUrl}/pages/bayar/tunggu.php?id={$pid}", $jar2);
    t('tautan status milik sendiri -> 200 + tombol salin', $r['status'] === 200 && strpos($r['body'], 'data-copy') !== false, "status {$r['status']}");

    $r = http('GET', "{$baseUrl}/admin/logout.php", $jar2);
    unlink($jar2);
    $pdo->prepare('DELETE FROM pendaftar WHERE id = ?')->execute([$pid]);
    echo "  (baris uji pendaftar sudah dibersihkan)\n";

    // E2E verifikasi dokumen: lunas -> terdaftar -> struk/tunggu tampil -> batal -> revoked.
    echo "\n=== Integrasi verifikasi (e2e admin) ===\n";
    $verPdo = null;
    try {
        $verPdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=db_almutqon-verif;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        echo "  \033[33mSKIP\033[0m  DB verif tidak bisa diakses, lewati tes e2e verifikasi\n";
    }
    if ($verPdo) {
        $pdo->prepare("INSERT INTO pendaftar (program,nama,whatsapp,metode,user_email,biaya,metode_pembayaran,kanal_pembayaran,batas_bayar) VALUES ('offline','UJI-VERIF','08123456789','Privat',?,636000,'bank','BSI',DATE_ADD(NOW(), INTERVAL 24 HOUR))")->execute([$email]);
        $vid = (int) $pdo->lastInsertId();

        $jarV = tempnam(sys_get_temp_dir(), 'amqtest');
        $r = http('GET', "{$baseUrl}/admin/login.php", $jarV);
        preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $mv0);
        $r = http('POST', "{$baseUrl}/admin/login.php", $jarV, ['email' => $email, 'password' => $pass, 'csrf_token' => $mv0[1] ?? '']);
        $r = http('GET', "{$baseUrl}/admin/data-pembayaran.php", $jarV);
        preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $mv1);
        $r = http('POST', "{$baseUrl}/admin/data-pembayaran.php", $jarV, ['lunas' => (string) $vid, 'metode_pembayaran' => 'bank', 'csrf_token' => $mv1[1] ?? '']);
        t('lunas via UI -> 302', $r['status'] === 302, "status {$r['status']}");
        $r = http('GET', "{$baseUrl}/admin/data-pembayaran.php", $jarV);
        t('flash sebut dokumen terverifikasi', strpos($r['body'], 'Dokumen terverifikasi (Kode:') !== false);

        $vrow = $pdo->query("SELECT * FROM pendaftar WHERE id={$vid}")->fetch(PDO::FETCH_ASSOC);
        t('kolom verif_token/code/url terisi', !empty($vrow['verif_token']) && !empty($vrow['verif_code']) && !empty($vrow['verif_url']));
        $vinv = (string) $vrow['no_invoice'];
        $vd = $verPdo->query('SELECT * FROM dokumen WHERE no_surat=' . $verPdo->quote($vinv))->fetch(PDO::FETCH_ASSOC);
        t('dokumen verif aktif + PDF tersimpan', $vd && $vd['status'] === 'active' && !empty($vd['pdf_path']));

        $r = http('GET', "{$baseUrl}/pages/bayar/struk.php?no=" . urlencode($vinv), $jarV);
        t('struk tampilkan blok verifikasi + QR', $r['status'] === 200 && strpos($r['body'], 'VERIFIKASI KEASLIAN DOKUMEN') !== false && strpos($r['body'], 'id="verifQr"') !== false, "status {$r['status']}");

        $r = http('GET', "{$baseUrl}/admin/data-pembayaran.php", $jarV);
        preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $mv2);
        $r = http('POST', "{$baseUrl}/admin/data-pembayaran.php", $jarV, ['batal_lunas' => (string) $vid, 'csrf_token' => $mv2[1] ?? '']);
        $r = http('GET', "{$baseUrl}/admin/data-pembayaran.php", $jarV);
        t('batal -> flash ikut dicabut', strpos($r['body'], 'ikut dicabut') !== false);
        $vst = $verPdo->query('SELECT status FROM dokumen WHERE no_surat=' . $verPdo->quote($vinv))->fetchColumn();
        t('dokumen verif berstatus revoked', $vst === 'revoked');

        // Bersih-bersih kedua DB + file PDF uji.
        if (!empty($vd['pdf_path'])) {
            @unlink('/Users/mac/Herd/verif-almt/storage/uploads/' . basename((string) $vd['pdf_path']));
        }
        if (!empty($vd['id'])) {
            $verPdo->prepare('DELETE FROM activity_log WHERE dokumen_id=?')->execute([(int) $vd['id']]);
            $verPdo->prepare('DELETE FROM dokumen WHERE id=?')->execute([(int) $vd['id']]);
        }
        $r = http('GET', "{$baseUrl}/admin/logout.php", $jarV);
        unlink($jarV);
        $pdo->prepare('DELETE FROM pendaftar WHERE id = ?')->execute([$vid]);
        echo "  (baris uji verifikasi sudah dibersihkan di kedua DB)\n";
    }

    // E2E sinkron personel: tambah -> staff tercatat -> toggle ikut -> hapus ikut lepas.
    echo "\n=== Sinkron personel (e2e hook pengajar) ===\n";
    if ($verPdo ?? null) {
        $jarH = tempnam(sys_get_temp_dir(), 'amqtest');
        $r = http('GET', "{$baseUrl}/admin/login.php", $jarH);
        preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $mh0);
        $r = http('POST', "{$baseUrl}/admin/login.php", $jarH, ['email' => $email, 'password' => $pass, 'csrf_token' => $mh0[1] ?? '']);
        $r = http('GET', "{$baseUrl}/admin/data-pengajar.php", $jarH);
        preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $mh1);
        $r = http('POST', "{$baseUrl}/admin/data-pengajar.php", $jarH, ['tambah_pengajar' => 1, 'nama' => 'UJI SINKRON', 'email' => 'uji.sinkron@contoh.id', 'password' => 'Rahasia123', 'peran' => 'pengajar', 'csrf_token' => $mh1[1] ?? '']);
        t('tambah pengajar -> 302', $r['status'] === 302, "status {$r['status']}");
        $r = http('GET', "{$baseUrl}/admin/data-pengajar.php", $jarH);
        t('flash staff tersinkron', strpos($r['body'], 'Staff verifikasi tersinkron.') !== false);
        $hu = $pdo->query("SELECT * FROM users WHERE email='uji.sinkron@contoh.id'")->fetch(PDO::FETCH_ASSOC);
        t('user punya NIP + link', $hu && !empty($hu['id_pengajar']) && !empty($hu['verif_staff_id']));
        $hs = $verPdo->query('SELECT * FROM staff WHERE nip=' . $verPdo->quote((string) $hu['id_pengajar']))->fetch(PDO::FETCH_ASSOC);
        t('staff verif tercatat (role staff, aktif)', $hs && $hs['role'] === 'staff' && (int) $hs['is_active'] === 1);

        $r = http('GET', "{$baseUrl}/admin/data-pengajar.php", $jarH);
        preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $mh2);
        $r = http('POST', "{$baseUrl}/admin/data-pengajar.php", $jarH, ['toggle_aktif' => (string) $hu['id'], 'csrf_token' => $mh2[1] ?? '']);
        $ha = $verPdo->query('SELECT is_active FROM staff WHERE id=' . (int) $hs['id'])->fetchColumn();
        t('revoke ikut nonaktifkan staff', (int) $ha === 0);

        $r = http('GET', "{$baseUrl}/admin/data-pengajar.php", $jarH);
        preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $mh3);
        $r = http('POST', "{$baseUrl}/admin/data-pengajar.php", $jarH, ['hapus_pengajar' => (string) $hu['id'], 'csrf_token' => $mh3[1] ?? '']);
        $r = http('GET', "{$baseUrl}/admin/data-pengajar.php", $jarH);
        t('hapus ikut lepas tautan', strpos($r['body'], 'ikut dinonaktifkan') !== false);
        $hd = $verPdo->query('SELECT is_active,almutqon_user_id FROM staff WHERE id=' . (int) $hs['id'])->fetch(PDO::FETCH_ASSOC);
        t('staff off + tautan lepas', (int) $hd['is_active'] === 0 && $hd['almutqon_user_id'] === null);

        $verPdo->prepare('DELETE FROM activity_log WHERE description LIKE ?')->execute(['%' . $hu['id_pengajar'] . '%']);
        $verPdo->prepare('DELETE FROM staff WHERE id=?')->execute([(int) $hs['id']]);
        $r = http('GET', "{$baseUrl}/admin/logout.php", $jarH);
        unlink($jarH);
        echo "  (baris uji sinkron sudah dibersihkan di kedua DB)\n";
    }

    // Siklus revoke: user temp bisa login → admin revoke → login ditolak → bersih.
    t('format ID pengajar PGJ-XXXX', preg_match('/^PGJ-(\d{4}|\d{8})$/', buat_id_pengajar($pdo)) === 1);
    $pdo->prepare("INSERT INTO users (name, email, password, peran, id_pengajar, aktif) VALUES (?,?,?,?,?,1)")
        ->execute(['UJI-REVOKE', 'uji.revoke@contoh.com', password_hash('Rahasia123', PASSWORD_BCRYPT), 'pengajar', 'PGJ-UJIT']);
    $uid = (int) $pdo->lastInsertId();
    $jar3 = tempnam(sys_get_temp_dir(), 'amqtest');
    $r = http('GET', "{$baseUrl}/", $jar3);
    preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $m4);
    $r = http('POST', "{$baseUrl}/auth/login.php", $jar3, ['email' => 'uji.revoke@contoh.com', 'password' => 'Rahasia123', 'csrf_token' => $m4[1] ?? '']);
    t('pengajar aktif bisa login', strpos($r['body'], '"success":true') !== false);

    $jar4 = tempnam(sys_get_temp_dir(), 'amqtest');
    $r = http('GET', "{$baseUrl}/admin/login.php", $jar4);
    preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $m5);
    $r = http('POST', "{$baseUrl}/admin/login.php", $jar4, ['email' => $email, 'password' => $pass, 'csrf_token' => $m5[1] ?? '']);
    $r = http('GET', "{$baseUrl}/admin/data-pengajar.php", $jar4);
    t('data-pengajar admin -> 200 + tombol Revoke', $r['status'] === 200 && strpos($r['body'], 'Revoke') !== false && strpos($r['body'], 'PGJ-UJIT') !== false, "status {$r['status']}");
    preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $m6);
    $r = http('POST', "{$baseUrl}/admin/data-pengajar.php", $jar4, ['toggle_aktif' => (string) $uid, 'csrf_token' => $m6[1] ?? '']);
    t('revoke via POST -> 302', $r['status'] === 302, "status {$r['status']}");

    $r = http('POST', "{$baseUrl}/auth/login.php", $jar3, ['email' => 'uji.revoke@contoh.com', 'password' => 'Rahasia123', 'csrf_token' => $m4[1] ?? '']);
    t('login setelah revoke -> ditolak', strpos($r['body'], 'dinonaktifkan') !== false);

    $r = http('GET', "{$baseUrl}/admin/logout.php", $jar4);
    unlink($jar3);
    unlink($jar4);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    echo "  (user uji revoke sudah dibersihkan)\n";
}

echo "\n===================== HASIL =====================\n";
printf("  \033[32mLulus: %d\033[0m   \033[31mGagal: %d\033[0m\n", $passCount, $failCount);
exit($failCount === 0 ? 0 : 1);