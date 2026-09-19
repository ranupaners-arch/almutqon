<?php
// config/mail.php — kirim email via SMTP (Brevo) tanpa library eksternal.
// Konfigurasi dibaca dari .env (SMTP_HOST/PORT/ENCRYPTION/USER/PASS, MAIL_FROM*, APP_DEBUG).
// Bila SMTP tidak dikonfigurasi, fallback ke mail() (biasanya nonaktif di lokal/live).

require_once __DIR__ . '/database.php'; // memastikan .env sudah ter-parse

// Baca konfigurasi SMTP; default mengarah ke Brevo (smtp-relay.brevo.com).
function mail_config(): array
{
    $host = getenv('SMTP_HOST');
    $port = getenv('SMTP_PORT');
    $user = getenv('SMTP_USER');
    $pass = getenv('SMTP_PASS');
    $from = getenv('MAIL_FROM');
    $fromName = getenv('MAIL_FROM_NAME');
    $enc = getenv('SMTP_ENCRYPTION');
    $debug = getenv('APP_DEBUG') === 'true';

    return [
        'enabled'   => $host !== false && $host !== '' && $user !== false && $user !== '' && $pass !== false && $pass !== '',
        'host'      => $host !== false && $host !== '' ? $host : 'smtp-relay.brevo.com',
        'port'      => $port !== false && $port !== '' ? (int) $port : 587,
        'user'      => $user !== false ? $user : '',
        'pass'      => $pass !== false ? $pass : '',
        // Dari Brevo harus alamat pengirim yang sudah diverifikasi; default = SMTP_USER bila kosong.
        'from'      => $from !== false && $from !== '' ? $from : ($user !== false && $user !== '' ? $user : 'admin@almutqon.test'),
        'from_name' => $fromName !== false && $fromName !== '' ? $fromName : 'Al-Mutqon',
        'encryption'=> $enc !== false && $enc !== '' ? strtolower($enc) : 'tls', // tls=STARTTLS, ssl=langsung TLS (465)
        'debug'     => $debug,
    ];
}

// Kirim email. Return: ['ok'=>bool, 'mode'=>'smtp'|'mail', 'error'=>string]
// $attachment = ['filename'=>string, 'data'=>string, 'mime'=>string] | null
function send_mail(string $to, string $subject, string $body, ?array $attachment = null): array
{
    $cfg = mail_config();

    if ($cfg['enabled']) {
        if (smtp_send($cfg, $to, $subject, $body, $attachment)) {
            return ['ok' => true, 'mode' => 'smtp', 'error' => ''];
        }
        return ['ok' => false, 'mode' => 'smtp', 'error' => 'Gagal mengirim via SMTP.'];
    }

    // Fallback ke mail() (tidak tersedia di sebagian besar hosting gratis) — tanpa lampiran.
    $headers = 'From: ' . encode_header($cfg['from_name']) . ' <' . clean_address($cfg['from']) . '>' . "\r\n"
        . 'Reply-To: ' . clean_address($cfg['from']) . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
        . 'MIME-Version: 1.0';
    $ok = @mail(clean_address($to), encode_header($subject), $body, $headers);
    return [
        'ok' => $ok,
        'mode' => 'mail',
        'error' => $ok ? '' : 'mail() tidak aktif atau gagal.',
    ];
}

