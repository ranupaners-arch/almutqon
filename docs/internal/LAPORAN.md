# LAPORAN — Analisis Pemisahan Planning / Frontend / Backend

Project: Almutqon Arabic Learning
Tanggal: 2026-09-20
Status: **Dokumen analisis murni. Tidak ada perubahan kode.**

---

## 1. Kesimpulan Singkat

Project `almutqon` adalah **monolith PHP vanilla (tanpa framework)** dengan
document root = root project (`/Users/mac/Herd/almutqon`). Kode **frontend dan
backend saat ini tercampur dalam satu file** di sebagian besar halaman
(`pages/`, `admin/`, `auth/`, `pengajar/`): file yang sama berisi markup HTML,
CSS, JavaScript, sekaligus logika server (query DB, validasi POST, CSRF,
auth). Yang sudah terpisah hanyalah **asset murni** (`css/`, `js/`, `assets/`)
dan **logika backend murni** (`config/`, `api/`).

Tidak ada folder khusus "planning" — dokumen perencanaan tersebar di
`CATATAN.md` (~87 KB), `AGENTS.md`, `.opencode/prompts/`, dan `opencode.json`.

---

## 2. Arsitektur Saat Ini

| Aspek | Nilai |
|---|---|
| Bahasa | PHP (vanilla), HTML, CSS, JS vanilla, MySQL |
| Framework | Tidak ada (larangan eksplisit di AGENTS.md) |
| Docroot | Root project — URL langsung (`/`, `/pages/...`, `/css/...`) |
| Path include | `require_once __DIR__ . '/../config/...'` (relatif) |
| URL di HTML/JS | Absolut root (`/assets/...`, `/api/...`) |
| Sesi | Satu sesi PHP: `$_SESSION['user']` + flag `$_SESSION['admin']` |
| Kontrak frontend–backend | `js/app.js` ⇄ `api/*.php` via `fetch()` JSON |
| Lingkungan | Laravel Herd (`http://almutqon.test`), DB `db_almutqon-website` |
| Situs live | `almutqon-verif.infinityfreeapp.com` (perbaikan lokal tidak otomatis live) |

---

## 3. Pemetaan Per Area

Jumlah file = file `.php/.css/.js` (non-node_modules); `assets/` dihitung manual.

| Folder | Jml | Kategori | Isi |
|---|---|---|---|
| `config/` | 28 | **Backend murni** | auth, csrf, database, donasi, google, mail, pdf, pembayaran, pendaftar-admin-actions, pengajar, sanitize, verif-client, whatsapp + 15 `schema-*.php` |
| `api/` | 7 | **Backend murni** | cron-wa-reminder, donasi, pendaftar, theme, unduh-jurnal, unduh-materi, upload-jurnal |
| `tools/` | 2 | Dev/migrasi | impor-verif, padankan-staf |
| `tests/` | 3 | Pengujian | perbaikan (465 baris), seed-dummy, test-mail |
| `css/` | 6 | **Frontend murni** | base, layout, components, pages, admin, theme |
| `js/` | 3 | **Frontend murni** | app (16 KB), ui (41 KB), alfarahidi (6 KB) |
| `assets/` | ~21 | **Frontend/Dokumen** | 1.png, 2.png, QRIS.png, favicon, avatars/, jurnal/(1 docx), materi/(5 pdf), pay/(8 logo), uploads/(2 png) |
| `index.php` | 1 | **Campuran** | homepage: markup + require config/database + include layouts |
| `layouts/` | 11 | **Campuran** | header/footer/admin/akun/pengajar (markup + sesi + guard), modals, price-section, theme-switch |
| `components/` | 4 | **Campuran** | card-artikel, card-kurikulum, card-program, card-testimoni |
| `pages/` | 21 | **Campuran** | akun(9), artikel(2), bayar(3), info(3), program(3), tentang(1) |
| `admin/` | 13 | **Campuran** | CRUD data + ekspor + login/logout + ganti password |
| `auth/` | 9 | **Campuran** | login, register, lupa/reset password, Google OAuth, logout |
| `pengajar/` | 9 | **Campuran** | absensi, agenda, materi, rekap, rpp, sdm, tugas, wali, index |
| `AGENTS.md` | – | Dokumen/dev | pedoman kerja agent |
| `CATATAN.md` | – | **Planning (de facto)** | perkembangan, bug, & pekerjaan berikutnya (~87 KB) |
| `.opencode/prompts/` | 3 | Dokumen/dev | prompt agent Playwright (generator, healer, planner) |
| `opencode.json` | – | Konfigurasi dev | MCP Playwright + definisi subagent |
| `.env`, `.gitignore` | – | Konfigurasi | kredensial DB (jangan di-commit) |

