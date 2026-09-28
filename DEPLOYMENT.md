# Panduan Deployment

Panduan ini mempertahankan struktur aplikasi PHP saat ini. Contoh aturan server yang disertakan menggunakan Apache. Untuk Nginx, buat aturan penolakan yang setara sebelum go-live.

Contoh minimum Nginx:

```nginx
location ~ ^/(config|database|docs|tests|tools|storage)(/|$) { return 404; }
location ~ /\. { deny all; }
location ~ ^/assets/(jurnal|materi)(/|$) { return 404; }
location ~ ^/assets/.*\.(php|phtml|phar|php[0-9]*)$ { return 404; }
```

## 1. Siapkan environment

Salin `.env.example` menjadi `.env` hanya di server. Nilai minimum production:

```dotenv
APP_ENV=production
APP_URL=https://domain-production.example
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=nama_database
DB_USER=user_aplikasi
DB_PASS=password-kuat
```

Isi SMTP, Google OAuth, WhatsApp, pembayaran, dan verifikasi dokumen sesuai fitur yang digunakan. Jangan menyalin kredensial development ke production.

## 2. Jalankan pemeriksaan awal

```bash
php tools/preflight.php --production
```

Pemeriksaan ini memvalidasi versi PHP, extension, environment, permission storage, HTTPS, debug mode, dan syntax PHP.

## 3. Backup database

Lakukan backup dari panel hosting atau utilitas database sebelum migrasi. Jangan menyimpan backup di document root. Folder `storage/backups/` hanya untuk proses operator dan diblokir dari HTTP.

## 4. Jalankan migrasi

Lihat status tanpa mengubah database:

```bash
php database/migrate.php --status
```

Jalankan migrasi:

```bash
php database/migrate.php
```

Runner mencatat migrasi yang selesai pada tabel `schema_migrations`. Migrasi lama tetap idempoten, tetapi backup wajib tersedia sebelum dijalankan pada database existing.

## 5. Buat paket release

```bash
php tools/build-release.php
```

Perintah menghasilkan direktori timestamp di `release/`. Paket tidak menyertakan `.env`, tes, dokumentasi internal, atau tool operator yang dapat mengubah data. File upload lokal juga tidak ikut agar tidak menimpa data server.

Upload isi direktori release ke document root. Jangan menghapus folder upload atau `storage/` production ketika memperbarui versi.

Jika server lama masih memiliki `assets/jurnal/` atau `assets/materi/`, biarkan file tersebut selama proses transisi. Aplikasi tetap dapat membacanya melalui endpoint terotorisasi, sedangkan `.htaccess` memblokir akses HTTP langsung. Upload baru otomatis masuk ke `storage/`.

## 6. Permission

- Direktori aplikasi: `0755`
- File aplikasi: `0644`
- `storage/jurnal`, `storage/materi`, `assets/avatars`, dan `assets/uploads` harus dapat ditulis oleh proses PHP
- Hindari permission `0777`

## 7. Cron WhatsApp

Jalankan melalui CLI setiap jam agar `CRON_SECRET` tidak perlu diletakkan di URL:

```cron
0 * * * * /usr/bin/php /path/almutqon/api/cron-wa-reminder.php >> /path/logs/almutqon-cron.log 2>&1
```

## 8. Pemeriksaan setelah deploy

Jalankan:

```bash
php tools/preflight.php --production --database
```

Kemudian periksa secara manual:

1. Beranda dan halaman informasi memuat tanpa error.
2. Registrasi dan login pengguna bekerja.
3. Login pengajar dan admin bekerja.
4. Form pendaftaran, pembayaran, email, dan WhatsApp memberi respons yang benar.
5. Upload jurnal dan materi berhasil, lalu hanya dapat diunduh oleh akun berhak.
6. Google OAuth memakai redirect URI production.
7. Endpoint verifikasi dokumen memakai HTTPS.
8. `/config/`, `/database/`, `/docs/`, `/tests/`, `/tools/`, `/storage/`, dan `/.env` tidak dapat diakses melalui browser.
9. Tidak ada error baru pada log PHP atau console browser.

## 9. Rollback

Jika deployment gagal:

1. Aktifkan maintenance page dari panel hosting bila tersedia.
2. Kembalikan file dari release sebelumnya.
3. Restore backup database jika migrasi sudah mengubah schema atau data.
4. Verifikasi login, pendaftaran, dan pembayaran sebelum membuka situs kembali.