// Implementasi minimal klien SMTP (EHLO → STARTTLS → AUTH LOGIN → MAIL → RCPT → DATA → QUIT).
function smtp_send(array $cfg, string $to, string $subject, string $body, ?array $attachment = null): bool
{
    $host = $cfg['host'];
    $port = $cfg['port'];
    $useSsl = $cfg['encryption'] === 'ssl' || $port === 465;
    $timeout = 20;

    $errno = 0;
    $errstr = '';
    $sock = @stream_socket_client(
        ($useSsl ? 'ssl://' : 'tcp://') . $host . ':' . $port,
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT
    );
    if (!$sock) {
        error_log('[mail] koneksi SMTP gagal: ' . $errstr);
        return false;
    }
    stream_set_timeout($sock, $timeout);

    $read = function () use ($sock, $cfg): array {
        $line = fgets($sock, 515);
        if ($line === false) {
            return ['code' => 0, 'text' => $cfg['debug'] ? 'koneksi terputus / timeout' : ''];
        }
        return ['code' => (int) substr($line, 0, 3), 'text' => rtrim($line, "\r\n")];
    };

    // Kirim perintah lalu baca semua baris respons (beberapa baris bila kode diikuti '-').
    $cmd = function (string $c) use ($sock, $read): array {
        fwrite($sock, $c . "\r\n");
        $code = 0;
        $text = '';
        while (true) {
            $r = $read();
            if ($r['code'] === 0) {
                return $r;
            }
            $code = $r['code'];
            $text .= $r['text'] . ' ';
            $line = $r['text'];
            // Kode 3 digit diikuti '-' berarti masih ada baris berikutnya.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        return ['code' => $code, 'text' => trim($text)];
    };
    $expect = function (array $r, int $code, string $step) use ($cfg): bool {
        if ($r['code'] !== $code) {
            error_log("[mail] SMTP {$step}: ekspektasi {$code}, dapat {$r['code']} ({$r['text']})");
            return false;
        }
        return true;
    };

    // Konsumsi greeting "220" yang dikirim server segera setelah koneksi.
    if (!$expect($read(), 220, 'greeting')) {
        fclose($sock);
        return false;
    }

    $helo = php_uname('n') ?: 'localhost';
    if (!$expect($cmd('EHLO ' . $helo), 250, 'EHLO')) {
        fclose($sock);
        return false;
    }

    // STARTTLS bila diminta (port 587) lalu EHLO ulang.
    if ($cfg['encryption'] === 'tls' && !$useSsl) {
        if (!$expect($cmd('STARTTLS'), 220, 'STARTTLS')) {
            fclose($sock);
            return false;
        }
        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log('[mail] STARTTLS crypto gagal.');
            fclose($sock);
            return false;
        }
        if (!$expect($cmd('EHLO ' . $helo), 250, 'EHLO (TLS)')) {
            fclose($sock);
            return false;
        }
    }

    // AUTH LOGIN
    if (!$expect($cmd('AUTH LOGIN'), 334, 'AUTH LOGIN')) {
        fclose($sock);
        return false;
    }
    if (!$expect($cmd(base64_encode($cfg['user'])), 334, 'AUTH user')) {
        fclose($sock);
        return false;
    }
    if (!$expect($cmd(base64_encode($cfg['pass'])), 235, 'AUTH password')) {
        fclose($sock);
        return false;
    }

    $from = clean_address($cfg['from']);
    $rcpt = clean_address($to);
    if (!$expect($cmd("MAIL FROM:<{$from}>"), 250, 'MAIL FROM')) {
        fclose($sock);
        return false;
    }
    if (!$expect($cmd("RCPT TO:<{$rcpt}>"), 250, 'RCPT TO')) {
        fclose($sock);
        return false;
    }
    if (!$expect($cmd('DATA'), 354, 'DATA')) {
        fclose($sock);
        return false;
    }

    $msg = build_message($cfg, $to, $subject, $body, $attachment);
    $sent = @fwrite($sock, $msg);
    $done = @fwrite($sock, "\r\n.\r\n");
    $r = $read();
    fclose($sock);

    if ($sent === false || $done === false) {
        error_log('[mail] gagal menulis DATA.');
        return false;
    }
    return $r['code'] === 250;
}

// Bangun badan email (multipart/mixed bila ada lampiran; base64 UTF-8 agar aman multibyte).
function build_message(array $cfg, string $to, string $subject, string $body, ?array $attachment = null): string
{
    $fromEmail = clean_address($cfg['from']);
    $fromName = clean_address($cfg['from_name']);
    $toEmail = clean_address($to);
    $host = $_SERVER['HTTP_HOST'] ?? 'almutqon.local';

    if ($attachment !== null && !empty($attachment['data'])) {
        $boundary = '----=_almutqon_' . md5(uniqid('', true));
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $attachment['filename'] ?? 'lampiran.pdf');
        $mime = $attachment['mime'] ?? 'application/pdf';

        $head = "Date: " . gmdate('r') . "\r\n"
            . "To: <{$toEmail}>\r\n"
            . "From: " . encode_header($fromName) . " <{$fromEmail}>\r\n"
            . "Reply-To: {$fromEmail}\r\n"
            . "Subject: " . encode_header($subject) . "\r\n"
            . "Message-ID: <" . uniqid('', true) . "@{$host}>\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n"
            . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . chunk_split(base64_encode($body)) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: {$mime}; name=\"{$filename}\"\r\n"
            . "Content-Disposition: attachment; filename=\"{$filename}\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . chunk_split(base64_encode($attachment['data'])) . "\r\n"
            . "--{$boundary}--\r\n";
        return $head;
    }

    return "Date: " . gmdate('r') . "\r\n"
        . "To: <{$toEmail}>\r\n"
        . "From: " . encode_header($fromName) . " <{$fromEmail}>\r\n"
        . "Reply-To: {$fromEmail}\r\n"
        . "Subject: " . encode_header($subject) . "\r\n"
        . "Message-ID: <" . uniqid('', true) . "@{$host}>\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n"
        . "\r\n"
        . chunk_split(base64_encode($body));
}

// Encode header RFC 2047 (B) supaya aman untuk UTF-8 dan mencegah header injection.
function encode_header(string $text): string
{
    $text = preg_replace('/[\r\n]+/', ' ', $text);
    if (preg_match('/[\x80-\xFF]/', $text)) {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
    return $text;
}

// Bersihkan alamat email / nama dari CRLF (anti header injection).
function clean_address(string $value): string
{
    return trim(preg_replace('/[\r\n]+/', '', $value));
}