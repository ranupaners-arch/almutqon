<?php
// config/pendaftar-admin-actions.php — POST action bersama halaman Data Pendaftar & Pembayaran
// Persiapan pemanggil: $pdo tersedia, require database.php + auth.php + csrf.php + pembayaran.php
// File ini menambah require mail.php + pdf.php (kirim struk otomatis saat LUNAS)
// + verif-client.php (daftarkan struk ke API verifikasi, best-effort).
// Setelah action: redirect, exit.

require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/pdf.php';
require_once __DIR__ . '/whatsapp.php';
require_once __DIR__ . '/verif-client.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dihubungi'])) {
    require_csrf();
    $id = (int) $_POST['dihubungi'];
    $stmt = $pdo->prepare("UPDATE pendaftar SET status = 'dihubungi' WHERE id = ?");
    $stmt->execute([$id]);
    flash('Status diubah menjadi "Sudah dihubungi".');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lunas'])) {
    require_csrf();
    $id = (int) $_POST['lunas'];
    $metodeBayar = in_array($_POST['metode_pembayaran'] ?? '', ['bank', 'qris', 'ewallet'], true)
        ? $_POST['metode_pembayaran'] : 'bank';

    $stmt = $pdo->prepare("SELECT * FROM pendaftar WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_error('Pendaftar tidak ditemukan.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Nomor invoice baru dibuat DI SINI (saat pembayaran dikonfirmasi diterima).
    $noInvoice = tandai_lunas($pdo, $id, $metodeBayar, (int) $row['biaya']);

    // Lengkapi data untuk generate struk
    $row['no_invoice'] = $noInvoice;
    $row['status_pembayaran'] = 'lunas';
    $row['metode_pembayaran'] = $metodeBayar;
    $row['tanggal_bayar'] = date('Y-m-d H:i:s');

    $email = trim((string) ($row['user_email'] ?? ''));
    $adminNama = $_SESSION['user']['name'] ?? 'Admin Almutqon';
    $pdfBytes = null;

    // 1) Bangun PDF struk (tanpa QR) — bukti yang didaftarkan ke layanan verifikasi.
    try {
        $pdfBytes = (new StrukPdf())->struk($row, pembayaran_info(), $adminNama);
    } catch (\Throwable $t) {
        error_log('[struk-pdf] ' . $t->getMessage());
        $pdfBytes = null;
    }

    // 2) Daftarkan dokumen ke API verifikasi (best-effort: gagal tak membatalkan LUNAS).
    //    Dilakukan SEBELUM email agar QR berisi tautan verifikasi yang sudah jadi.
    $vr = ['ok' => false, 'skipped' => true, 'code' => '', 'url' => '', 'error' => ''];
    if ($pdfBytes !== null) {
        $vr = verif_register_doc($pdo, $id, $row, $adminNama, $pdfBytes);
    }

    // 3) Kirim email. PDF BER-QR dipakai bila verifikasi berhasil; fallback ke PDF biasa.
    if ($email !== '') {
        try {
            $verif = ($vr['ok'] && trim((string) ($vr['url'] ?? '')) !== '')
                ? ['url' => $vr['url'], 'code' => (string) ($vr['code'] ?? '')]
                : null;
            $pdf = ($verif !== null && $pdfBytes !== null)
                ? (new StrukPdf())->struk($row, pembayaran_info(), $adminNama, $verif)
                : $pdfBytes;
            if ($pdf === null) {
                throw new \RuntimeException('PDF struk tidak tersedia');
            }
            $parVerif = ($verif !== null)
                ? "\nVerifikasi keaslian:\n{$verif['url']}\nKode verifikasi: {$verif['code']}\n"
                : "\nStruk belum tercatat untuk verifikasi keaslian (QR tidak tersedia).\n";
            $body = "Yth. " . ($row['nama'] ?? 'Saudara/Saudari') . ",\n\n"
                . "Terima kasih atas pembayaran kursus AL-MUTQON.\n\n"
                . "No. Invoice   : {$noInvoice}\n"
                . "Total         : " . rupiah((int) $row['biaya']) . "\n"
                . "Status        : LUNAS\n"
                . "Tanggal Bayar : " . date('d M Y H:i') . "\n\n"
                . "Struk/kwitansi resmi terlampir pada email ini (PDF). Simpan sebagai bukti pembayaran."
                . $parVerif
                . "\n-- Administrasi Al-Mutqon\nadmin.almutqon@gmail.com";
            $r = send_mail($email, "Struk Pembayaran Kursus AL-MUTQON — {$noInvoice}", $body, [
                'filename' => 'struk-' . $noInvoice . '.pdf',
                'data' => $pdf,
                'mime' => 'application/pdf',
            ]);
            $baseMsg = $r['ok']
                ? "Pembayaran LUNAS. Struk dikirim ke email {$email} (Invoice: {$noInvoice})."
                : "Pembayaran LUNAS (Invoice: {$noInvoice}), tetapi struk GAGAL terkirim ke {$email}.";
            $baseErr = !$r['ok'];
        } catch (\Throwable $t) {
            error_log('[struk-email] ' . $t->getMessage());
            $baseMsg = "Pembayaran LUNAS (Invoice: {$noInvoice}), tetapi struk gagal terkirim karena kesalahan sistem.";
            $baseErr = true;
        }
    } else {
        $baseMsg = "Pembayaran LUNAS (Invoice: {$noInvoice}). Email pendaftar kosong — struk tidak terkirim.";
        $baseErr = true;
    }

    if ($vr['ok']) {
        $baseMsg .= " Dokumen terverifikasi (Kode: {$vr['code']}).";
    } elseif (!$vr['skipped']) {
        $baseMsg .= " Pencatatan verifikasi gagal — ulangi dari halaman Pembayaran.";
    }
    if ($baseErr) {
        flash_error($baseMsg);
    } else {
        flash($baseMsg);
    }

    // Notifikasi WhatsApp: pembayaran diterima + invoice + link unduh struk PDF.
    // Kegagalan kirim TIDAK membatalkan status LUNAS (hanya dicatat di log).
    try {
        $host = $_SERVER['HTTP_HOST'] ?? 'almutqon.test';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $strukUrl = $scheme . '://' . $host . '/pages/bayar/struk-pdf.php?no=' . urlencode($noInvoice);
        $pesanWa = "Assalamu'alaikum " . ($row['nama'] ?? 'Saudara/Saudari') . ",\n\n"
            . "Pembayaran kursus AL-MUTQON Anda telah kami terima. Status: LUNAS.\n\n"
            . "No. Invoice : {$noInvoice}\n"
            . "Total       : " . rupiah((int) $row['biaya']) . "\n\n"
            . "Unduh struk/kwitansi resmi (PDF) di sini:\n{$strukUrl}\n\n"
            . "Selamat belajar — semoga ilmunya bermanfaat.\n\n"
            . "-- Administrasi Al-Mutqon";
        $wa = send_whatsapp((string) ($row['whatsapp'] ?? ''), $pesanWa);
        if (!$wa['ok'] && !$wa['skipped']) {
            error_log('[lunas-wa] gagal ke ' . ($row['whatsapp'] ?? '') . ': ' . $wa['error']);
        }
    } catch (\Throwable $t) {
        error_log('[lunas-wa] ' . $t->getMessage());
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batal_lunas'])) {
    require_csrf();
    $id = (int) $_POST['batal_lunas'];
    $stmt = $pdo->prepare("UPDATE pendaftar SET status_pembayaran = 'belum', metode_pembayaran = '', tanggal_bayar = NULL WHERE id = ?");
    $stmt->execute([$id]);
    // Rujukan verifikasi lokal dipertahankan (riwayat jujur); dokumen remote dicabut.
    $vr = verif_revoke_doc($pdo, $id);
    $msg = 'Status pembayaran dikembalikan ke "Belum".';
    if ($vr['ok']) {
        $msg .= ' Dokumen verifikasi ikut dicabut.';
    } elseif (!$vr['skipped']) {
        $msg .= ' (Revoke verifikasi gagal — dicatat di log.)';
    }
    flash($msg);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi_ulang'])) {
    require_csrf();
    $id = (int) $_POST['verifikasi_ulang'];
    $stmt = $pdo->prepare("SELECT * FROM pendaftar WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || $row['status_pembayaran'] !== 'lunas' || ($row['no_invoice'] ?? '') === '') {
        flash_error('Hanya pengajuan LUNAS ber-invoice yang bisa diverifikasi.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!empty($row['verif_token'])) {
        flash('Dokumen ini sudah terverifikasi.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    try {
        $adminNama = $_SESSION['user']['name'] ?? 'Admin Almutqon';
        $pdf = (new StrukPdf())->struk($row, pembayaran_info(), $adminNama);
        $vr = verif_register_doc($pdo, $id, $row, $adminNama, $pdf);
        if ($vr['ok']) {
            flash("Dokumen terverifikasi (Invoice: {$row['no_invoice']}, Kode: {$vr['code']}).");
        } else {
            flash_error('Verifikasi gagal: ' . ($vr['error'] !== '' ? $vr['error'] : 'kesalahan sistem.'));
        }
    } catch (\Throwable $t) {
        error_log('[verif-retry] ' . $t->getMessage());
        flash_error('Verifikasi gagal karena kesalahan sistem.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pendaftar'])) {
    require_csrf();
    $id = (int) $_POST['hapus_pendaftar'];
    $stmt = $pdo->prepare("SELECT nama FROM pendaftar WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_error('Data pendaftar tidak ditemukan.');
    } else {
        $del = $pdo->prepare("DELETE FROM pendaftar WHERE id = ?");
        $del->execute([$id]);
        flash('Data pendaftar "' . ($row['nama'] ?? '') . '" berhasil dihapus.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}