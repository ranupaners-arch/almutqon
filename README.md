# Al-Mutqon

Platform web untuk lembaga kursus bahasa Arab Al-Mutqon: profil program, pendaftaran, pembayaran, serta portal khusus peserta, pengajar, dan admin.

## Fitur utama

- Profil lembaga & informasi program kursus (offline, online, dauroh)
- Pendaftaran peserta dengan akun (registrasi/login email, login dengan Google OAuth, reset password)
- Portal peserta: status pendaftaran, riwayat pembayaran, unggah/unduh tugas, jurnal & materi, kalender, profil
- Pembayaran kursus dengan struk/kwitansi PDF (generator PDF & QR code native PHP, tanpa library pihak ketiga)
- Donasi manual (transfer bank, e-wallet, QRIS) dengan kode unik per transaksi
- Portal pengajar: absensi, agenda, RPP, tugas, rekap, data SDM & wali santri
- Panel admin: kelola pendaftar, pembayaran, donasi, jurnal, agenda, pengajar, dan artikel
- Artikel/blog informasi lembaga
- Notifikasi WhatsApp (pendaftaran, status lunas, reminder via cron) dan email (SMTP)
- Integrasi verifikasi dokumen via API internal verif.almutqon

## Tech stack

- PHP 8.1+ (native, tanpa framework)
- MySQL 8 / MariaDB yang kompatibel, via PDO
- HTML, CSS, JavaScript vanilla
- Apache dengan `.htaccess` (atau aturan Nginx yang setara)
- Tanpa dependency Composer/npm — PDF, QR code, dan klien SMTP ditulis sendiri

## Menjalankan secara lokal

Kebutuhan sistem: PHP 8.1+, MySQL/MariaDB, extension `curl`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`.

1. Salin `.env.example` menjadi `.env`.
2. Isi konfigurasi database lokal (dan fitur lain seperti SMTP, Google OAuth, WhatsApp, donasi sesuai kebutuhan).
3. Jalankan `php database/migrate.php`.
4. Arahkan document root ke direktori proyek.
5. Buka URL yang diatur melalui `APP_URL`.

Periksa kebutuhan runtime dengan:

```bash
php tools/preflight.php --database
```

## Struktur folder

- `admin/`, `auth/`, `api/`, `pages/`, `pengajar/`: entry point aplikasi (panel admin, autentikasi, endpoint API, halaman publik/peserta, portal pengajar)
- `components/`, `layouts/`: komponen dan layout tampilan yang dipakai bersama
- `config/`: konfigurasi dan helper runtime (database, mail, WhatsApp, Google OAuth, pembayaran, donasi, PDF, QR code, dll.)
- `database/`: baseline schema, migrasi, dan runner migrasi
- `css/`, `js/`, `assets/`: aset front-end dan upload gambar publik
- `storage/`: jurnal, materi, dan backup yang tidak boleh diakses melalui HTTP
- `tests/`: pengujian CLI
- `tools/`: utilitas operator CLI (preflight, backup, build release, sinkronisasi data)

## Deploy/rilis

Tidak ada pipeline CI/CD otomatis. Paket rilis dibuat manual dengan:

```bash
php tools/build-release.php
```

Perintah ini menghasilkan direktori timestamp di `release/` (tanpa `.env`, tes, dokumentasi internal, tool operator, dan file upload lokal), yang kemudian diunggah manual ke document root server. Panduan lengkap production tersedia di [DEPLOYMENT.md](DEPLOYMENT.md).

## Kredit

Dibuat oleh **Ranupane Rimba Argana**, founder [Kukerjain](https://kukerjain.dev) — lihat profil di [ranupane.kukerjain.dev](https://ranupane.kukerjain.dev).
