<?php
// tests/test-mail.php — kirim email uji melalui SMTP yang dikonfigurasi di .env (Brevo).
//
// Cara pakai:
//   1. Isi SMTP_USER / SMTP_PASS / MAIL_FROM (pengirim terverifikasi) di .env
//   2. php tests/test-mail.php penerima@contoh.com
//   Jika SMTP belum diisi, helper fallback ke mail() dan mode tampil 'mail'.

require __DIR__ . '/../config/mail.php';

$to = $argv[1] ?? null;
if ($to === null) {
    echo "Gunakan: php tests/test-mail.php email-tujuan\n";
    exit(1);
}

$r = send_mail($to, '[Al-Mutqon] Uji Kirim Email', "Ini email uji dari Al-Mutqon.\nWaktu: " . date('c'));

echo "Mode : {$r['mode']}\n";
echo $r['ok'] ? "OK    : email terkirim.\n" : "GAGAL : {$r['error']}\n";
echo $r['ok'] ? "Detail: cek {$to} (inkl. spam).\n" : "TAMBAH: detail SMTP ada di error_log bila APP_DEBUG=true.\n";
exit($r['ok'] ? 0 : 1);