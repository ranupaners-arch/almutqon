# Deploy keamanan & resiliensi integrasi verifikasi (Fix #5/#6 + verif #5/#6)
Status: BERJALAN · Service: almutqon + verif.almutqon · Diperbarui: 2026-09-25 07:20

## Sedang dikerjakan
Batch perbaikan "perbaiki semua" (sesi 2026-09-24) + hasil uji menyeluruh (sesi 2026-09-25).
Kode selesai & teruji penuh lokal; deploy ke live belum dilakukan.

## Status terakhir (terverifikasi)
- **verif.almutqon (DB dev bersih setelah uji; hanya staff asli 5 + dok INV-20260921-1513, 0 sisa artefak UJI)**:
  - Fail-fast `APP_SECRET_KEY` (Bootstrap.php:19-26).
  - `download.php` butuh `&t=<HMAC>` (403 tanpa token); `index.php` bangun token.
  - **Fix #6**: `public/lupa_password.php` & `public/reset_password.php` DITAMBAH — lupa/reset
    sebelumnya Vy jatuh ke landing (entry hilang); kini rute hidup.
  - Uji kamis: API e2e & portal + manage/reset 25/25; portal penuh 23/23; publik 11/11.
- **almutqon**:
  - `config/database.php:45-48` error DB generic + `error_log`; `.env.example`; `verif-client.php`
    HTTPS non-lokal diblokir; `schema-verif.php` +4 kolom (dev sudah, **live belum**);
    `impor-verif.php` status failed + cap 5x; `tests/perbaikan.php` +3 asersi e2e.
  - **`tests/perbaikan.php` (HTTP E2E penuh): 97/97 PASS** dengan `AMQ_ADMIN_PASS='Albathulah1!'`
    (password `Almutqon2026!` di CATATAN.md:14 sudah tak valid).
- Changelog: almutqon Fix #5/#6 & verif Fix #5/#6 sudah ditulis.

## Keputusan penting
- Download publik DILINDUNGI token; link lama tanpa `&t=` → 403 disengaja.
- Tidak ada outbox/queue penuh — cukup status per baris + tool impor.
- Refactor struktur almutqon (Phase 5) DIBATALKAN.

## Langkah berikutnya
1. DB **live** almutqon: `php config/schema-verif.php` (tambah 4 kolom).
2. `.env` **live**: `VERIF_API_URL=https://…`, `VERIF_API_TOKEN` sesuai sisi verif,
   `APP_SECRET_KEY` terisi (fail-fast matikan aplikasi bila kosong); ikut sertakan dengan
   deploy #6: gunakan `.env` baru (dari `.env.example`), jangan salin isi `.env` dev.
3. Sediakan `AMQ_ADMIN_PASS` benar di catatan (CATATAN.md:14 masih menulis `Almutqon2026!` —
   ganti `Albathulah1!` bila dokumen dirawat).

## Jangan lakukan
- Jangan menyalin `.env` dev ke live (SMTP_PASS/CRON_SECRET/VERIF_API_TOKEN asli — untracked).
- Jangan nilai default baru untuk APP_SECRET_KEY; jangan kembali ke `die($e->getMessage())`.
- Jangan hapus kolom/backfill status tanpa meninjau `impor-verif.php`.
- Jangan menambah percobaan login gagal >4 per run di verif (rate-limit 5/5menit/IP).