---

## 4. Titik Kopling Frontend ↔ Backend (fakta terukur)

1. **51 file PHP** di `pages/`+`admin/`+`pengajar/`+`auth/` langsung
   `require config/*` — tidak ada lapisan pemisah/service layer.
2. **8 halaman view berisi logika server** (query DB / POST / validasi /
   upload) di file yang sama dengan HTML, contoh paling jelas
   `pages/akun/profil.php:19-45` (validasi POST + CSRF + upload avatar).
   Lainnya: `pages/akun/ganti-password.php`, `pages/akun/riwayat-tugas.php`,
   `pages/artikel/{index,detail}.php`, `pages/bayar/{struk-pdf,struk,tunggu}.php`.
3. **Kontrak JSON tersirat** antara `js/app.js` dan `api/*.php`:
   fetch(`/api/pendaftar.php`, `/api/donasi.php`, `/auth/logout.php`,
   `/auth/logout-pengajar.php`). Shape response tidak didokumentasikan →
   change di API harus selalu disetujui frontend.
4. **Sesi & guard dipakai bersama** — `is_admin()` / `require_admin()`
   (`config/auth.php`) dijalankan dari halaman admin/pengajar/auth yang
   sebenarnya bagian frontend.
5. **`assets/` bukan murni statis** — dipakai backend untuk *upload*
   (`assets/avatars/`, `assets/jurnal/`, `assets/materi/`, `assets/uploads/`).
6. **Unauthenticated guard tercecer di view** — contoh `pages/akun/*.php`
   memanggil `header('Location: /')` sendiri, bukan lewat helper.
7. File backend terbesar yang difaktakan: `config/mail.php` (253 baris),
   halaman terbesar: `pages/bayar/tunggu.php` (347 baris),
   `layouts/modals.php` (407 baris) — belum terpecah meski campur dua fungsi.

---

## 5. Opsi Pemisahan & Perbandingan

### Opsi A — Ringan: hanya dokumentasi & dev dipisah
Pindahkan dokumen/dev tanpa menyentuh kode produksi:

```
docs/          ← CATATAN.md, AGENTS.md, catatan planning baru
dev/           ← tests/, tools/, .opencode/, opencode.json
```

- **Upaya:** sangat kecil. **Risiko:** hampir nol.
- **URL/deploy:** tidak terpengaruh.
- **Catatan:** tidak memisahkan frontend/backend secara struktural.

### Opsi B — Sedang: `public/` + `backend/` dalam satu repo
Pindahkan docroot ke `public/`:

```
public/        ← index.php, pages/, admin/, auth/, pengajar/, layouts/,
                 components/, css/, js/, assets/
backend/       ← config/, api/
docs/          ← dokumen planning
dev/           ← tests/, tools/
```

- **Upaya:** sedang–besar. **Risiko:** tinggi — semua `require __DIR__/..`
  (~169 kemunculan), URL absolut `/...`, path upload, dan docroot Herd +
  hosting infinityfree harus diperbaiki. Berisiko merusak situs live.
- Perbaiki dulu jalur sebelum mulai: `__DIR__` jadi berubah level.

### Opsi C — Berat: frontend & backend sebagai project terpisah
Frontend (SPA/static) terpisah dari backend (API murni).

- **Upaya:** sangat besar (tulis ulang seluruh lapisan).
- **Konflik:** melanggar AGENTS.md ("jangan migrasi ke React/framework")
  tanpa izin eksplisit dari user.
- Hanya layak bila product direction benar-benar pindah.

### Ringkasan perbandingan

| Opsi | Upaya | Risiko | Live site | Framework baru | Sesuai AGENTS.md |
|---|---|---|---|---|---|
| A (ringan) | Kecil | Nol | Tidak terganggu | Tidak | Ya |
| B (sedang) | Sedang–besar | Tinggi | Berisiko | Tidak | Ya |
| C (berat) | Sangat besar | Sangat tinggi | Rewrite | Ya | Tidak |

