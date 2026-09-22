# CHANGELOG_FIXES — Almutqon

Catatan perbaikan bug. Terbaru di ATAS. Ditulis sesudah fix terverifikasi.

### Fix #3 — Nama penandatangan struk tidak konsisten antarsaluran

**Gejala:** Struk yang dikirim email, yang diunduh (`struk-pdf.php`), dan yang ditampilkan web (`struk.php`) memuat nama berbeda di bawah garis "Bagian Keuangan" — email memakai nama admin sesi, unduhan tanpa login "Admin Almutqon", web memakai merek "Almutqon". Terlihat seperti dokumen berbeda padahal isi lain identik.

**Akar:** Nama penandatangan diambil `$_SESSION['user']['name'] ?? 'Admin Almutqon'` saat build (tergantung siapa yang mengeklik/unduh), web memakai merek hardcoded "Almutqon". Tiga jalur lewat kode berbeda → identitas melenceng.

| | |
|---|---|
| **Tanggal** | 2026-09-20 |
| **File** | `config/pdf.php` (`ttd()`), `pages/bayar/struk.php` |
| **Masalah** | Nama penandatangan berbeda di email vs unduhan vs web |
| **Akar** | Nama dari sesi admin (berubah sesuai pengguna) vs merek hardcoded di web |
| **Fix** | Penandatangan di-pin jadi identitas tetap **"Admin Almutqon"** di `StrukPdf::ttd()` (keputusan user) — semua build (email, unduhan, upload verifikasi) otomatis identik; `struk.php` ganti `<b>Almutqon</b>` → `<b>Admin Almutqon</b>`. Tanpa perubahan skema |
| **Verifikasi** | `php -l` kedua file bersih; `struk(row,$verif,'Ummu Rania')` vs `struk(row,$verif,'Admin Almutqon')` → byte-identik, teks `(Admin Almutqon) Tj` ada, QR 803 modul; unduhan nyata `struk-pdf.php?no=INV-20260920-1539` → "Admin Almutqon" (26.880 B); web `struk.php` → `<b>Admin Almutqon</b>`. Email lama (terkirim sebelum fix) tetap memakai nama admin saat itu |
| **Pelajaran** | Satu identitas tanda tangan harus ditentukan sekali sebagai keputusan produk lalu di-pin di sumber (rendering PDF), bukan dilewatkan sebagai parameter dari sesi — konsistensi lintas saluran jadi kebetulan |
| **Log Keyword** | penandatangan, ttd, struk, tanda tangan, konsisten, identitas |
| **Deploy** | BELUM — lokal belum di-push/publish |

### Fix #2 — Tata letak verifikasi tidak seragam & blok verifikasi terlalu berat

