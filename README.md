# Almutqon Arabic Learning

Platform pembelajaran bahasa Arab berbasis PHP, HTML, CSS, JavaScript, dan MySQL.

## Kebutuhan sistem

- PHP 8.1 atau lebih baru
- MySQL 8 atau MariaDB yang kompatibel
- Extension PHP: `curl`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, dan `pdo_mysql`
- Apache dengan dukungan `.htaccess`, atau aturan Nginx yang setara

## Menjalankan secara lokal

1. Salin `.env.example` menjadi `.env`.
2. Isi konfigurasi database lokal.
3. Jalankan `php database/migrate.php`.
4. Arahkan document root ke direktori proyek.
5. Buka URL yang diatur melalui `APP_URL`.

Periksa kebutuhan runtime dengan:

```bash
php tools/preflight.php --database
```

Panduan production tersedia di [DEPLOYMENT.md](DEPLOYMENT.md). Catatan historis proyek berada di `docs/internal/` dan bukan bagian dari paket release.

## Struktur penting

- `admin/`, `auth/`, `api/`, `pages/`, `pengajar/`: entry point aplikasi
- `config/`: konfigurasi dan helper runtime
- `database/`: baseline, migrasi, dan runner migrasi
- `storage/`: jurnal, materi, dan backup yang tidak boleh diakses melalui HTTP
- `assets/`: aset publik dan upload gambar publik
- `tests/`: pengujian CLI
- `tools/`: utilitas operator CLI

Jangan commit `.env`, file upload pengguna, backup database, atau release artifact.