---

## 6. Rekomendasi

Karena permintaan saat ini bersifat **informatif** ("hanya ingin tau"):

- **Tidak ada pemindahan kode** sekarang.
- Laporan ini menjadi dokumentasi pemetaan untuk referensi.
- Bila pemisahan nyata diinginkan, jalur paling aman = **Opsi B bertahap**
  dengan dua langkah terpisah agar setiap perubahan bisa diverifikasi:
  1. Pindahkan dokumen/dev tanpa menyentuh kode (Opsi A).
  2. Baru pindahkan docroot + perbaiki path, lalu uji `http://almutqon.test`
     dan `tests/perbaikan.php` sebelum deploy.
- Hindari Opsi C tanpa keputusan eksplisit dari pemilik project.

---

## 7. Status Git & Catatan

- Repo: 1 commit (`479b3a1 Initial commit`), branch `main`.
- **Belum di-commit** (batch saat ini):
  - Modified: `admin/login.php`, `auth/login-pengajar.php`,
    `layouts/admin-header.php`, `layouts/akun-header.php`,
    `layouts/header.php`, `layouts/pengajar-header.php`
  - Untracked: `assets/favicon.ico`, `assets/favicon.png`
- File `LAPORAN.md` ini baru dibuat dan ikut untracked.

---

## Lampiran — Peta File Lengkap (Non-Processing)


### config/ — Backend murni (28)
`auth.php` `csrf.php` `database.php` `donasi.php` `google.php` `mail.php`
`pdf.php` `pembayaran.php` `pendaftar-admin-actions.php` `pengajar.php`
`sanitize.php` `verif-client.php` `whatsapp.php`
`schema-absensi.php` `schema-agenda.php` `schema-donasi.php` `schema-google.php`
`schema-jurnal.php` `schema-password_resets.php` `schema-pembayaran.php`
`schema-pendaftar.php` `schema-pengajar.php` `schema-tautan-staf.php`
`schema-theme.php` `schema-tugas-nilai.php` `schema-verif.php`
`schema-wa-reminder.php` `schema-wali.php`

### api/ — Backend murni (7)
`cron-wa-reminder.php` `donasi.php` `pendaftar.php` `theme.php`
`unduh-jurnal.php` `unduh-materi.php` `upload-jurnal.php`