**Gejala:** `struk.php` web masih memakai tanda tangan dua kolom ("Penerima / Orang Tua" + "Bagian Keuangan" hardcoded) dan blok verifikasi beserta judul "VERIFIKASI KEASLIAN DOKUMEN" + paragraf tautan panjang; `tunggu.php` punya judul + caption; PDF (Fix #1) memakai kotak verifikasi sendiri. Visual tidak konsisten antarsaluran.

**Akar:** Tanda tangan dua kolom adalah peninggalan desain awal (kolom penerima kosong tak berguna, nama penandatangan hardcoded); blok verifikasi tekstual dirancang sebelum user memutuskan hanya QR + kode sebagai bukti keaslian. `StrukPdf::ttd()` + blok `.sign`/`.verif`/`.verif2` berkembang terpisah di tiga tempat sehingga desainnya melenceng.

| | |
|---|---|
| **Tanggal** | 2026-09-20 |
| **File** | `config/pdf.php`, `pages/bayar/struk.php`, `pages/bayar/tunggu.php`, `tests/perbaikan.php`; docs: `CATATAN.md`, `CHANGELOG_FIXES.md` |
| **Masalah** | Tanda tangan & blok verifikasi tidak seragam dan kelebihan teks; kolom "Penerima / Orang Tua" kosong dan tanpa makna |
| **Akar** | Tiga renderer (PDF, `struk.php`, `tunggu.php`) mendesain blok yang sama secara terpisah; kolom tanda tangan peninggalan desain lama |
| **Fix** | (1) `StrukPdf::ttd()` → satu kolom: label "Bagian Keuangan" → QR (`re f`, pusat halaman, `96/(n+8)` px/modul, quiet 4) → `Kode:` (posisi lama dipertahankan: `self::MARGIN`/baseline) → garis → nama; tanpa verif: `y-=20` lalu garis+nama; (2) `struk.php`: kolom penerima + blok `.verif` (judul/caption/link) dihapus, QR `#verifQr` polos hitam-putih (`dotsOptions` `type:'square'`) di dalam `.sign__col`; (3) `tunggu.php`: h3 + paragraf tautan dihapus, `.verif2` hanya Kode + QR, lalu JS QR memakai `data-url` di kontainer (sebelumnya ambil `<a>` yang sudah dihapus → akan gagal render); (4) asersi `tests/perbaikan.php:369-371` disesuaikan (QR+Kode hadir; judul verif & "Penerima / Orang Tua" absen) |
| **Verifikasi** | `php -l` 4 file bersih. Sample PDF (berverif: `INV-…`, n=29) & unduhan nyata `struk-pdf.php` (`INV-20260920-2923`, URL 104 char → n=41/v6): struktur valid (header/EOF/startxref/`/Count 1`), teks terlarang (judul verif, caption, nama kolom penerima) ABSEN, `Kode:` ada, modul QR hasil rekonstruksi dari `re f` **IDENTICAL** dgn `qr_matrix(url,'M')`, posisi QR di antara label dan kode (margin ≥10pt). Sample tanpa-verif: tanpa QR & tanpa Kode. Web `struk.php` di-record lunas asli: `id="verifQr"`, `Kode: E15F33`, tanpa teks terlarang. **Tercatat:** regresi e2e `tests/perbaikan.php` gagal 26 dari awal sesi (login ulang admin → 200, verif kolom kosong, struk 404) — kegagalan kaskade yang berawal di seksi "P1-2 XSS" dan tak menyentuh tata letak; asersi Fix #2 tak bisa diverifikasi end-to-end sampai itu beres. Verifikasi visual QR web butuh browser (JS) |
| **Pelajaran** | Susun blok bersama satu kali (label→QR→kode→garis→nama) lalu pakai di semua renderer agar tak melenceng. Saat menghapus elemen DOM, cek dependensi JS yang membacanya (JS tunggu.php mengambil `.verif2 a` yang dihapus — `link` jadi null → QR tak tergambar; diganti `data-url`). Edit yang menghapus method cukup membuat duplikasi `rect()` → `php -l` fatal "Cannot redeclare"; selalu `php -l` setelah edit blok besar |
| **Log Keyword** | verifikasi, tanda tangan, struk, tunggu, ttd, qr-code-styling, faktur, tampilan seragam, Fix#1 |
| **Deploy** | BELUM — perubahan lokal belum di-push/deploy; browser-check visual QR web & e2e penuh tertunda |

### Fix #1 — Faktur email tidak memuat QR verifikasi keaslian

**Gejala:** Struk/kwitansi (PDF) yang dikirim ke email pendaftar saat pembayaran LUNAS tidak punya QR verifikasi, padahal halaman `tunggu.php` sudah menampilkan QR; alur pengiriman juga mengirimkan PDF sebelum kode verifikasi dibuat.

**Akar:** Dua hal: (1) `config/pendaftar-admin-actions.php` memanggil `send_mail()` lebih dulu (baris lama 63-67) lalu baru `verif_register_doc()` (baris lama 93) — saat PDF disusun, `verif_url`/`verif_code` belum ada, sehingga QR tidak mungkin digambar; (2) `StrukPdf::struk()` (`config/pdf.php`) tidak punya gambar atau QR sama sekali — generator PDF murni PHP hanya mendukung teks/garis, dan tidak ada encoder QR di codebase (implementasi awal dibuat lalu dihapus user).

| | |
|---|---|
| **Tanggal** | 2026-09-20 |
| **File** | `config/qrcode.php` (baru), `config/pdf.php`, `config/pendaftar-admin-actions.php`, `pages/bayar/struk-pdf.php` |
| **Masalah** | Struk email tanpa QR verifikasi; urutan alur LUNAS membuat QR mustahil digambar |
| **Akar** | Email dikirim sebelum verifikasi didaftarkan; PDF generator tanpa dukungan QR; encoder QR tidak ada |
| **Fix** | (1) Encoder QR murni PHP `QrEncoder::matrix()` + `qr_matrix()` (mode byte, EC M, versi 1–10, mask via penalti) terverifikasi byte-identik dengan `segno`; (2) `StrukPdf::struk(..., ?array $verif)` menambah kotak "VERIFIKASI KEASLIAN DOKUMEN" ber-QR saat `$verif['url']` ada; (3) alur LUNAS diubah urut: bangun PDF → `verif_register_doc()` → kirim email memakai PDF BER-QR (fallback PDF biasa bila verifikasi gagal); link + kode 6-digit ikut ditulis di body email; (4) `struk-pdf.php` juga meneruskan `verif_url`/`verif_code` sehingga PDF unduhan konsisten ber-QR |
| **Verifikasi** | 8 input (versi 1/2/4/7) FULL-MATRIX IDENTICAL terhadap `segno 1.6.6` (finder, timing, alignment, version, format, data = 0 diff; semua 8 mask); QR hasil render (3 panjang, v1/v2/v7) ter-decode `OpenCV` = isi persis; PDF sample ter-parse `pypdf` (1 halaman A4, text "VERIFIKASI KEASLIAN DOKUMEN" + "Kode: 481527"), QR dalam PDF ter-decode OpenCV = `https://verif.almutqon.test/v/6f2c9a1e`; jalur tanpa verif tetap valid tanpa QR; `php -l` bersih |
| **Pelajaran** | Verifikasi kode QR wajib pakai referensi (segno) + decoder (OpenCV); bug "format info" yang menyesatkan ternyata di script pembaca (lupa XOR format mask `0x5412`), bukan di encoder. Urutan kejadian dalam alur kirim harus: siapkan identitas dokumen → baru render → baru kirim |
| **Log Keyword** | qrcode, struk, faktur, email, verif_url, verif_code, segno, QR |
| **Deploy** | BELUM — perubahan di commit lokal belum di-push/di-deploy; kirim email nyata via SMTP Brevo belum ditest (butuh kredensial live) |