### frontend murni
- **css/** (6): `admin.css` `base.css` `components.css` `layout.css`
  `pages.css` `theme.css`
- **js/** (3): `alfarahidi.js` (6 KB, pengisi angka statistik) `app.js` (16 KB) `ui.js` (41 KB)
- **assets/**: `1.png` `2.png` `QRIS.png` `favicon.ico` `favicon.png`,
  `avatars/`, `jurnal/` (1 docx), `materi/` (5 pdf), `pay/` (8 logo bank/e-wallet),
  `uploads/` (2 png artikel)

### layout/component — Campuran (view + koneksi backend)
- **layouts/** (11): `admin-footer.php` `admin-header.php` `akun-footer.php`
  `akun-header.php` `footer.php` `header.php` `modals.php`
  `pengajar-footer.php` `pengajar-header.php` `price-section.php` `theme-switch.php`
- **components/** (4): `card-artikel.php` `card-kurikulum.php` `card-program.php`
  `card-testimoni.php`

### halaman — Campuran (view + logika server)
- **pages/akun/** (9): `ganti-password.php` `jurnal-materi.php` `kalender.php`
  `materi.php` `pendaftaran-saya.php` `pengaturan.php` `profil.php`
  `riwayat-tugas.php` `unggah-tugas.php`
- **pages/artikel/** (2): `detail.php` `index.php`
- **pages/bayar/** (3): `struk-pdf.php` `struk.php` `tunggu.php`
- **pages/info/** (3): `faq.php` `kebijakan-privasi.php` `syarat-ketentuan.php`
- **pages/program/** (3): `dauroh.php` `offline.php` `online.php`
- **pages/tentang/** (1): `index.php`
- **admin/** (13): `daftar-artikel.php` `data-agenda.php` `data-donasi.php`
  `data-jurnal.php` `data-pembayaran.php` `data-pendaftar.php` `data-pengajar.php`
  `export-donasi.php` `export-pembayaran.php` `ganti-password.php` `login.php`
  `logout.php` `tambah-artikel.php`
- **auth/** (9): `google-callback.php` `google-login.php` `login-pengajar.php`
  `login.php` `logout-pengajar.php` `logout.php` `lupa-password.php`
  `register.php` `reset-password.php`
- **pengajar/** (9): `absensi.php` `agenda.php` `index.php` `materi.php`
  `rekap.php` `rpp.php` `sdm.php` `tugas.php` `wali.php`
- **root** (1): `index.php`

### Dev / Dokumen / Planning
- `tests/` (3): `perbaikan.php` `seed-dummy.php` `test-mail.php`
- `tools/` (2): `impor-verif.php` `padankan-staf.php`
- Dokumen: `AGENTS.md` `CATATAN.md` `LAPORAN.md`
- Config IDE: `opencode.json`, `.opencode/prompts/` (3), `.opencode/package.json`
- Konfigurasi rahasia: `.env` (jangan di-commit), `.gitignore`

---

## Lampiran 2 — Detail Warna & Tema

Semua warna memakai CSS custom property di `css/base.css`. Ada dua set
variabel: light (`:root`) dan dark (`:root[data-theme='dark']`), sehingga
situs mendukung tiga mode: Terang, Gelap, dan Ikuti Sistem.

### Palet warna utama (light)

| Variabel | Nilai | Kegunaan |
|---|---|---|
| `--forest` / `--primary` | `#166534` | Warna utama (hijau hutan) |
| `--forest-dark` / `--primary-dark` | `#14532D` | Aksen hover/border gelap |
| `--forest-light` / `--primary-light` | `#DCFCE7` | Latar lembut berdasar hijau |
| `--forest-rgb` | `22, 101, 52` | Untuk `rgba()` bayangan |
| `--accent` | `#F59E0B` | Aksen kuning/amber (highlight) |
| `--accent-light` | `#FEF3C7` | Latar lembut aksen |
| `--background` | `#FFFFFF` | Latar halaman |
| `--surface` | `#F8FAFC` | Latar bagian |
| `--card` | `#FFFFFF` | Latar kartu |
| `--text-dark` | `#0F172A` | Teks heading/utama |
| `--text-body` | `#475569` | Teks paragraf |
| `--border` | `#E2E8F0` | Garis pembatas |
| `--muted` | `#94A3B8` | Teks samar |
| `--footer-bg` | `#0B1120` | Latar footer gelap |

### Palet warna gelap (dark override)

| Variabel | Nilai |
|---|---|
| `--forest` / `--primary` | `#4ADE80` |
| `--forest-dark` | `#22C55E` |
| `--forest-light` | `#14532D` |
| `--forest-rgb` | `74, 222, 128` |
| `--accent` | `#FBBF24` |
| `--accent-light` | `#78350F` |
| `--background` | `#0B1120` |
| `--surface` | `#111C2F` |
| `--card` | `#1E293B` |
| `--text-dark` | `#F1F5F9` |
| `--text-body` | `#CBD5E1` |
| `--border` | `#334155` |
| `--footer-bg` | `#060B16` |

### Bayangan (shadow)

Ada 4 tingkatan di light, 4 di dark, plus `--shadow-primary` (berdasar
`--forest-rgb`) yang dipakai pada elemen penting seperti tombol CTA.

### Radius (sudut)

`--radius-sm` 8px, `--radius-md` 12px, `--radius-lg` 16px, `--radius-xl`
20px, `--radius-2xl` 24px, `--radius-full` 9999px (pill).

### Transisi

`--transition` `.3s cubic-bezier(.4,0,.2,1)` dan `--transition-fast`
`.2s cubic-bezier(.4,0,.2,1)`.

### Tipografi

| Variabel | Font |
|---|---|
| `--font-heading` | `Plus Jakarta Sans`, system-ui |
| `--font-body` | `Inter`, system-ui |
| `--font-arabic` | `Noto Naskh Arabic`, `Traditional Arabic`, serif |

Container halaman lebar `1240px` (`--container`).

### Warna khusus area admin (`css/admin.css`)

| Variabel | Nilai |
|---|---|
| `--admin-bg` | `#F1F5F9` |
| `--admin-surface` | `#FFFFFF` |
| `--admin-sidebar` | `#0B1120` |
| `--admin-primary` | `#166534` |
| `--admin-primary-dark` | `#14532D` |
| `--admin-accent` | `#F59E0B` |
| `--admin-info` | `#2563EB` (biru) |
| `--admin-danger` | `#DC2626` (merah) |
| `--admin-radius` | `14px` |

### Mekanisme tema

- **Pilihan tema** disimpan per pengguna di kolom `users.theme`
  (`config/schema-theme.php`): `ENUM('light','dark','system')`.
- **Endpoint simpan**: `POST /api/theme.php` (`api/theme.php`), auth via
  session user + `require_csrf()` (`config/csrf.php`).
- **Penerapan di HTML**: `layouts/header.php` me-`select theme` dari DB
  lalu pasang `data-theme` di `<html>`. Bila 'system', skrip inline dalam
  header langsung ubah atribut via `matchMedia('(prefers-color-scheme: dark)')`.
- **Kontrol UI**: partial `layouts/theme-switch.php` merender segmen
  Terang/Gelap/Sistem (SVG inline, `role="radiogroup"`) dan skrip `fetch`
  ke `/api/theme.php` bila user klik — perilaku JS identik dengan
  `verif.almutqon/app/Pages/_theme_switch.php`.
- **Dark CSS**: `css/base.css:103` memilih selector
  `:root[data-theme='dark'] { ... }` dan `color-scheme: dark` di form
  elements agar form native ikut gelap.
- **Seleksi teks**: `::selection` dalam mode dark memakai `--primary`.
- **CSS load order** (di `<head>` `layouts/header.php`):
  `base.css` → `layout.css` → `components.css` → `pages.css` → `theme.css`
  → `admin.css` (halaman admin). `theme.css` mengatur layout segmented
  control tema.

---

## Lampiran 3 — Fitur Utama Project

Berikut fitur yang terimplementasi di `almutqon` yang belum disebut di
bagian analisis arsitektur di atas.

### Pendaftaran & pembayaran

- **Program**: Offline, Online, Dauroh (halaman `pages/program/*.php`).
  Biaya flat per sesi (kursus) atau per orang (dauroh, min. 40 peserta),
  diatur di `config/pembayaran.php` → `biaya_kursus_config()`.
- **Generator nomor invoice unik** + terbilang Rupiah
  (`buat_no_invoice()`).
- **Alur pembayaran manual** (tanpa gateway): pendaftar memilih bank /
  e-wallet tujuan dari `assets/pay/` + `QRIS.png` (kode QR). Konfirmasi
  LUNAS oleh admin memunculkan struk PDF
  (`pages/bayar/struk.php` → `strukturPDF` di `config/pdf.php`) dan
  halaman tunggu (`pages/bayar/tunggu.php`).
- **Sinkronisasi dengan `verif.almutqon`** via `config/verif-client.php`:
  - `verif_register_doc()` (admin LUNAS) → POST `/api/v1/dokumen.php`
    di almutqon-verif, PDF struk dikirim, token 6-digit disimpan ke
    `pendaftar.verif_token/code/url`.
  - `verif_revoke_doc()` (admin batal LUNAS) → aksi `revoke`.
  - `verif_sync_pengajar()` (ubah pengajar) → POST `/api/v1/staf.php`.
  - Detail di bagian 6 (Laporan Integrasi) pada `/Users/mac/Herd/LAPORAN.md`.

### Donasi

- `config/donasi.php` — daftar bank tujuan (BSI, BRI, KROM, SeaBank) dan
  e-wallet (ShopeePay, dll) — nomor rekening diisi lewat `.env`
  (`DONASI_BANK_*`, `DONASI_EWALLET_*`).
- Endpoint `api/donasi.php` untuk aksi admin (tambah/hapus kanal donasi
  dan lihat rekap) + laporan `admin/data-donasi.php` /
  `admin/export-donasi.php`.

### Artikel & berita

- `pages/artikel/index.php` + `detail.php`, CRUD di `admin/daftar-artikel.php`
  dan `admin/tambah-artikel.php`, unggah gambar artikel ke
  `assets/uploads/`.

### Pengajar & tugas

- Area `pengajar/` (login `auth/login-pengajar.php`, role dari
  `config/pengajar.php`): agenda, absensi, materi, rekap, RPP, SDM,
  tugas, wali murid, dashboard index.
- Pendaftar mengirim tugas: `pages/akun/unggah-tugas.php`
  (baca di `pages/akun/riwayat-tugas.php` + `jurnal-materi.php`).
- Penilaian: schema `config/schema-tugas-nilai.php` (tabel nilai tugas
  per pengajar).

### Jurnal & materi (unduhan)

- Upload jurnal/materi: `api/upload-jurnal.php`, `unduh-materi.php`,
  `unduh-jurnal.php` (akses file di `assets/jurnal/`, `assets/materi/`).
- Halaman pendaftar: `pages/akun/jurnal-materi.php` & `materi.php` —
  list unduhan yang tersedia bagi akun terdaftar.

### Kalender & pengaturan akun

- `pages/akun/kalender.php` — jadwal pertemuan.
- `pages/akun/profil.php` — edit data diri + upload avatar
  (ke `assets/avatars/`).
- `pages/akun/pengaturan.php` — kelola preferensi (termasuk tema
  dark/light/system).
- `pages/akun/ganti-password.php` — alur reset password.

### Integrasi email & WhatsApp

- **SMTP generic** (`config/mail.php`): kirim via `SMTP_HOST/PORT/USER/
  PASS/ENCRYPTION` di `.env`; pengirim default `MAIL_FROM_*`. Dipakai
  alur verifikasi, reset password, dan broadcast notifikasi.
- **WhatsApp** (`config/whatsapp.php`): kirim via HTTP POST provider
  (Meta Cloud API / Wablas / Fonz, sesuaikan payload `send_whatsapp()`
  di file). Kredensial di `.env` (`WA_API_URL`, `WA_API_TOKEN`,
  `WA_ENABLED`).
- **Cron reminder** (`api/cron-wa-reminder.php`): tiap 1 jam via Cron
  server, kirim pengingat batas bayar (status belum/pending, batas
  <3 jam, belum pernah diingatkan) — bisa dipicu HTTP dengan kunci
  `?key=CRON_SECRET` (env `CRON_SECRET`).

### Google OAuth

- `config/google.php` — OAuth2 Authorization Code + PKCE (SHA-256).
  Validasi tanda tangan token RS256 via JWKS endpoint Google
  (fetch `jwks`, cache, verifikasi `openssl_verify`). Detail di
  `auth/google-login.php` / `google-callback.php`.

### Auth & keamanan

- **Sesi** PHP + `csrf` di `config/csrf.php`.
- **Peran**: user, pengajar, admin (`config/auth.php` — `is_admin()`,
  `require_admin()`); direktur di sisi verif.
- **Login Google** + reset password (link token) + `config/sanitize.php`
  (filter input).

### UI & interaksi

- `js/ui.js` (~41 KB) — komposisi komponen UI, modal, tabel, toast.
- `js/app.js` (~16 KB) — fetch endpoint `api/*.php` dan sinkronisasi
  alur pembayaran/donasi.
- `js/alfarahidi.js` (~6 KB) — pengisi angka statistik pada halaman
  tertentu.
- `layouts/modals.php` (407 baris) — kumpulan modal global (konfirmasi,
  detail, form cepat) yang dimuat di semua halaman.
- Dark mode + segmented control tema (`layouts/theme-switch.php`).

### Admin & laporan

- CRUD pendaftar (`admin/data-pendaftar.php`), pengajar, agenda, jurnal,
  pembayaran, donasi + ekspor CSV (`export-pembayaran.php`,
  `export-donasi.php`).
- `admin/ganti-password.php` — ganti kredensial admin.
- Sinkronisasi pengajar ke staff verif (`admin/data-pengajar.php`).

### Testing & tools lokal

- `tests/perbaikan.php` (465 baris) — verifikasi struktur + endpoint +
  unit `verif-client` + PKCE/JWKS.
- `tests/seed-dummy.php` — seed data pendaftar, pengajar, materi,
  artikel.
- `tests/test-mail.php` — uji pengiriman SMTP.
- `tools/impor-verif.php` — impor sekali jalan invoice LUNAS lama ke
  API verif (idempoten, dukung `--perbaiki`).
- `tools/padankan-staf.php` — pairing ID pengajar almutqon ↔ staff
  verif (`--eksekusi` untuk menerapkan).

