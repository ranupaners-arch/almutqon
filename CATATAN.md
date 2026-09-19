# CATATAN ALMUTQON — Perkembangan, Bug, & Pekerjaan Berikutnya

Dokumen ini untuk melanjutkan sesi kerja. Update setelah tiap perubahan.

---

## 1. LINGKUNGAN & AKSES

| Item | Nilai |
|---|---|
| Lokal | `http://almutqon.test` (Laravel Herd), root `/Users/mac/Herd/almutqon` |
| DB | `127.0.0.1` / `db_almutqon-website` / user `root` / password kosong |
| File DB | `config/database.php` + `.env` (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) — kredensial sudah tidak hardcoded |
| Admin | `admin.almutqon@gmail.com` / `[ADMIN-PASSWORD]` (diganti 15 Sep) |
| Daftar admin | `$ADMIN_EMAILS` di `config/auth.php` (wajib email sudah ada di tabel `users`) |
| Situs live (beda!) | `almutqon-verif.infinityfreeapp.com` — perbaikan lokal TIDAK otomatis ada di sana |

> Sesi PHP **satu** untuk semua tombol login: `$_SESSION['user']` (publik) + flag
> `$_SESSION['admin']`. Semua endpoint memakai `session_start()` yang sama (cookie PHPSESSID).

---

## 2. YANG SUDAH SELESAI

### Autentikasi Admin (P1-1)
- `config/auth.php` → `is_admin()`, `require_admin()`, `update_password()`
- `admin/login.php`, `admin/logout.php`; guard `require_admin()` di `admin/daftar-artikel.php` & `admin/tambah-artikel.php`
- Halaman admin memakai `admin/...` access; button Keluar

### Sanitasi XSS (P1-2)
- `config/sanitize.php` → `sanitize_html()` (whitelist DOMDocument)
- Dipakai di INPUT (`admin/tambah-artikel.php`) & OUTPUT (`pages/artikel/detail.php`)

### Test Regresi
- `tests/perbaikan.php` — sekarang **21 kasus** (sanitizer unit + auth admin + CSRF + XSS e2e via HTTP + helper Google OAuth)
- Jalankan: `AMQ_ADMIN_PASS='[ADMIN-PASSWORD]' php tests/perbaikan.php [baseURL]`

### Ganti Password (admin & user umum)
- `admin/ganti-password.php` dan `pages/akun/ganti-password.php`
- Helper `update_password()` di `config/auth.php`
- Link "Ganti Password" di footer + halaman admin

### Akun & Navbar
- Kolom `users.avatar` (VARCHAR 255, nullable) — sesi login (publik & admin) menyertakan `avatar`
- Dropdown akun di navbar: `#navAccount` (avatar + nama + menu Profil/Ganti Password/Pengaturan/Keluar)
- `pages/akun/profil.php` — ganti nama + upload avatar (JPG/PNG/WebP ≤1MB, preview, hapus file lama; simpan di `assets/avatars/`)
- `pages/akun/pengaturan.php` — info akun + shortcut
- `updateUI()` di `js/app.js` kini menyinkronkan SEMUA tombol login (navbar/hero/mobile) + kartu akun mobile (`#mobileAccount` + `#mobileLogoutBtn`)

### Refactor CSS (14 Sep)
- `css/style.css` (2331 baris) dipecah jadi 4 file: `base.css` (variabel/reset/utilitas/reveal/reduced-motion), `layout.css` (navbar/menu/footer), `components.css` (hero..modal..widget), `pages.css` (halaman dalam/adm).
- Load order di `layouts/header.php`: `base → layout → components → pages`. Cache-buster `20260914b`.
- Pure move — tidak ada deklarasi yang diubah. Terverifikasi pixel-identik (A/B `style.css` vs 4 file) + 15 test regresi lulus.

### Bersihkan Dead Code (14 Sep)
- `js/app.js`: hapus mock `db.users` (localStorage `amq_users`), hapus `openModuleModal`/`closeModuleModal` + binding `[data-close-modules]` (modal `#moduleModal` tidak pernah ada).
- Hapus `partials/footer.php` (tak di-include; folder `partials/` dihapus), `css/style.css` (sisa pra-refactor), `assets/sayang.png` (647KB tak dipakai).
- Cache-buster JS di `layouts/footer.php` → `20260914b`.
- Hapus link mati "Metode" (`#metode`) di footer — section-nya tidak ada. Terverifikasi: seluruh anchor `#...` di homepage kini mengarah ke section yang ada.

### Perbaikan Kritis (13–14 Sep)
- Bug: `#navLoginBtn` TIDAK punya handler klik → login tak bisa dibuka → sudah dibind
- Bug: atribut `hidden` kalah sama `.btn{display:inline-flex}` → ditambahkan `[hidden]{display:none!important}` di CSS base (`css/base.css`)
- Password admin rusak (tidak cocok saat verify) → di-reset ke `[ADMIN-PASSWORD]`
- **Dedup modal**: dulu `#loginModal` 3x per halaman → sekarang **1x** di semua halaman (satu-satunya sumber: `layouts/footer.php` → include `layouts/modals.php`)

### Keamanan P1 — Selesai (14 Sep)
- **P1-1 CSRF**: helper `config/csrf.php` (`csrf_token()`, `csrf_field()`, `csrf_check()`, `require_csrf()`). Dipasang di: `admin/tambah-artikel.php`, `admin/daftar-artikel.php` (form hapus), `admin/ganti-password.php`, `admin/login.php`, `pages/akun/profil.php`, `pages/akun/ganti-password.php`, `auth/login.php`, `auth/register.php`, `layouts/modals.php` (`#authForm` + 3 form program + form donasi), dan `js/app.js` (append `csrf_token` ke FormData auth & form program).
- **P1-2 hapus artikel → POST**: `admin/daftar-artikel.php` pakai `<form>` POST + `require_csrf()` + flash session (PRG), bukan link GET + `confirm()`.
- **P1-3 upload artikel**: `admin/tambah-artikel.php` kini cek MIME via `finfo` (jpeg/png/webp) + ukuran ≤2MB + nama acak `uniqid`.
- **P1-4 env**: kredensial DB dipindah ke `.env` (di-root), dibaca `config/database.php` via `getenv() !== false`.

### Fungsional P2 — Selesai (14 Sep)
- **P2-5 form offline/online/dauroh → DB**: tabel `pendaftar` (skema di `config/schema-pendaftar.php`, jalankan `php config/schema-pendaftar.php`), endpoint `api/pendaftar.php` (POST + CSRF), handler `submitPendaftar()` di `js/app.js` (fetch → alert → reset & tutup modal). Halaman admin baru `admin/data-pendaftar.php` (daftar, link WhatsApp via `wa.me`, tandai "dihubungi" via POST+CSRF). Link dari navigasi `admin/*.php`.
- **P2-6 select Online konsisten**: option `value="Privat"`/`value="Non-Privat"` (sama dengan form Offline).
- **P2-7 tanggal artikel**: INSERT di `admin/tambah-artikel.php` kini mengisi `tanggal = NOW()`.
- **P2-8 artikel beranda dari DB**: `index.php` query `articles` (LIMIT 3, gambarnya fallback Unsplash jika kosong; link ke `detail.php?id=`) — ganti 3 item hardcoded.
- **P2-9 `$isKurikulumPage`**: didefinisikan `false` di navbar & mobile menu `layouts/header.php` → notice PHP hilang.
- **P2-10 label 15 input**: semua field form Offline/Online/Dauroh diberi `id`+`name`+`label for` (juga CSRF hidden field) → terbaca screen reader.
- **P2-11 dot carousel**: `buildPDots()` di `js/ui.js` kini set `aria-label="Lanjut ke program N"`.
- **P2-12 kontras**: teks `PARTNER 01` → `var(--text-body)` tanpa opacity; divider "atau" login → `var(--text-body)`; tombol disabled alfarahidi → `#475569` di `#F8FAFC` (tanpa opacity .4). Semua naik ~2.45 → **≥7.2:1** (WCAG AA terpenuhi).

### Test Regresi (14 Sep)
- `tests/perbaikan.php` — **21 kasus lulus / 0 gagal** (sanitizer + auth admin + CSRF admin + XSS e2e + helper Google OAuth).
- E2E Playwright: login modal (CSRF) → isi form offline/online/dauroh → tersimpan 3 baris di `pendaftar`; admin `data-pendaftar.php` menampilkan & menandai; 0 JS error; 0 PHP warning/notice.

### Sesi & Reset Password P3 — Selesai (14 Sep)
- **P3-1 logout konsisten**: kedua logout (`auth/logout.php` & `admin/logout.php`) kini memakai helper `destroy_session()` di `config/auth.php` — membersihkan seluruh sesi (user + admin + CSRF) & hapus cookie.
- **P3-2 `window.currentUser` sinkron**: `js/app.js` kini set `window.currentUser = data.user` setelah login via fetch (tidak perlu reload).
- **P3-3 lupa password → flow lengkap**: tabel `password_resets` (skema `config/schema-password_resets.php`), helper `store_reset_token()`/`find_valid_reset()`/`mark_reset_used()` di `config/auth.php`. Halaman `auth/lupa-password.php` (minta email → token 30 menit → kirim `mail()`; di lokal tanpa SMTP menampilkan link dev) & `auth/reset-password.php` (buat password baru, sekali pakai, auto-login, hapus flag admin). Token acak `bin2hex(random_bytes(32))`. Link "Lupa password?" → `/auth/lupa-password.php`.
- **"Ingat saya" berfungsi**: `config/auth.php` `remember_me()` memperpanjang cookie sesi 30 hari saat checkbox dicentang (dikirim via `js/app.js`). Google login tetap tampilan (belum ada kredensial OAuth) — dicatat di USULAN.

### Donasi (Opsi B: transfer manual) — Selesai (14 Sep)
- Tabel `donasi` (skema `config/schema-donasi.php`): user_email (kosong jika anonim), nama, whatsapp, jumlah, metode (bank/qris/ewallet), status (pending/diterima).
- `config/donasi.php` + blok `DONASI_*` di `.env` — **sudah diisi** (14 Sep): BSI 7252363372 a.n. Ranupane Rimba Argana; DANA 082258442647 a.n. Ranupane Rimba Argana; QRIS `/assets/QRIS.png`.
- Modal donasi (`layouts/modals.php`): tambah field Nama & WhatsApp + CSRF; setelah submit tampil **STEP instruksi transfer** (tujuan sesuai metode + kode unik 3 digit + bukti via WhatsApp).
- `api/donasi.php`: POST + CSRF, validasi (nama/wa wajib, nominal ≥10rb), simpan, kembalikan instruksi. Handler `#donationSubmit`/`#donationDone` di `js/ui.js` → fetch; `openModal()` reset form.
- Admin `admin/data-donasi.php`: daftar donasi, total pending/diterima, ringkasan, link `wa.me`, tanda **Terima** (POST+CSRF). Link ditambahkan di semua nav admin.
- Donasi **tidak wajib login** (boleh anonim).

### Tema Terang/Gelap per Akun — Selesai (14 Sep)
- Kolom `theme` ENUM('light','dark') default 'light' di `users` (migrasi `config/schema-theme.php`, idempoten berbasis INFORMATION_SCHEMA karena MySQL tak dukung `ADD COLUMN IF NOT EXISTS`).
- Toggle "Tampilan" di `pages/akun/pengaturan.php` (`#themeToggle` + tombol `.theme-btn`); simpan via `api/theme.php` (POST+CSRF, wajib login, validasi whitelist).
- `layouts/header.php` baca tema per user → `<html data-theme="…">`. **Tema HANYA berpengaruh saat login**; pengunjung publik selalu terang; logout otomatis reset `data-theme="light"` di `confirmLogout()` (`js/app.js`).
- CSS: blok `:root[data-theme='dark']` di `base.css` + `css/theme.css`. Semua `background:#fff` di CSS diganti `var(--card)` (tambah variabel baru). Kartu/input halaman akun memakai var `--background/--surface/--card/--text-*`.
- **Adaptasi hero dark**: gradient bg (teal gelap→navy), tombol `.btn-primary` teks dark (kontras 10:1), ring avatar `var(--card)`, blob glow teal/amber — semua di `theme.css`.
- **Ikon share kartu adaptif dark (cache-buster `20260917b`)**: `.article-card__share` dulunya kaca putih `rgba(255,255,255,.95)` di kartu gelap → kini dark glass `rgba(15,23,42,.8)` + border `rgba(148,163,184,.28)`; ikon tetap `var(--primary)` (hijau terang di dark). Hover naik `--card`.
- **Toggle switch (16 Sep, cache-buster `20260917a`)**: dua tombol Terang/Gelap diganti **satu switch** (`#darkModeToggle`, `role="switch"`, default **terang**). `js/app.js` `initThemeSettings()`: init `checked` dari `data-theme`, POST `api/theme.php`, rollback bila gagal, `aria-checked` disinkron. CSS `.switch` di `theme.css` (thumb putih 24px, aktif forest).
- **Catatan**: `config/csrf.php` harus di-`require` di atas konten bila `csrf_token()` dipakai di halaman (footer baru memuatnya).

### Verifikasi Regresi (15 Sep)
- Test `tests/perbaikan.php` awalnya 3 gagal (login admin) karena password admin tidak cocok di DB → ternyata admin sudah diganti manual ke `[ADMIN-PASSWORD]` (15 Sep). Password diresync ke `[ADMIN-PASSWORD]` → **17/17 lulus**.
- (Pola yang sama seperti 13 Sep: setelah admin ganti password manual, pastikan CATATAN.md & DB sinkron.)

### Google OAuth "Masuk dengan Google" — Terpasang (15 Sep)
- Alur **OAuth 2.0 authorization code + PKCE (S256)**, tanpa library. `config/google.php` (helper + verifikator id_token RS256 via JWKS Google, cache 1 jam di temp), `auth/google-login.php` (mulai OAuth), `auth/google-callback.php` (tukar kode → verifikasi → tautkan/buat akun → sesi).
- Kolom `users.google_id` UNIQUE + `users.password` menjadi **nullable** (migrasi `config/schema-google.php`, sudah dijalankan). Akun yang dibuat via Google disimpan tanpa password.
- Tombol di `layouts/modals.php` kini tautan `<a href="/auth/google-login.php">` sungguhan (Format SVG tetap).
- `update_password()` kini mengizinkan akun ber-password NULL mengganti password **tanpa** password lama; halaman `pages/akun/ganti-password.php` hanya menampilkan field "Password Lama" bila akun punya password.
- Kredensial di `.env`: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, opsional `GOOGLE_REDIRECT_URI`. Namun masih kosong — tombol menampilkan halaman "Belum Aktif" sampai diisi.
- redirect URI yang perlu didaftarkan di Google Cloud Console: `http://almutqon.test/auth/google-callback.php` (lokal) / `https://almutqon-verif.infinityfreeapp.com/auth/google-callback.php` (live).
- Catatan: `email` harus tervalidasi Google (`email_verified=true`) untuk bisa masuk; foto profil Google belum disimpan (avatar default huruf awal).

### Pagination & Filter Kategori Artikel (15 Sep)
- `pages/artikel/index.php`: baris filter kategori (pill "Semua" + tiap kategori unik dari DB + jumlah) + pagination (`?kategori=<slug>` & `?page=N`), 6 artikel/halaman.
- Kategori digenerate dinamis dari `DISTINCT kategori` (bebas di admin → dislug-kan lowercase+dash). Slug tak dikenal dianggap "Semua"; `page` non-angka → 1; di luar rentang → di-clamp.
- Link bersih via helper `artikel_filter_url()`; seluruh output `htmlspecialchars`. Pagination memakai `aria-label`/`aria-current`; kontrol disembunyikan bila hanya 1 halaman. Grid tetap struktur sama.
- Diuji manual: page1(6 item)+page2(4 item) saat 10 artikel, filter kabar (2 item + tanpa pagination), slug invalid, `page=abc` → semua OK; artikel uji dibersihkan; **21/21 test regresi lulus**.

### Kurikulum Marquee Auto-Scroll (15 Sep)
- `.kurikulum-track` (sebelumnya scroll-snap horizontal manual) kini **marquee kontinu ke kiri & loop seamless**: `js/ui.js` blok **KURIKULUM MARQUEE** — kartu asli di-klon 1× (total 8, klon `aria-hidden` + tanpa class `.reveal` agar langsung tampil), lalu `requestAnimationFrame`: `offset = total % setWidth` (modulo) → `translateX(-offset)`. Karena 2 set konten identik, saat offset kembali ke 0 terlihat mulus (titik wrap = posisi kartu ke-5 ≈ kartu ke-1).
- Kecepatan default `0.06 px/ms` (~60px/s), bisa disetel via `data-speed` (mis. `data-speed="0.1"`) — dibaca tiap frame.
- Ukuran set (`setWidth`) dihitung ulang **tiap frame** → kebal resize/mobile (`280px`) tanpa perhitungan ulang manual.
- UX/a11y: **pause saat hover/focus** (biar bisa dibaca & diklik), **stop saat section di luar viewport** (IntersectionObserver), dan **`prefers-reduced-motion`** → animasi dimatikan & `overflowX` kembali `auto` (bisa scroll manual). Tanpa JS, `overflow-x:hidden` tetap menahan (kartu `.reveal` memang butuh JS).
- CSS `.kurikulum-track`: `overflow-x: hidden` (ganti auto), `will-change: transform`, hapus `scroll-snap-type`.
- **Viewport wrapper (`20260915e`):** track kini dibungkus `.kurikulum-carousel > .kurikulum__viewport` dengan `overflow: hidden` di CSS (bukan inline JS) — konsisten dengan model `.stories__viewport`/`.programs__viewport`/`.articles__viewport`. `margin-top: 60px` pindah ke `.kurikulum-carousel`; `overflowX` viewport diatur JS (auto saat reduced-motion, hidden normal). JS tidak lagi menyentuh `overflowX` track. Kartu yang lewat batas kiri/kanan section benar-benar terpotong viewport.
- **4 kartu per baris desktop (`20260915d`):** `.kurikulum-card` base `flex: 0 0 calc((100% - 72px)/4)`, tablet `@media (max-width:1024px)` → `calc((100% - 48px)/3)`, mobile `@media (max-width:768px)` tetap `280px`. Gap track `24px` seragam.
- **Overflow halaman horizontal ditemukan & dibuang (`20260915e`):** pelakunya `html/body overflow-x` — bukan `.kurikulum-card` (yang selalu dicrop track). Elemen `.about__content.reveal-right` (`transform: translateX(40px)` saat pra-reveal) mendorong `document.scrollWidth` +20/+24 di lebar <1280. Fix global di `base.css` reset: `html, body { overflow-x: hidden; }`.
- Verified Playwright (`final-verify.js`): **scrollWidth == innerWidth di 390/768/900/1024/1280/1440 (margin 0)**, 4 kartu tampil @1280 (Mubtadi'→Mutafawwiq), kontinuitas tanpa celah (gap 24.0..24.0), kartu lewat batas tersembunyi tanpa push halaman, reduced-motion → viewport `auto` & track diam. Tanpa error JS; **21/21 regresi lulus**. Cache-buster **`20260915e`**.

### Geser Manual Kurikulum (Drag + Flick) & Fix Blocker Widget (`20260915g`)
- **Fitur geser** ditambahkan di blok KURIKULUM MARQUEE `js/ui.js` (dipakai bersama autoplay): **pointer events** pada `.kurikulum-track` — `pointerdown` mulai drag (simpan `startX`/`startOffset` = offset saat ini), `pointermove` geser **1:1 mengikuti jari/mouse** (`total = startOffset + dx`, di-wrap modulo `wrap(v,w)=(v%w+w)%w` sehingga drag melewati 1 set langsung loopy mulus), `pointerup`/`pointercancel` lepas. Saat drag, autoplay dijeda (`dragPause`) & klasi `.is-dragging` (cursor grabbing, animasi kartu tidak terganggu). Setelah lepas, autoplay lanjut dari posisi baru.
- **Flick / momentum**: kecepatan dihitung dari pergerakan terakhir (`v px/ms`, di-haluskan `0.08v+0.92flick`), setelah lepas `total += flickVel*delta` lalu meluruh `*0.9^delta/16` sampai berhenti → terasa "dilempar" natural; `dragPause` benar-benar dilepas sebelum momentum mulai.
- **Klik tetap jalan**: ambang `moved <= 6px` = klik (link di kartu bisa diklik normal); drag jauh menyetel `supClick` → click yang menyusul dilepas **diblokir** (`preventDefault` + `stopImmediatePropagation`) supaya tidak navigasi saat user hanya menggeser. `supClick` di-reset tiap `pointerdown` berikutnya.
- CSS track: `touch-action: pan-y` (pesen horizontal dipakai drag, vertikal tetap scroll halaman), `cursor: grab` / `.is-dragging` `grabbing`, `user-select:none`.
- **Akar masalah "kartu tidak bisa diklik" & swipe tak sampai (temuan besar):** `.alfarahidi-widget` — container transparan seluas panel (di 390px **366×508px**, di desktop **340×508px**) karena panel `visibility:hidden` tetap menempati layout flex → area kosong itu **menelan semua klik/geser konten di belakangnya** (termasuk kartu kurikulum kanan). Fix: `.alfarahidi-widget { pointer-events: none }`; hanya `.alfarahidi-fab` & `.alfarahidi-widget.active .alfarahidi-panel` yang `pointer-events: auto`. Wujud/diri widget tidak berubah.
- Verified Playwright (`drag-final.js`): swipe touch 200px → kartu geser **250px** (incl. momentum); klik ringan di bekas area widget diterima; FAB buka panel, nav next (`ضَرَبَ`→`كَتَبَ`), tutup → semua jalan; reduced-motion normal; tanpa error JS; **21/21 regresi lulus**. Cache-buster **`20260915g`**.

### Carousel Artikel Beranda + CTA "Lihat Semua Artikel" (15 Sep)
- Section Artikel di `index.php` diubah dari grid statis → **carousel geser** model sama dengan testimoni: `.articles__carousel > .articles__viewport > #articlesTrack` + nav `.stories__btn`/dots (3 baris desktop, 2 tablet ≤1024, 1 mobile ≤768, gap 28/16).
- JS `js/ui.js` blok **ARTICLES CAROUSEL**: prev/next + dots (aria-label), swipe sentuh, resize debounce. Tanpa autoplay (isi artikel). Nav otomatis di-`hidden` bila tak perlu geser (mis. 3 kartu di layar besar) → tampilan desktop tetap seperti semula.
- Tombol **"Lihat Semua Artikel"** (`btn-outline`) ditambahkan di bawah carousel → `/pages/artikel/index.php` (sebelumnya halaman list tak punya pintu sama sekali).
- Verifikasi DOM Playwright (_Pixel 5/Desktop/tabel): desktop nav hidden; mobile nav tampil + 3 dot + next geser `translateX`; prev kembali ke 0; tablet 2 dot. Cache-buster **`20260915a`**.

### Warna Hijau → Forest (`20260916g`)
- Skema hijau/teal diganti jadi **`--forest`**: `:root` kini mendefinisikan `--forest:#166534`, `--forest-dark:#14532D`, `--forest-light:#DCFCE7`, `--forest-rgb:22,101,52`; **`--primary*` dipetakan ke `var(--forest*)`** → semua tombol (`.btn-primary`, FAB) & section hijau otomatis ikut forest. Dark mode: `--forest:#4ADE80`, `--primary-light:#14532D`.
- `--shadow-primary` & shadow tombol/FAB di `components.css` pakai `rgba(var(--forest-rgb),…)`.
- Hex teal yang di-hardcode ikut dirapikan: `auth/reset-password.php` & `auth/lupa-password.php` (logo, focus ring, tombol) → forest; avatar-gradien di `pages/akun/profil.php` & `pengaturan.php` → `var(--forest…)`.
- Catatan: ikon dekoratif kurikulum (6 warna, mis. emerald `#10B981`) & brand WhatsApp/X/FB/TG dibiarkan (bukan button/section hijau). Cache-buster CSS → **`20260916g`** (JS `20260916f`). Regresi **21/21 lulus**; verifikasi Playwright: `--primary=#166534`, tombol & FAB `rgb(22,101,52)`.

### Konsistensi Ukuran Kartu Artikel (16 Sep)
- `css/components.css`: judul kartu diberi clamp **2 baris** dan excerpt clamp **3 baris** (`display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp; overflow:hidden`) sehingga semua kartu punya struktur baris identik.
- `.article-card__title` diberi `min-height: calc(1.4em * 2)` — judul 1 baris tetap menyisakan ruang baris ke-2 → posisi excerpt/CTA seragam antar kartu.
- Tinggi disamakan oleh grid/flex (`.articles__grid` = grid stretch, `.articles__track` = flex stretch) + `.article-card__link{height:100%}` + `.article-card__body{flex:1}` + `.link-arrow{margin-top:auto}` (dari fitur sebelumnya).
- Verifikasi Playwright @1440px: **semua kartu 544px, semua gambar 200px**, "Baca Selengkapnya" sama posisinya di semua kartu, tombol share merata (488/894/1301). Cache-buster CSS → **`20260916d`**. Regresi **21/21 lulus**.

### Bagikan Artikel — Icon Share + Menu Opsi (16 Sep)
- Tombol share di **kartu** (index & beranda) diganti dari copy-langsung (`data-copy` + ikon `link`) menjadi **ikon `share`** yang membuka **menu opsi** (`role="menu"`, `aria-haspopup`) berisi: **WhatsApp / X / Facebook / Telegram / Salin tautan**.
- Markup kartu kini `data-share-url` + `data-share-title` (judul artikel dipakai teks share) — di `pages/artikel/index.php` & `components/card-artikel.php`. Tombol tetap di luar `<a>` (HTML valid).
- `js/ui.js` blok **SHARE MENU**: menu dibuat sekali (`getShareMenu()`) lalu diposisikan `fixed` di atas tombol (`positionShareMenu`, flip ke bawah bila tak muat, clamp ke viewport); klik item membangun URL share (wa.me / twitter intent / fb sharer / t.me) lalu `window.open`; *copy* → `copyToClipboard()` + toast; klik luar/Esc/scroll/resize (≥150ms setelah buka, `onShareViewportChange`) menutup menu; `aria-expanded` disinkron.
- `css/pages.css`: `.share-menu` (dropdown card, shadow-lg, radius-lg) + `.share-menu__item` + icon persegi brand-color 26px (`--wa/-x/--fb/--tg/--copy`), X-dark via `:root[data-theme='dark']`.
- Halaman **detail** tetap punya baris Bagikan (WA/FB/X/TG + Salin tautan) dengan `data-copy` (handler `[data-copy]` tetap dipertahankan).
- **Teguran bug potensial**: scroll-focus setelah klik sempat menutup menu instan → dilindungi `shareOpenedAt` + guard 150ms.
- Cache-buster CSS `20260916e`, JS `20260916f`. Regresi **21/21 lulus**. Verifikasi Playwright: menu muncul (items WhatsApp/X/FB/Telegram/Salin tautan), copy → toast + menu tutup, aria-expanded reset.

### Fix Notice PHP di Halaman Akun (16 Sep)
- **Bug**: `Notice: session_start(): Ignoring session_start() because a session is already active (started from config/auth.php on line 5) in layouts/header.php on line 2` tampil di bawah halaman akun (pengaturan, ganti-password, profil) saat sudah login. Akar: halaman akun me-`require config/auth.php` (sudah `session_start()`) lalu include `layouts/header.php` yang memanggil `session_start()` lagi → **double start** (PHP 8.4 emit Notice).
- **Fix**: `layouts/header.php` pakai guard `if (session_status() === PHP_SESSION_NONE) session_start();` — aman dipanggil dari halaman mana pun (header tetap jadi pintu masuk sesi untuk halaman publik).
- **Regresi bertambah jadi 24 kasus** (`tests/perbaikan.php`): blok baru "Halaman Akun: tanpa notice PHP" login admin → fetch `pengaturan.php` / `ganti-password.php` / `profil.php` → assert 200 + tanpa Notice/Warning/Deprecated. **24/24 lulus** (16 Sep).

### SMTP Gmail — Kirim Email Reset Password (16 Sep)
- `config/mail.php`: helper `send_mail()` + klien **SMTP native** (tanpa library) — EHLO, STARTTLS, AUTH LOGIN, MAIL/RCPT/DATA; badan & subjek base64 UTF-8 (aman multibyte Arab + anti header injection via `clean_address()`/`encode_header()`).
- Konfigurasi dari `.env` (blok `# --- SMTP (Gmail App Password)`): `SMTP_HOST=smtp.gmail.com`, `SMTP_PORT=465`, `SMTP_ENCRYPTION=ssl`, `SMTP_USER=admin.almutqon@gmail.com`, `SMTP_PASS=<App Password Gmail 16 karakter>`, `MAIL_FROM_NAME`, `MAIL_FROM=admin.almutqon@gmail.com`, `APP_DEBUG` (detail SMTP ke error_log saat true).
- **Terpasang & teruji nyata**: `php tests/test-mail.php admin.almutqon@gmail.com` → `Mode : smtp` + email masuk ke inbox (16 Sep). Wajib 2FA Gmail aktif + App Password (password login Gmail ditolak `535`). `auth/lupa-password.php` kini memakai `send_mail()`; **fallback dev-link hanya tampil bila email GAGAL terkirim** (bukan selalu seperti dulu), error di-`error_log`.
- Teruji end-to-end via **mock SMTP lokal** (handshake penuh + AUTH + DATA, subjek Arab ter-encode benar) + kirim asli; regresi **21/21 lulus**.

### Breadcrumb Artikel (16 Sep)
- `css/pages.css`: blok **BREADCRUMB** — pill bulat kecil (latar `var(--card)`, border `var(--border)`), separator `›`, link `var(--primary)`, item aktif `var(--text-dark)` + truncated (max-width 260px, ellipsis) supaya judul panjang tidak merusak hero. Dipakai di halaman dalam yang ber-`page-hero`.
- `pages/artikel/index.php`: breadcrumb **Beranda › Artikel** di atas `section-label` dalam `page-hero`.
- `pages/artikel/detail.php`: breadcrumb **Beranda › Artikel › (judul)** dengan `aria-current="page"`.
- Aksesibel: `<nav aria-label="Breadcrumb">` + `<ol>`. Cache-buster CSS → **`20260916a`**. Test regresi **21/21 lulus**.

### Perbaikan Carousel Saat Layar Diperkecil (15 Sep)
- **Bug (programs/testimoni):** tombol prev/next tampak "macet/kepotong" saat window diminimize. Akar masalah: debounce `resize` (150ms) mengukur `getBoundingClientRect().width` kartu **saat transisi flex-basis (0.3s) / track (0.5s) masih berjalan** → offset `translateX` salah dan tidak otomatis dikoreksi sampai user klik lagi.
- **Fix** (`js/ui.js`): helper bersama `measureCard(track)` — tambah class `.no-anim` (CSS `transition:none!important` di `.no-anim, .no-anim *` di `components.css`), paksa reflow (`void host.offsetWidth`), ukur lebar+gap **final**, hapus class. Dipakai di ketiga carousel (testimoni/programs/articles) di `updateCarousel()`/`updatePCarousel()`/`updateACarousel()`.
- **Breakpoint JS disamakan dengan CSS** `max-width:768`/`1024`: kondisi `<768`→`<=768`, `<1024`→`<=1024` (sebelumnya selisih 1px tepat di 768/1024 membuat jumlah dot/disabled salah).
- **Gap programs mobile** kini `16px` di blok `@media (max-width:768px)` (sebelumnya gap 28 sementara kartu `flex:0 0 100%` → tepi kanan kartu terpotong 28px saat digeser di mobile).
- Verified Playwright: setelah 6× resize cepat, `delta offset = 0px` di semua index; tombol prev/next & dots konsisten; kartu aktif selalu `inView` (programs mobile `gap=16`, `inView=true`); clamp saat kembali ke desktop benar. **21/21 regresi lulus**. Cache-buster **`20260915b`**.

### Carousel Program: Nav Tersembunyi di Desktop (16 Sep)
- `.programs__nav` (prev/next + dots) kini `display:none` di `@media (min-width:1025px)` — tepat saat 3 kartu program muat penuh 1 baris (`flex:0 0 calc((100% - 56px)/3)`). Di bawah 1025px tetap tampil: 1 kartu (mobile, 3 dot) / 2 kartu (tablet, 2 dot) + tombol geser aktif; di ≥1025px semua kartu muat → nav tak perlu.
- Terverifikasi Playwright: 375/1024px `flex`, 1025/1280px `none`; klik geser berfungsi di semua ukuran (375 →2 langkah, 768 →2, 1024 →1); 320px overflow ternyata hanya `.mobile-menu` off-canvas (tanpa scrollbar). Cache-buster CSS → **`20260917c`**. Regresi **24/24 lulus**.

### Bersihkan Dead CSS (16 Sep, cache-buster `20260917c`)
- Hapus rule yang **tanpa markup/JS pemakai** (dibuktikan `comm` class CSS vs class HTML/JS): `.program-detail-section`, `.curriculum-box`, `.article-grid`, `.article-card__content`, `.article-card__desc`, `.article-card__footer`, `.logo__icon` (layout.css + media query components.css — logo memakai `<img>`), `.back-to-top` (sisa orphan).
- Dipertahankan karena **dipakai**: `.feature-grid`/`.feature-card*`, `.curriculum-list`, semua `.article-card__*` aktual, `.alfarahidi-*`, `.ql-align-*`, `.share-menu__*`, `.scrolled`. JS audit: semua metode `app.*` dipanggil (init / open*Form via onclick). Tabel tetap rapi.

### Dashboard Admin Profesional (16 Sep)
- **Layout bersama baru**: `layouts/admin-header.php` (sidebar + topbar) & `layouts/admin-footer.php` (JS toggle). Semua halaman admin (kecuali login) kini include layout — tidak lagi self-contained + CSS inline per halaman.
- **Sidebar kiri 1/5 layar desktop**: `flex:0 0 20vw` (clamp 236–300px), background `#0B1120`, brand logo + menu ikon Material Symbols: **Dashboard / Tulis Artikel / Data Pendaftar / Data Donasi / Ganti Password + Lihat Situs + Keluar**; item aktif ditebak hijaunya; kartu user di footer. **Mobile ≤768px**: sidebar jadi off-canvas, tombol **hamburger** di topbar, overlay klik-luar + `Esc` menutup.
- **`css/admin.css` baru** (`?v=20260917d`): sistem komponen admin seragam — `.admin__stat` (kartu statistik), `.admin__card`, `.admin__table` (+ `.admin__table-wrap` scroll-x), `.admin__badge`, `.admin__btn`, `.admin__input`, `.admin__alert`, `.admin__empty`, halaman login (`admin__login`). Menyusul brand forest `#166534`.
- **Dashboard** (`daftar-artikel.php`): kini halaman Dashboard — 4 kartu statistik (Total Artikel, Pendaftar, Donasi Pending, **Rupiah Donasi Diterima**) + tabel artikel (aksi Lihat/Hapus via POST+CSRF). `data-donasi.php` & `data-pendaftar.php`: kartu statistik ringkas + tabel rapi (badge status, link `wa.me`, tombol Terima/Tandai/Hapus). `ganti-password.php` & `tambah-artikel.php`: form dengan class admin (`admin__quill=true` memuat Quill CDN hanya di halaman itu).
- **Flash message dipusatkan**: `config/auth.php` + helper `flash()` / `flash_error()` / `pesan_flash()` (PRG) — menggantikan blok session manual di 5 halaman.
- Login-polish: `admin/login.php` pakai `admin.css` (card gelap gradien, brand logo).
- Verifikasi Playwright: sidebar 1/5 (256px @1280px), hamburger buka/tutup (overlay + Esc) di 375/768px, tabel scroll-x tanpa overflow halaman, Quill toolbar aktif; stat grid 2 kolom mobile. Regresi **24/24 lulus**.

### Fitur Reset ID Artikel + Topbar Bersih (16 Sep, admin.css `20260917e`)
- **Reset ID artikel** (`admin/daftar-artikel.php`): tombol **Reset ID** (POST + CSRF + confirm) di header kartu "Daftar Artikel" + badge **"ID berikutnya: N"** (dari `information_schema.tables` → `AUTO_INCREMENT`). Jalankan `ALTER TABLE articles AUTO_INCREMENT = 1`; **hanya efektif saat tabel kosong** — bila masih ada artikel tampil peringatan (MySQL otomatis naikkan ke `MAX(id)+1` bila ada data).
- **Topbar kanan dibersihkan**: blok `.admin__topbar-user` (avatar huruf + nama admin) dihapus dari `layouts/admin-header.php` → topbar hanya hamburger + judul halaman. CSS `.admin__topbar-user/__avatar/__nama` ikut dihapus dari `css/admin.css` (cache-buster `admin.css` → `20260917e`).
- Verifikasi Playwright: `topbar-user` hilang di 5 halaman, badge & tombol Reset tampil, reset dengan artikel terisi → pesan peringatan benar. Regresi **24/24 lulus**.

### Fitur Pembayaran Kursus + Generate Struk PDF (16 Sep, admin.css `20260917f`)
- **Biaya tetap per program** (`config/pembayaran.php`, sumber: pemilik):
  - Offline **Privat** 318.000/jam × 2 jam = **636.000** · **Non-Privat** 299.000.
  - Online **Privat** 199.000/jam × 1,5 jam = **298.500** · **Non-Privat** 160.000.
  - **Dauroh** 99.000 (Offline) / 70.000 (Online) × jumlah peserta; min peserta **40** (form & API).
- **Migrasi DB** (`config/schema-pembayaran.php`, idempoten): kolom `no_invoice`, `biaya`, `metode_pembayaran`, `status_pembayaran` (`belum/pending/lunas`), `tanggal_bayar` di tabel `pendaftar`.
- **Alur** (per 16 Sep malam, setelah request user): daftar program (offline/online/dauroh) → API `api/pendaftar.php` hitung biaya **tanpa** membuat no. invoice (alert hanya menampilkan nominal + instruksi transfer; invoice & struk dikirim via email setelah admin konfirmasi) → admin konfirmasi **LUNAS** (Pilih metode Bank/QRIS/E-Wallet, POST+CSRF+confirm; ada tombol **Batal** untuk revert) → `tandai_lunas()` membuat invoice `INV-YYYYMMDD-XXXX` + struk PDF terkirim otomatis ke email pendaftar → struk bisa dibuka/dicetak.
- **Struk**:
  - HTML print-ready: `/pages/bayar/struk.php?no=INV-…` (badge LUNAS/BELUM, terbilang, tanda tangan digital; tombol Cetak & Download PDF). Invoice tak dikenal → **404**.
  - **PDF murni PHP tanpa library**: `/pages/bayar/struk-pdf.php?no=…` via `config/pdf.php` (class `StrukPdf`, A4, Helvetica core font/WinAnsi, terbilang Rupiah, tandatangan alm. bagian keuangan). Terverifikasi sah: objek 6/6, xref benar, render teks lengkap, `file` → PDF 1.4.
- **Admin `data-pendaftar.php`**: stat baru **Pembayaran Lunas** & **Terkumpul (Rp)**; kolom Biaya; badge status bayar (Belum/Pending/LUNAS); aksi Lunas (+metode), Batal, link **Struk** & **PDF** (baris lama tanpa invoice → label "tanpa invoice"). CSS baru: `.admin__badge--gray`, `.admin__select-sm`, `.admin__btn--green`, `.admin__stat-icon--blue`.
- Verifikasi e2e (Playwright + uji struktural PDF): alur publik (daftar → alert nominal Rp636.000 tanpa invoice), struk lunas/belum/404, download PDF via HTTP valid, admin Lunas→flash+Batal. Regresi **24/24 lulus**. Data uji dibersihkan.

### Halaman Pembayaran Kursus di Dashboard Admin (16 Sep, admin.css `20260917g`)
- **Menu baru** "Pembayaran Kursus" (`icon=payments`) di sidebar, antara Data Pendaftar & Data Donasi → halaman `admin/data-pembayaran.php` ($admin_aktif `pembayaran`).
- **Isi halaman**: 4 kartu statistik (Lunas / Pending / Belum Bayar / **Terkumpul Rp**), **filter tab** Semua/Belum/Pending/Lunas (kondisi dilanjutkan di URL `?status=…`), tabel: No. Invoice, Nama+Program, Nominal, Metode Bayar, Tanggal Bayar, Status badge, aksi **Konfirmasi Lunas** (pilih Bank/QRIS/E-Wallet, POST+CSRF+confirm), **Struk/PDF**, **Batal**. CSS baru `.admin__tabs/.admin__tab/.admin__tab-count`, cache-buster `admin.css` → `20260917g`.
- **Refactor**: handler POST `dihubungi`/`lunas`/`batal_lunas` dipindah ke file bersama `config/pendaftar-admin-actions.php` (dipakai `data-pendaftar.php` & `data-pembayaran.php`); redirect memakai `REQUEST_URI` sehingga **filter status dipertahankan** setelah aksi.
- Verifikasi Playwright: menu aktif, 4 stat + 4 tab, filter lunas tepat, konfirmasi Lunas dari halaman pembayaran (flash + tetap di tab belum), tanpa overflow @375px. Regresi **24/24 lulus**.

### Request User (16 Sep): Daftar Harga + Info Transfer + Invoice di LUNAS + Struk via Email
- **Daftar harga di halaman program**: partial baru `layouts/price-section.php` (`$program = offline|online|dauroh`), disertakan di `pages/program/{offline,online,dauroh}.php` menggantikan CTA lama → section `#biaya` berisi `.price-tag` (harga per program dari `config/pembayaran.php`), tombol CTA buka modal, dan catatan transfer (rekening BSI + DANA via `pembayaran_info()`). **Catatan**: path include memakai `__DIR__ . '/../config/pembayaran.php'` (bukan `../../`) — `__DIR__` mengacu ke `layouts/`.
- **Harga live di modal**: `layouts/modals.php` kini `require_once config/pembayaran.php`, render blok `.modal__price` (data-price-box `offline|online|dauroh`) + `.modal__note-transfer` di tiap modal, dan mengekspor `window.AMQ` JSON (`biaya` via `biaya_for_js()` + rekening) di akhir file. `js/app.js` punya `initPricing()` (dipanggil dari `init()`) → update `.pb-total`/`.pb-ket` saat ganti metode/jumlah peserta: `#offline_metode`, `#online_metode`, `#dauroh_metode` (change) & `#dauroh_jumlah` (input; min 40). CSS baru `.price-tag*` (pages.css) & `.modal__price/.modal__note-transfer` (components.css).
- **Invoice hanya dibuat saat LUNAS**: `api/pendaftar.php` TIDAK lagi membuat/mengembalikan no_invoice; `tandai_lunas()` di `config/pembayaran.php` MENGHASILKAN `INV-YYYYMMDD-XXXX` saat admin konfirmasi Lunas. Alert submit jadi TANPA invoice (bilang invoice & struk dikirim via email setelah admin konfirmasi).
- **Email struk otomatis saat klik Lunas**: `config/mail.php` `send_mail()` mendukung `$attachment` (PDF, multipart/mixed base64). Handler `lunas` di `config/pendaftar-admin-actions.php`: `tandai_lunas()` → buat PDF `StrukPdf` → kirim ke email pendaftar → flash berhasil/GAGAL kirim (nilai SMTP dari `.env`; jika kirim gagal flow tetap lanjut).
- Verifikasi e2e Playwright: section harga 3 halaman (cards+note), harga live offline 636k/299k, online 298.5k/160k, dauroh Online · 40 → Rp2.800.000 (ubah 50 → 3.5jt, Offline → 4.95jt), transfer note tampil, alert regis tanpa invoice, admin Lunas → flash "Struk dikirim ke email … (Invoice: INV-…)" + baris pindah ke tab lunas + badge LUNAS + struk 200. Regresi **24/24 lulus**; data uji dibersihkan. Cache-buster publik → `20260917d` (css) & `20260917b` (js).

### Request User (17 Sep): Form Bayar = Opsi Metode + Halaman Status Countdown 24 Jam
- **Form pendaftaran (offline/online/dauroh)**: blok instruksi transfer (`.modal__note-transfer`) dihapus; diganti **radio metode bayar** model donasi — `.modal__methods` + label `.method` (Transfer Bank / QRIS / E-Wallet, default **bank**), nama `offline_metode_bayar` / `online_metode_bayar` / `dauroh_metode_bayar` (value `bank|qris|ewallet`). Box **total live** (`.modal__price`) tetap tampil. `window.AMQ` diekspor cukup `{ biaya }`; `$__info = pembayaran_info()` di `modals.php` dihapus (unused).
- **Notifikasi + redirect**: `js/app.js` `submitPendaftar` (fetch `api/pendaftar.php`) pada sukses TIDAK alert invoice lagi (jangan re-introduce alert lama) → tutup modal lalu `window.location.href = data.redirect`. Pesan di halaman status: **"Pengajuan Program Terkirim, Silahkan Menyelesaikan Pembayaran"**.
- **Halaman status countdown 24 jam** — `pages/bayar/tunggu.php?id=…` (baru, standalone, load CSS publik `?v=20260917e`):
  - **Akses hanya pemilik** (`strcasecmp(session user email, user_email row)`) **atau admin** (`is_admin()`); selain itu 404 HTML.
  - Badge status: `MENUNGGU PEMBAYARAN` / `PEMBAYARAN LUNAS` / `BATAS WAKTU HABIS`; countdown (Hari/Jam/Menit/Detik) ke **`batas_bayar`** (kolom baru, fallback `created_at+24j`), reload otomatis saat habis.
  - Box **Total**, **Metode**, **Transfer ke Tujuan Ini** (bank → `[bank_bank, bank_nomor, a.n. nama]`, ewallet → `[tipe, nomor, a.n. nama]`, qris → gambar `qris_gambar` bila ada, else stok "belum tersedia").
  - Aksi: LUNAS → tombol **Lihat Struk** (`struk.php?no=INV-…`) + **Download PDF** (`struk-pdf.php?no=…`); belum → tombol **Konfirmasi ke Admin** (`wa.me/6282258442647`).
- **DB**: kolom `batas_bayar DATETIME NULL AFTER tanggal_bayar` via `config/schema-pembayaran.php` (idempoten). `api/pendaftar.php` simpan `metode_pembayaran` + `batas_bayar = DATE_ADD(NOW(), INTERVAL 24 HOUR)`, respons `{ id, redirect, biaya, ... }`.
- **Admin prefill**: select metode di `data-pembayaran.php` & `data-pendaftar.php` (form Lunas/Batal) kini `selected` sesuai `metode_pembayaran` pendaftar; kolom "Bayar:" di data-pendaftar memakai label rapi (Bank/QRIS/E-Wallet).
- **Jebakan TZ**: MySQL & `date_default_timezone_set('Asia/Jakarta')` harus SAMA — di bootstrap `config/database.php` diselaraskan **WIB**; tanpa ini `strtotime(batas_bayar)` salah geser 7 jam (countdown dan status kadaluarsa jadi meleset).
- Verifikasi e2e Playwright: daftar → modal tampil total + 3 radio (bank default) → pilih QRIS → submit → redirect `tunggu.php?id=…` (judul + countdown + total Rp636.000 + metode QRIS + gambar QRIS tampil) → akses ulang pemilik OK → id milik orang lain **404** → admin Lunas (confirm dialog) → badge LUNAS + link Struk, id terlambat → **BATAS WAKTU HABIS**. Regresi **24/24 lulus**; data uji dibersihkan. Cache-buster publik → **`20260917e` (css) & `20260917c` (js)**; `layouts/price-section.php` teks disesuaikan (info transfer kini di halaman status).

### Request User (17 Sep): Hapus Pendaftar + Kolom Email + Salin Nominal/Rekening
- **Hapus data pendaftar** (hard delete, tanpa relasi FK ke `pendaftar`): action baru `hapus_pendaftar` di `config/pendaftar-admin-actions.php` (POST+CSRF+confirm, flash nama yang dihapus). Tombol **Hapus** (`admin__btn--danger`) ditambahkan di `admin/data-pendaftar.php` & `admin/data-pembayaran.php` (selalu tampil, di luar blok Lunas). Batal Lunas di `data-pendaftar.php` diseragamkan ke `admin__btn--danger-light` (sama spt pembayaran).
- **Kolom Email** di `admin/data-pembayaran.php`: header + `<td>` baru (link `mailto:`, `word-break:break-all`, "—" bila kosong) agar admin mudah mengenali pendaftar.
- **Salin di halaman status** `pages/bayar/tunggu.php`: tombol `.copy` (ikon `content_copy`) pada **nominal** (`data-copy` = digit `biaya`, mis. `636000`) dan tiap baris tujuan transfer (bank/ewallet/`a.n.`). JS inline: `navigator.clipboard` bila secure context, else fallback `textarea`+`execCommand('copy')` (karena situs http), feedback ikon `check` + class `.copied` 1,4 dtk.
- Verifikasi e2e Playwright: kolom Email tampil, tombol copy nominal (data-copy `636000`) & nomor rekening ter-set + class `.copied` muncul saat diklik, hapus baris via kedua halaman berhasil. Regresi **24/24 lulus**; data uji dibersihkan. **Tanpa bump cache-buster** (CSS/JS eksternal tak berubah; style & script inline di `tunggu.php`).
- **Artefak dev dihapus** (permintaan user): `seed.spec.ts`, `specs/` (README + `audit-screenshot*.png`), `.playwright-cli/`, dan `audit-screenshot.png`/`audit-screenshot-after.png` di root.

### Perbaikan Keamanan (17 Sep): Kebocoran Link Reset Password
- **Bug**: `auth/lupa-password.php` menampilkan `$resetUrl` **di layar** saat SMTP gagal (`$dev_link = $mail['ok'] ? '' : $resetUrl`). Siapa pun bisa minta reset untuk email orang lain lalu membaca link reset → **account takeover**.
- **Fix**: fallback tampil-di-layar dihapus. Saat kirim gagal, `$mail['error']` tetap dicatat via `error_log`, dan link reset **hanya** ditulis ke log server bila `APP_DEBUG=true` (`error_log('[reset-link-debug] ' . $resetUrl)`) — tidak pernah dikirim ke browser. Halaman tetap menampilkan pesan netral "Jika email terdaftar, link reset sudah dikirim" (anti user-enumeration). CSS `hr` & `.flash-ok a` yang jadi tak terpakai ikut dihapus. Verifikasi: balasan POST tidak lagi memuat `token=`/`reset-password.php`.

### Perbaikan Keamanan (17 Sep): Session Fixation — `session_regenerate_id()`
- **Bug**: ID sesi tidak pernah diganti saat status autentikasi berubah → **session fixation** (penyerang menyiapkan ID sesi, korban login dengan ID itu, penyerang ikut masuk).
- **Fix**: helper baru `regenerate_session()` di `config/auth.php` (panggil `session_regenerate_id(true)` bila sesi aktif), dipanggil **sebelum** menulis data sesi di: `auth/login.php`, `auth/register.php` (sekaligus menambah `require auth.php`), `admin/login.php`, `auth/google-callback.php`, `auth/reset-password.php`. Di `auth/login.php` regenerasi sebelum `remember_me()`; di `admin/login.php` setelah verifikasi email+password+whitelist admin.
- **Verifikasi**: login & register sekarang mengirim `Set-Cookie: PHPSESSID=<baru>` (ID berbeda dari sebelum autentikasi); regresi `tests/perbaikan.php` **24/24 lulus**; akun uji dihapus.

### Perbaikan Bug (17 Sep): Metode Dauroh Tidak Divalidasi → Biaya Rp0
- **Bug**: validasi `metode` di `api/pendaftar.php` hanya untuk `offline`/`online`. Untuk `dauroh`, nilai sembarang diteruskan ke `hitung_biaya_kursus()` yang memakai `per_orang[$metode] ?? 0` → tersimpan `biaya = 0` + label "Dauroh Bogus".
- **Fix**: `api/pendaftar.php` kini menolak dauroh dengan `metode` di luar `['Offline','Online']`. Sebagai pertahanan berlapis, `hitung_biaya_kursus()` (`config/pembayaran.php`) melempar `InvalidArgumentException` untuk program/metode tak dikenal (semua pemanggil lain memakai nilai tetap, jadi aman).
- **Verifikasi**: `dauroh + metode=Bogus` → ditolak; `dauroh + Offline + 40 peserta` → `biaya 3.960.000` / label "Dauroh Offline"; regresi **24/24 lulus**; data uji dibersihkan.

### Perbaikan Bug (17 Sep): Input Kepanjangan → PDOException Fatal
- **Bug**: nama/email panjang melampaui kolom (`users.name` 100, `users.email` 150; `pendaftar.nama` 120, `whatsapp` 30, `alamat` 255, `instansi` 120) memicu `PDOException: Data too long` yang tak tertangani → respons fatal HTML (bocor path bila `display_errors` aktif) dan JS hanya menampilkan "Terjadi kesalahan".
- **Fix**: validasi panjang `mb_strlen` di server — `auth/register.php` (nama ≤100, email ≤150) & `api/pendaftar.php` (nama ≤120, whatsapp ≤30, alamat ≤255, instansi ≤120, catatan ≤5000) mengembalikan JSON pesan yang jelas; `INSERT` dibungkus `try/catch (PDOException)` → `error_log` + JSON rapi (tanpa fatal). Atribut `maxlength` ditambahkan di form modal (`layouts/modals.php`): auth (100/150), offline & online (120/30/255/5000), dauroh (120/30/120).
- **Verifikasi**: register nama 150 char → "Nama maksimal 100 karakter."; email valid-format 190 char → "Email maksimal 150 karakter."; `api/pendaftar` nama 200 char → "Nama maksimal 120 karakter."; pendaftaran valid tetap sukses; regresi **24/24 lulus**; data uji dibersihkan.

### Fitur (17 Sep): Field Baru Form Pendaftaran Program
- **Kolom DB baru** (`pendaftar`, nullable): `nama_panggilan` varchar(100), `jenis_kelamin` varchar(20), `usia` tinyint unsigned. Migrasi: `ALTER TABLE pendaftar ADD ...` — **wajib dijalankan juga di DB live**.
- **Form** (`layouts/modals.php`): ditambahkan Nama Panggilan (opsional), Jenis Kelamin (dropdown, wajib), Usia (number, wajib, 1-120) untuk **offline, online, dan dauroh**. Opsi gender: offline/online = `Laki-laki`/`Perempuan`; dauroh = `Umum`/`Banin`/`Banat`. Label "Catatan Tambahan" → "Informasi Tambahan" (tetap pakai kolom `catatan`); form dauroh kini juga punya Informasi Tambahan.
- **Validasi** (`api/pendaftar.php`): `jenis_kelamin` wajib & harus sesuai daftar opsi programnya; `usia` wajib 1-120; `nama_panggilan` ≤100; `catatan` (informasi tambahan) ≤5000.
- **Admin**: `admin/data-pendaftar.php` menampilkan Panggilan/Jenis Kelamin/Usia + label "Informasi:"; `admin/data-pembayaran.php` menampilkan baris "Panggilan: … · <gender> · <usia> th".
- **Verifikasi**: T1 offline lengkap → sukses; tanpa gender → "Pilih jenis kelamin (Laki-laki / Perempuan)."; tanpa usia → "Usia harus antara 1-120 tahun."; dauroh + `Laki-laki` → ditolak, + `Banin` → sukses (biaya 3.960.000); isi DB & tampilan admin benar; regresi **24/24 lulus**; baris uji dibersihkan.

### UI (17 Sep): Styling Dropdown & Textarea di Form Program
- Sebelumnya `select`/`textarea` di `.auth__input-group` tak punya style (tampil native & kecil). Ditambahkan di `css/components.css` mengikuti gaya `input`: font 15px, padding `12px 16px`, border radius `var(--radius-md)`, serta panah kustom (`appearance:none` + background SVG), hover & focus konsisten, `option` mengikuti tema, dan textarea `resize:vertical`.
- Cache-buster CSS dinaikkan `20260917e` → **`20260917f`** di `layouts/header.php` & `pages/bayar/tunggu.php`.
- Verifikasi: computed style select (metode & gender) = font 15px, tinggi 49px, ada background-image panah; opsi dauroh Umum/Banin/Banat tampil; regresi **24/24 lulus**.

### Fitur (17 Sep): Opsi Bank + Kode Transfer Antarbank & Opsi E-Wallet
- **Config** `config/donasi.php`: helper `donasi_env()`, `donasi_bank_channels()` (BSI 451, BRI 002, KROM 459, SeaBank 535), `donasi_ewallet_channels()` (ShopeePay/DANA/GoPay), `donasi_resolve_bank()/donasi_resolve_ewallet()`, `donasi_target($metode,$kanal)` (sumber tunggal instruksi transfer + baris "Kode antarbank" + `kanal`), dan `donasi_kanal_label()`. **Nomor rekening BRI/KROM/SeaBank masih DUMMY** (BSI `7252363372`) — ganti via env `DONASI_BANK_<KODE>_NOMOR` / `DONASI_EWALLET_<PROVIDER>_NOMOR` di `.env` (sudah ditambahkan sebagai contoh).
- **DB**: kolom `kanal_pembayaran VARCHAR(30) NULL` di `pendaftar` (after `metode_pembayaran`) & `donasi` (after `metode`) — **wajib dijalankan juga di DB live**: `ALTER TABLE pendaftar ADD COLUMN kanal_pembayaran VARCHAR(30) NULL AFTER metode_pembayaran; ALTER TABLE donasi ADD COLUMN kanal_pembayaran VARCHAR(30) NULL AFTER metode;`
- **UI** (`layouts/modals.php`): closure `$renderPayPanels($prefix)` merender 2 panel per form (`data-pay-group` + `data-pay-panel="bank"/"ewallet"`) berisi **kartu pilihan berikon** (`.paychan`: logo SVG monogram berwarna merek + nama kanal, radio tersembunyi). **Kode antarbank & nomor rekening sengaja TIDAK ditampilkan di form** — hanya di halaman status `tunggu.php` (dan langkah 2 modal donasi). Diterapkan di form **offline, online, dauroh, dan modal donasi**. Toggle di `js/ui.js` (radio metode → tampil/sembunyikan panel + `disabled` semua input panel non-aktif; QRIS = keduanya tersembunyi; ikut `reset` form). `js/ui.js` juga mengirim `bank_tujuan`/`ewallet_tujuan` (dari radio terpilih) saat submit donasi; form program terkirim otomatis via `FormData`.
- **API**: `api/pendaftar.php` & `api/donasi.php` menyimpan `kanal_pembayaran` + memakai `donasi_target()`; `pages/bayar/tunggu.php` (label `Transfer Bank · <kanal>`), `config/pdf.php` (struk), `admin/data-pendaftar.php` & `admin/data-pembayaran.php` (`Bayar: Bank · BSI`) ikut menampilkan kanal. `layouts/price-section.php` teks jadi "Transfer Bank (BSI/BRI/KROM/SeaBank) … E-Wallet (ShopeePay/DANA/GoPay)".
- **Ikon** (`assets/pay/`): `$payLogo` pakai **ikon asli** bila ada file `assets/pay/<slug>.<svg|png|webp>` (slug = `strtolower(kunci)`: `bsi, bri, krom, seabank, shopeepay, dana, gopay`), jika tidak → fallback monogram SVG. Tag `<img>` dapat `?v=filemtime` (auto cache-bust saat file diganti) + `object-fit: contain`. **Ikon asli sudah dipasang (17 Sep)** semua 7 kanal sebagai `.png`; tiap file di-resize ke maks 128px + **auto-crop padding transparan** (canvas alpha-bbox, margin ~10%) agar tidak tampil kecil, total ~61 KB. Asli sebelum diolah ada di `/tmp/amq-pay-originals/` (sementara). Untuk ganti: cukup timpa file dengan nama sama — tak perlu ubah kode/CSS.
- **Verifikasi**: `donasi_target()` unit (bank default/XXX→BSI, BRI, KROM, SEABANK, e-wallet, qris) benar; form **tanpa** teks "Kode"/nomor rekening (regresi teks: 0 kemunculan); e2e Playwright kartu ikon tampil (4 bank + 3 e-wallet, BSI default) + toggle panel (bank→4 input aktif, ewallet→3, qris→0) + submit offline saat e-wallet aktif → `bank_tujuan` (disabled) **tidak** terkirim, `kanal=ShopeePay`; curl simpan `kanal_pembayaran` + render `tunggu.php` (`E-Wallet · ShopeePay` + nomor + `a.n.` + countdown `#countWrap`); uji ikon: `assets/pay/bsi.svg` ada → `<img …bsi.svg?v=…>`, dihapus → fallback monogram; regresi **24/24 lulus**; data uji dibersihkan. Cache-buster: CSS `20260917f` → **`20260917h`**, JS `20260917d` → **`20260917e`**.

### Fitur (17 Sep): Halaman Pendaftaran Saya + Notifikasi Link Status (Email & WA)
- **Halaman** `pages/akun/pendaftaran-saya.php` (baru, wajib login — kalau tidak, redirect `/`): riwayat pengajuan milik user (`WHERE user_email = ? ORDER BY id DESC`) berisi label program, nama, metode+kanal (`donasi_kanal_label`), nominal (`rupiah`), badge status (LUNAS hijau / MENUNGGU + sisa waktu kuning / BATAS WAKTU HABIS merah; sisa dihitung server via `sisa_waktu_teks()`), tombol **Lihat Status & Bayar** → `/pages/bayar/tunggu.php?id=…` + tombol Lihat Struk bila lunas ber-invoice. Empty state → ajakan Lihat Program.
- **Tautan**: dropdown akun desktop (`layouts/header.php`), tombol di menu mobile, dan baris di `pages/akun/pengaturan.php` (ikon `receipt_long`).
- **Notifikasi** (`api/pendaftar.php` + `require config/mail.php`): setelah insert, susun `statusUrl` (http/https + `HTTP_HOST` + `/pages/bayar/tunggu.php?id=…`) & `waUrl` (`wa.me/6282258442647` + teks berisi nama, label program, **ID pengajuan**); kirim email ke `user_email` (subjek "Pengajuan Program Diterima — Selesaikan Pembayaran"; isi: total, metode, batas bayar +24 jam, link status, link WA). Gagal kirim **tidak** menggagalkan pengajuan (try/catch + `error_log('[pendaftar-email] …')`). Respons JSON bertambah `status_url`, `wa_url`, `email_terkirim` (aditif, frontend lama aman). Teks WA di tombol `tunggu.php` ikut ditambah ID pengajuan.
- **Tes** (`tests/perbaikan.php`): halaman baru masuk loop tanpa-notice; tanpa login → 302 `/`; e2e: insert baris milik email admin → login ulang → halaman 200 memuat `tunggu.php?id=…` + "Lihat Status" → tunggu 200 + `data-copy`; baris uji dibersihkan.
- **Verifikasi**: regresi **29/29 lulus** (24 lama + 5 baru); e2e Playwright submit offline sungguhan (id 39, lalu dihapus): `email_terkirim=true` (1 email nyata ke admin), countdown + 4 tombol salin, WA memuat ID, baris muncul di riwayat dengan badge MENUNGGU. Sisa DB: hanya baris asli id 37.

### UI (17 Sep): Tampilan Perayaan Saat Lunas di Halaman Status
- `pages/bayar/tunggu.php` saat `status_pembayaran = lunas`: boks **"Transfer ke Tujuan Ini" disembunyikan total** (`if (!$lunas)`, termasuk gambar QRIS); teks polos diganti blok `.lunas-hero` (`role="status"`): ikon `verified` 84px animasi pop-in + melayang + 2 cincin berdenyut, 16 confetti CSS (`--l/--d/--c/--x`, keyframes `confetti-fall`), judul "Pembayaran Lunas — Selamat Bergabung!" + "Pembayaran Anda Telah Kami Terima. Terima Kasih! 🎉" + paragraf selamat belajar & info dihubungi via WA.
- Konsistensi: label nominal → "Total yang Dibayar", tombol salin nominal disembunyikan saat lunas; boks Metode + tombol Struk/PDF tetap. Kondisi belum/expired tidak berubah. `prefers-reduced-motion` menonaktifkan animasi.
- **Tanpa bump cache-buster** (hanya style inline di `tunggu.php`; CSS/JS eksternal tak berubah).
- **Verifikasi**: curl lunas (tanpa "Transfer ke Tujuan Ini"/div countdown, ada hero + 16 confetti + Struk) & belum-bayar (utuh: countdown + transfer + salin + WA, tanpa hero); Playwright hero terlihat, ikon 84×84, tanpa error JS; regresi **29/29 lulus**; baris uji dibersihkan.

### Fitur (17 Sep): Modul Manajemen File & Jurnal Ilmiah Peserta
- **DB** (`config/schema-jurnal.php`, idempoten via `CREATE TABLE IF NOT EXISTS`): `materi_kursus` (id, program enum offline/online/dauroh + index, judul 255, path_file 255, created_at) & `jurnal_peserta` (id, user_email 190 + index, pendaftar_id INT UNSIGNED + index, judul_jurnal 255, path_file 255, status_review enum pending/acc/revisi default pending, diunggah_pada). **Wajib dijalankan juga di DB live**: `php config/schema-jurnal.php`.
- **Upload** (`api/upload-jurnal.php`, POST klasik + PRG + flash): wajib login + `require_csrf()`; `pendaftar_id` harus milik user & LUNAS (cegah numpang ID); judul ≤255; file PDF/DOC/DOCX (ekstensi + MIME `finfo`) ≤5MB; simpan acak `jurnal_uniqid.ext` di `assets/jurnal/`; metadata ke DB.
- **Unduh aman** (`api/unduh-materi.php`, `api/unduh-jurnal.php`, GET ala `struk-pdf.php`): tanpa link langsung ke folder; materi = pemilik LUNAS program tsb / admin; jurnal = admin (semua) / pemilik; path dikunci via `realpath` + containment; `Content-Disposition: attachment` nama ramah.
- **Peserta** (`pages/akun/jurnal-materi.php`, wajib login): tanpa LUNAS → pesan "Halaman khusus peserta aktif"; bila LUNAS → materi sesuai program (gateway unduh), form unggah (program LUNAS dropdown + judul + file, `csrf_field()`, `enctype`, `accept`), riwayat jurnal + badge status + unduh file sendiri. Tautan: dropdown akun, menu mobile, `pengaturan.php` ("Jurnal & Materi").
- **Admin** (`admin/data-jurnal.php` + sidebar `layouts/admin-header.php` "Jurnal Peserta"): stat Total/Perlu Dinilai/Materi; tabel jurnal JOIN nama peserta + badge status + tombol Unduh (gateway); form tambah materi (judul/program/file PDF-DOC-DOCX ≤10MB, `finfo`, nama acak di `assets/materi/`) + tabel materi dengan unduh & hapus (file ikut terhapus, path dikunci).
- **Tes** (`tests/perbaikan.php`, +8): guest jurnal-materi → 302; upload GET → 302; upload tanpa CSRF → 403; data-jurnal guest → 302 login; unduh jurnal/materi tanpa login → 404; data-jurnal admin → 200 tanpa notice.
- **Verifikasi**: regresi **36/36 lulus**; e2e Playwright (baris LUNAS + materi uji): gate lolos, materi tampil, form CSRF+enctype, unggah PDF nyata → flash + riwayat PENDING, unduh jurnal & materi 200 `application/pdf`, admin melihat JOIN nama + link unduh; guest unduh → 404; semua data & file uji dibersihkan (DB: jurnal=0, materi=0; folder kosong).

### Fitur (17 Sep): Ekspor Data Finansial ke CSV (Admin)
- **Endpoint** `admin/export-pembayaran.php` & `admin/export-donasi.php` (GET, guard `require_admin()` — tamu → 302 login): query LUNAS (`pendaftar`, order `tanggal_bayar` DESC) & DITERIMA (`donasi`, order `created_at` DESC); kolom sesuai spek (invoice/tanggal/nama/program-kanal/biaya; tanggal/nama/WA/metode-kanal/jumlah); nominal `(int)` murni tanpa Rp/titik; tanggal ISO `Y-m-d H:i:s` (mudah disortir); header `Content-Type: text/csv; charset=UTF-8` + `Content-Disposition: attachment; filename="Laporan_…_Ymd.csv"`; BOM UTF-8 + pemisah `;` agar rapi dibuka di Excel Indonesia.
- **Jebakan PHP 8.1+**: `fputcsv()` tanpa argumen `$escape` memicu `Deprecated` yang mengotori isi CSV → selalu panggil `fputcsv($out, $row, ';', '"', '')`.
- **Tombol**: "Unduh CSV" (`.admin__btn .admin__btn--green .admin__btn--sm` + ikon `download`) di baris judul kartu (`admin__card-title`, flex space-between) pada `admin/data-pembayaran.php` → `export-pembayaran.php` & `admin/data-donasi.php` → `export-donasi.php`.
- **Tes** (`tests/perbaikan.php`, +4): kedua endpoint tanpa login → 302; dengan admin → 200, tanpa "Deprecated"/"Rp", memuat baris header.
- **Verifikasi**: isi CSV aktual (1 baris lunas `…3848;…;"Online Privat";"Bank · BRI";298500`, 1 donasi diterima `…;QRIS;-;100000`); BOM `efbbbf`; tombol tampil di kedua halaman; regresi **40/40 lulus**; tanpa perubahan data.

### Fitur (17 Sep): Notifikasi WhatsApp Server-Side
- **Helper** `config/whatsapp.php` (cURL): `wa_config()` (`WA_API_URL`/`WA_API_TOKEN`/`WA_ENABLED` dari `.env`; aktif hanya bila URL+token terisi), `wa_normalize()` (`08…`/`+62…`/`62…` → `628…`, tolak panjang <9/>15 digit), `send_whatsapp($no,$pesan)` → `['ok','skipped','error']`; POST JSON `{"to","message"}` + Bearer (blok payload ditandai untuk disesuaikan ke provider Meta/Wablas/Fonz); timeout 15/10 dtk; 2xx = OK; gagal → `error_log('[wa] …')`. Belum dikonfigurasi → `skipped` aman tanpa log.
- **`.env`**: `WA_API_URL=` + `WA_API_TOKEN=` (kosong), `WA_ENABLED=true`, `CRON_SECRET=<acak, JAGA RAHASIA>`, `APP_URL=http://almutqon.test` (ganti di live).
- **Pendaftaran** (`api/pendaftar.php`): setelah email, kirim WA (sapaan nama, label program, total `rupiah()`, ID pengajuan, batas +24 jam, link status, nomor konfirmasi admin); try/catch + `error_log('[pendaftar-wa] …')`; respons + `wa_terkirim` (aditif).
- **LUNAS** (`config/pendaftar-admin-actions.php`): setelah email struk, kirim WA (LUNAS + invoice + link `struk-pdf.php?no=` + ucapan selamat belajar); gagal kirim tidak membatalkan LUNAS; flash tak diubah.
- **Cron** (`api/cron-wa-reminder.php`, tiap jam: `0 * * * * php …/api/cron-wa-reminder.php >> log 2>&1`): sasaran belum/pending + `batas_bayar` tersisa <3 jam & belum lewat + `wa_reminder_at IS NULL`; pesan berisi sisa waktu, total, link status; flag `wa_reminder_at=NOW()` hanya saat sukses (gagal/skip → dicoba lagi jam berikut). Proteksi HTTP via `?key=CRON_SECRET` (`hash_equals`, 403 bila salah); CLI bebas. Kolom via `config/schema-wa-reminder.php` (idempoten; **jalankan juga di DB live**).
- **Tes** (`tests/perbaikan.php`, +7): unit `wa_normalize` (4 kasus); skipped-path kondisional (hanya bila tak terkonfigurasi — hindari kirim nyata); cron tanpa/salah key → 403.
- **Verifikasi**: regresi **47/47 lulus**; submit curl nyata (`success=true, email=true, wa=false` = skip aman, alur tak rusak); LUNAS via UI admin → flash OK; cron CLI menarget tepat (`0 dari 1`, flag tetap NULL = retry); semua baris uji dibersihkan (sisa id37 asli).

### UI (17 Sep): Portal "Mulai Belajar" Ala Dashboard Admin
- Label peserta "Jurnal & Materi" → **"Mulai Belajar"** (dropdown akun, menu mobile, baris Pengaturan, hero halaman). Nama file/URL (`jurnal-materi.php`, API, tabel DB) dan sisi admin ("Jurnal Peserta") tidak diubah agar tidak merusak link & tes.
- Isi ditulis ulang menyerupai dashboard admin dengan CSS scoped `.belajar-*` (inline di halaman, tanpa memuat `admin.css` global yang merusak layout publik): 4 kartu statistik (Program Aktif, Materi Tersedia, Tugas Diunggah, Disetujui/ACC), kartu tabel **Materi Kursus** (Materi/Program/Tanggal/Unduh), kartu form **Unggah Tugas** (Program/Jenis Tugas/File, CSRF tetap), kartu tabel **Riwayat Tugas Saya** (badge PENDING/ACC/REVISI + unduh file). Responsif: stat 2 kolom di layar kecil.
- **Verifikasi**: render akun LUNAS 200 (stat/card/tabel/form tampil, materi dummy offline terfilter benar); regresi **47/47 lulus**; baris uji dibersihkan.
- **Sidebar** (`layouts/akun-sidebar.php`, partial reusable + dipakai di Mulai Belajar): menu kiri ala dashboard admin berisi 5 halaman akun (Profil/Pendaftaran/Mulai Belajar/Ganti Password/Pengaturan, ikon + status `active`) + "Lihat Situs"; CSS scoped `.belajar-side` (sticky 230px di desktop, bar horizontal scroll di mobile); container halaman dilebarkan 960 → 1140px. Halaman akun lain cukup set `$akun_aktif` + include partial ini untuk memakai sidebar yang sama.
- **Verifikasi sidebar**: desktop sidebar 230px + konten 842px + active "Mulai Belajar"; mobile menumpuk penuh; tanpa error JS; regresi **47/47 lulus**; baris uji dibersihkan.
- **Shell admin penuh** (`layouts/akun-header.php` + `layouts/akun-footer.php`, dipakai KHUSUS `jurnal-materi.php`): sidebar gelap + topbar + konten persis pola `admin-header` (menu 5 halaman akun + Lihat Situs + Keluar via fetch + avatar/email di footer sidebar; guard login → 302 `/`; hamburger mobile). Footer minimal (tanpa app.js — halaman ini tak butuh JS publik). Partial `layouts/akun-sidebar.php` dihapus (digantikan menu shell).
- **`jurnal-materi.php` ditulis ulang** dengan komponen admin: `.admin__stats` (4 stat), `.admin__card` + `.admin__table` (materi & riwayat + badge PENDING/ACC/REVISI), form `.admin__input`/`.admin__form-label`/`.admin__input-file` (CSRF + enctype tetap), `.admin__alert`, `.admin__empty`, tombol `.admin__btn`. Seluruh query & alur upload/gateway tak berubah.
- **Verifikasi**: guest 302; render LUNAS 200 (sidebar/stat/2 tabel/form CSRF/materi dummy, tanpa navbar publik); upload PDF nyata → flash + riwayat; navigasi sidebar ke Pendaftaran Saya; mobile hamburger membuka sidebar + judul "Mulai Belajar"; regresi **47/47 lulus**; data & file uji dibersihkan.
- **Pecah menu belajar** (`materi.php`, `unggah-tugas.php`, `riwayat-tugas.php`; sidebar hanya berisi ketiganya + Kembali ke Beranda): tiap halaman pakai shell akun + stat + 1 kartu fokus (tabel materi / form unggah + CSRF / tabel riwayat + badge); `jurnal-materi.php` jadi redirect 301-semantik ke `materi.php` (tamu tetap 302 `/`); link publik (dropdown, mobile, Pengaturan) → `materi.php`.
- **Verifikasi pecah**: 3 halaman 200 + sidebar + active-state benar per halaman; upload via form baru → 302 → muncul di riwayat; regresi **50/50 lulus** (+3 tes guest); data & file uji dibersihkan.

### Fitur (17 Sep): Kalender Agenda Admin ↔ Dashboard User
- **DB** (`config/schema-agenda.php`, idempoten): `agenda` (id, judul 255, deskripsi text null, tanggal date + index, jam_mulai/jam_selesai time null, lokasi 255, cakupan enum global/offline/online/dauroh default global + index, created_at). **Jalankan juga di DB live.**
- **Admin** (`admin/data-agenda.php` + sidebar "Agenda Kegiatan" ikon `calendar_month`): stat Total/Mendatang; form tambah (judul/tanggal/jam/lokasi/cakupan/keterangan; validasi regex tanggal & jam + panjang + enum, flash_error bila invalid); tabel (tanggal+jam, judul+lokasi+keterangan, badge cakupan, hapus + confirm).
- **User** (`pages/akun/kalender.php`, shell akun, guard login): filter otomatis global + program LUNAS miliknya; tabel **Agenda Terdekat** (10 ke depan + hitung mundur Hari ini/Besok/H-n); **kalender bulan** tanpa library (navigasi ◀ ▶, titik warna per cakupan, hari ini dilingkari hijau, klik tanggal → detail); menu akun +1 "Kalender".
- **Tes** (`tests/perbaikan.php`, +3): kalender guest → 302; data-agenda guest → 302; data-agenda admin → 200 tanpa notice.
- **Verifikasi**: tambah global/offline OK + tanggal invalid ditolak (flash); user hanya melihat cakupannya (global selalu; offline hanya bila LUNAS offline); grid 32 sel; navigasi September→Oktober; klik tanggal → detail; hapus bersih; regresi **53/53 lulus**; data uji dibersihkan. Tanpa notifikasi otomatis (sesuai keputusan).
- **Label admin**: "Jurnal Peserta" → **"Tugas Peserta"** (sidebar, judul halaman, stat "Total Tugas", kolom "Judul Tugas", empty state) selaras istilah sisi user; nama file/URL/tabel/variabel tidak diubah. Regresi **53/53 lulus**.

### Fitur (17 Sep): Data Pengajar (Peran, ID, Revoke, Hapus)
- **DB** (`config/schema-pengajar.php`, idempoten): `users.peran` (enum user/pengajar/koor_mubtadi/koor_mutawassith/koor_mahir/koor_mutafawwiq/direktur, default user), `id_pengajar` VARCHAR(20) NULL + index unik, `aktif` TINYINT default 1. **Jalankan juga di DB live.**
- **Helper** (`config/pengajar.php`): `peran_pengajar_options()` (6 peran + label Indonesia), `peran_label()`, `buat_id_pengajar()` (PGJ-XXXX unik, anti-duplikat).
- **Admin** (`admin/data-pengajar.php` + sidebar "Data Pengajar" ikon `supervisor_account`): stat Tenaga/Aktif/Direktur/Koordinator; form tambah (nama/email/password min 6/peran; email terdaftar → naik peran + aktifkan, ID dibuat bila kosong); tabel (ID, nama/email, badge peran, status Aktif/Dicabut) + aksi **Revoke/Aktifkan** toggle + **Hapus** (confirm; akun sendiri dilindungi di keduanya).
- **Revoke**: `auth/login.php` menolak akun `aktif=0` ("Akun dinonaktifkan. Hubungi admin.", cek toleran via try/catch agar DB lama tetap bisa login); `layouts/akun-header.php` menendang sesi yang sudah dicabut. Login admin panel tidak ikut diblokir (aksesnya diatur `$ADMIN_EMAILS`).
- **Tes** (`tests/perbaikan.php`, +8): 6 peran + label; format PGJ-XXXX; guest data-pengajar → 302; admin 200 + tombol Revoke; siklus penuh (tambah via DB → login OK → revoke POST → login ditolak "dinonaktifkan").
- **Verifikasi**: tambah via UI (PGJ-6830, Koordinator Marhalah Mubtadi'), hapus via UI + flash; regresi **61/61 lulus**; user uji bersih (total users kembali 2).

### Fitur (17 Sep): Dashboard Pengajar (Reguler vs Direktur Penuh)
- **Login** (`auth/login-pengajar.php`, standalone ala login admin): ID Pengajar (PGJ-XXXX) atau email + password; tolak bila bukan pengajar / nonaktif; sesi `$_SESSION['pengajar']` terpisah (regenerate). Helper `config/auth.php`: `is_pengajar()`, `require_pengajar()` (→ login-pengajar), `is_direktur()`, `require_direktur()` (403).
- **Shell** (`layouts/pengajar-header.php` + `pengajar-footer.php`, pola dashboard admin): menu reguler Dashboard/Agenda/Materi/Tugas Masuk; Direktur + menu **SDM & Kinerja**; footer sidebar ID + peran; logout via `auth/logout-pengajar.php` (JSON).
- **Halaman** (`pengajar/`): `index.php` (profil + 4 stat + aksi cepat), `agenda.php` (mendatang read-only), `materi.php` (semua materi + unduh), `tugas.php` (tugas masuk + unduh; ubah status tetap wewenang admin), `sdm.php` (**khusus Direktur**: 5 stat monitoring agregat + tabel kontroling + revoke/aktifkan, tambah/hapus/reset milik admin).
- **Akses file**: `api/unduh-materi.php` & `api/unduh-jurnal.php` dibuka untuk sesi pengajar (selain admin/pemilik).
- **Reset password** (`admin/data-pengajar.php` tombol Reset): acak 8 hex, tampil SEKALI via flash.
- **Tes** (`tests/perbaikan.php`, +3): dashboard/sdm guest (302/403), form login 200.
- **Verifikasi**: reset → flash + login ID berhasil → dashboard 200 (tanpa menu SDM utk pengajar biasa; sdm 403); sebagai direktur sdm 200 + menu; regresi **64/64 lulus**; user uji bersih. Catatan: akun nyata id 1 = Direktur, id 46 = koor_mubtadi (data asli, tak disentuh).

### Fitur (17 Sep): Tugas Terarah ke Pengajar + Nilai + Kirim Materi
- **DB** (`config/schema-tugas-nilai.php`, idempoten; **jalankan juga di DB live**): `jurnal_peserta.pengajar_id` (tujuan, NULL = lama), `dinilai_oleh`/`dinilai_pada` (jejak penilai), `materi_kursus.diunggah_oleh` (jejak pengunggah).
- **User**: form unggah + dropdown **Kirim ke Pengajar** (aktif saja: "Nama (ID · Peran)"); `api/upload-jurnal.php` validasi tujuan + simpan + flash menyebut penerima; redirect sukses pindah ke `unggah-tugas.php`; riwayat tampil kolom **Dikirim ke** (nama + ID) + "Dinilai: …".
- **Pengajar**: `pengajar/materi.php` + form kirim materi (PDF/DOC/DOCX ≤10MB, `finfo`, nama acak, catat pengunggah); `pengajar/tugas.php` hanya menampilkan tugas untuknya (+ tanpa tujuan; direktur semua) + form **Nilai** (Pending/ACC/Revisi + confirm, catat penilai "Nama (ID)" + waktu, otorisasi per tugas).
- **Direktur/Admin**: `sdm.php` + kolom **Tugas Dinilai** per pengajar; `data-jurnal.php` + kolom Untuk Pengajar + "oleh …" + uploader materi.
- **Tes** (+1): nilai tugas tanpa CSRF → 403.
- **Verifikasi**: upload ter-routing (tujuan=53); guru A lihat / guru B tidak; nilai ACC + `dinilai_oleh` tercatat; unggah materi + `diunggah_oleh`; SDM direktur tampil; regresi **65/65 lulus**; data & file uji bersih.

### Fitur (17 Sep): Nilai Huruf Tugas (A+ s.d. E)
- **DB**: `jurnal_peserta.nilai` VARCHAR(5) NULL via `config/schema-tugas-nilai.php` (**jalankan juga di DB live**); opsi `nilai_huruf_options()` di `config/pengajar.php` (A+, A, A-, B+, B, B-, C+, C, D, E).
- **Penilaian** (`pengajar/tugas.php`): dropdown Nilai di samping status (validasi whitelist, `—` = kosongkan); kolom Nilai baru; tersimpan bersama status + penilai.
- **Tampil**: kolom Nilai di admin Tugas Peserta + riwayat user. **Bug tertangkap saat verifikasi**: SELECT riwayat lupa mengambil `nilai` → diperbaiki, nilai tampil.
- **Verifikasi**: nilai A- tersimpan + flash; `Z9` ditolak & nilai tak berubah; tampil di admin + riwayat; regresi **65/65 lulus**; data uji bersih (sisa jurnal id 5 asli).

### Fitur (17 Sep): Hapus Tugas Per Sisi (User & Pengajar)
- **DB**: `jurnal_peserta.hapus_user` + `hapus_pengajar` via `config/schema-tugas-nilai.php` (**jalankan juga di DB live**). Soft delete: sisi lain & admin tetap melihat; file fisik dipertahankan.
- **User** (`riwayat-tugas.php`): tombol Hapus per baris (POST + CSRF + cek milik sendiri) + filter `hapus_user = 0`; flash display ditambahkan (sebelumnya halaman ini tak menampilkan flash).
- **Pengajar** (`tugas.php`): tombol Hapus + filter `hapus_pengajar = 0` (otorisasi sama dengan nilai: direktur semua, lainnya rute sendiri); admin `data-jurnal.php` + penanda "dihapus user/pengajar".
- **Tes** (+2): hapus tanpa CSRF → 403 (user & pengajar).
- **Verifikasi isolasi**: guru hapus → hilang darinya, user tetap lihat; user hapus → hilang darinya, flag 1/1, admin tampil penanda; regresi **74/74 lulus**; data & file uji bersih.

### Fitur (17 Sep): Halaman Info (FAQ/Privasi/S&K) + Absensi Kehadiran
- **Info** (`pages/info/faq.php`, `kebijakan-privasi.php`, `syarat-ketentuan.php`; layout publik + hero, tanpa tabel baru): FAQ akordeon `<details>` (pendaftaran, batas 24 jam, invoice, materi/tugas, kontak admin); privasi & S&K 4 pasal ringkas. Footer: 3 link mati → URL baru; sosmed WA → `wa.me/6282258442647`; FB/YouTube masih `#` + TODO (URL resmi belum ada).
- **DB** (`config/schema-absensi.php`, idempoten; **jalankan juga di DB live**): `absensi` (id, pendaftar_id + index, tanggal + index, status enum hadir/izin/sakit/alpa, dicatat_oleh, created_at, UNIQUE pendaftar+tanggal).
- **Pengajar** (`pengajar/absensi.php` + menu "Absensi" ikon `fact_check`): filter program + tanggal (auto-submit) → peserta LUNAS + radio H/I/S/A (default Hadir, tampilkan isian tersimpan) → simpan upsert sekaligus; validasi ID milik program + status valid + CSRF; catat pencatat "Nama (ID)".
- **User** (`pages/akun/materi.php`): kartu **Kehadiran Saya** per program aktif (sesi tercatat + % hadir; "Belum ada sesi" bila kosong).
- **Tes** (+4): 3 halaman info 200; absensi guest → 302.
- **Verifikasi**: simpan absensi → flash + DB (`hadir`, pencatat `UJI-ABSEN (PGJ-1667)`); stat user `100% hadir`; regresi **69/69 lulus**; data uji bersih.

### Fitur (17 Sep): Perwalian + RPP + Rekap Absensi Hari Kesepakatan
- **DB** (`config/schema-wali.php`, idempoten; **jalankan juga di DB live**): `perwalian` (pengajar_id + index, pendaftar_id NULL + index, program NULL, tipe enum privat/kelompok/dauroh), `jadwal_sepakatan` (pendaftar_id NULL, program NULL, hari CSV Indonesia), `rpp` (pengajar_id + index, materi_id NULL, program + index, judul, tujuan, kegiatan, durasi, tanggal).
- **Wali** (`pengajar/wali.php` + menu "Perwalian" ikon `group`): semua pengajar melihat daftar (tipe badge + target); tunjuk & cabut khusus Direktur (validasi pengajar aktif + target LUNAS/program, cegah duplikat, CSRF).
- **RPP** (`pengajar/rpp.php` + menu "RPP Materi" ikon `edit_note`): tulis (judul/program/materi opsional/tujuan/kegiatan/durasi/tanggal; validasi), daftar + lihat detail, hapus pemilik/Direktur.
- **Rekap** (`pengajar/rekap.php` + menu "Rekap Absensi" ikon `bar_chart`): filter program + rentang; kelola hari sepakat per program (checkbox) & per peserta privat; tabel per peserta (jadwal, sesi, hadir, I/S/A, % + penanda "di luar jadwal").
- **Tes** (+3): wali/rpp/rekap guest → 302.
- **Verifikasi**: direktur tunjuk wali privat + tulis RPP + simpan jadwal; guru biasa lihat daftar (tanpa form tunjuk) + absensi tersimpan; rekap 100% tanpa "di luar jadwal"; regresi **72/72 lulus**; data uji bersih.
- **Sidebar ramping**: menu Profil Saya, Ganti Password, Lihat Situs, Keluar dihapus; tambah **Kembali ke Beranda** (ikon `home`, href `/`); pojok profil menampilkan **tanda program yang diambil** (chip per baris `pendaftar`: label program + warna status LUNAS/Pending/Belum; "Belum ada program" bila kosong). Binding logout di footer dibersihkan (keluar tetap via menu avatar situs).

### UI (17 Sep): Kolom Detail Data Pendaftar Dipecah
- `admin/data-pendaftar.php`: kolom **Detail** dihapus; diganti 4 kolom baru masing-masing: **Panggilan** (`nama_panggilan`), **Jenis Kelamin**, **Usia** (`N thn`), **Metode**. Sel kosong → `—`. Info lama di Detail (invoice/alamat/instansi/peserta/bayar/informasi) tidak lagi tampil di tabel ini (invoice & metode bayar tetap ada di `data-pembayaran.php`).
- **Verifikasi**: render admin 200 (header Detail 0, 4 header baru masing-masing 1); regresi **40/40 lulus**.

### UI (18 Sep): Hapus Link Live Verif dari Footer
- Fokus tes lokal dulu, deploy nanti: link "Verifikasi Dokumen" → `https://almutqon-verif.infinityfreeapp.com/?i=1` dihapus dari `layouts/footer.php` (kolom Bantuan). Komentar `.env:33` (redirect URI live) dibiarkan sebagai dokumentasi deploy.
- **Verifikasi**: homepage 200, 0 kemunculan `infinityfree` di HTML render maupun kode (di luar `.env`/CATATAN).

### Integrasi (18 Sep): Fase 0 Sisi ALMUTQON + API v1 Hidup di Lokal
- `.env` + `VERIF_API_URL=http://verif-almt.test` + `VERIF_API_TOKEN` (hex 64-char, JAGA RAHASIA — sama dengan sisi verif). Belum ada kode klien (Fase 2 nanti).
- API verif `POST http://verif-almt.test/api/v1/dokumen.php` teruji 17 cek (auth, idempoten, revoke/restore, PDF). Siap dipanggil `tandai_lunas()` di Fase 2.

### Integrasi (18 Sep): Fase 2 Klien ALMUTQON — Selesai & Teruji Lokal
- **`config/verif-client.php`** (baru): `verif_config()` (+ `verif_load_env()` cermin `database.php` agar jalan di CLI/cron), `verif_api()` (cURL 15/10 dtk, Bearer, multipart bila ada PDF; gagal → array + `error_log`, tak pernah fatal), `verif_register_doc()` (bangun field struk → simpan `verif_token/code/url`), `verif_revoke_doc()` (rujukan lokal dipertahankan = riwayat jujur).
- **Migrasi** `config/schema-verif.php` (idempoten; **wajib juga di live**): `verif_token CHAR(64)`, `verif_code CHAR(6)`, `verif_url VARCHAR(255)` AFTER `no_invoice`. `SELECT *` di semua halaman ikut otomatis.
- **Hook** `config/pendaftar-admin-actions.php`: Lunas → email (pesan persis semula, kini disusun sekali) + registrasi verifikasi best-effort (PDF dibangun ulang bila email kosong); Batal → revoke remote + flash "ikut dicabut"; aksi baru **`verifikasi_ulang`** (lunas ber-invoice tanpa token → daftarkan + flash kode).
- **Admin `data-pembayaran.php`**: tombol **Verifikasi** (lunas tanpa token) + badge hijau ✓kode (sudah terverifikasi).
- **Tampil**: `struk.php` box "VERIFIKASI KEASLIAN DOKUMEN" (tautan + kode + QR `qr-code-styling` CDN + fallback) + `tunggu.php` LUNAS box `.verif2` + tombol "Verifikasi Dokumen". PDF tahap ini URL+kode teks (QR di PDF = Fase 3).
- **Regresi `tests/perbaikan.php` 84/84** (+10: 3 unit klien incl. API-mati graceful, 7 e2e lunas→struk→batal→revoked). E2E mandiri 15/15. Data uji bersih di kedua DB + file PDF uji terhapus.

### Integrasi (18 Sep): Impor ID Lama → Sinkron (aturan ID diganti nanti)
- **`tools/impor-verif.php`** (baru, idempoten, `--dry-run`/`--limit=N`): daftarkan invoice LUNAS lama yang belum punya `verif_token` ke API v1 (`no_surat = no_invoice`); penandatangan historis tak tercatat → label generik `Admin Almutqon (Bagian Keuangan)`; gagal satu baris tak menghentikan lainnya (exit 1 + daftar gagal).
- **Hasil lokal**: 1 kandidat (`INV-20260917-3848`) → terdaftar (Kode CD9FCF, `active`, PDF tersimpan); 1 baris sudah sinkron sebelumnya; ulangan → 0 kandidat. Struk lama otomatis tampilkan blok verifikasi; halaman publik "Sah & Terverifikasi". Regresi tetap **84/84**.
- **Rencana (nanti)**: ganti aturan/format ID terpadu lintas kedua proyek — belum dirancang, jangan ubah format `INV-…`/`no_surat` sebelum itu.

### Integrasi (18 Sep): Sinkron ID Pengajar/Staf — Selesai & Teruji Lokal
- **Keputusan user**: direktur disamakan NIP-nya; staf baru NIP = `id_pengajar`; sinkron otomatis via API; password awal acak + aktivasi mandiri.
- **Migrasi** `config/schema-tautan-staf.php` (idempoten; **wajib juga di live**): `users.verif_staff_id` BIGINT + index (tanpa FK lintas DB).
- **`peran_to_verif_role()`** di `config/pengajar.php`: direktur→direktur, koor/pengajar→staff.
- **Pairing** `tools/padankan-staf.php` (default dry-run, `--eksekusi` menerapkan): users.id=1 ↔ staff.id=2 (NIP `202501001`→`PGJ-1520`; login lama mati, password tak berubah); staff.id=9 baru NIP `PGJ-2450` untuk koor_mubtadi (role staff, password acak); konflik email-tanpa-NIP dilaporkan & dilewati; `SYSTEM-*`/password/role existing tak disentuh.
- **Hook** `admin/data-pengajar.php` (best-effort + log): tambah/promosi → upsert; toggle → ikut aktif/nonaktif; hapus → pasangan dinonaktifkan + tautan dilepas; reset password tak disinkron. Peran direktur SENGAJA dilewati (manual di dashboard verif). Tabel + badge status sinkron (✓/…/manual).
- **Regresi 94/94** (+10: 3 mapping, 7 e2e hook). Ketemu & diperbaiki: `require` ganda `pengajar.php` di tes (baris lama → `require_once`).
- **Cek manual tersisa untuk owner** (butuh password asli): login portal staff verif pakai `PGJ-1520`; pastikan `202501001` ditolak; reset password Dhirga via `manage_staff` → ia login → ganti sendiri.

### Integrasi (18 Sep): Struk Dikirim sebagai Invoice (tanpa nomor)
- `verif_register_doc()` kini mengirim `'jenis' => 'invoice'` → struk tak lagi memakan nomor urut / total dokumen / rekap di verif. Regresi tetap **94/94**.

### Integrasi: Rencana Fase 3 (belum dieksekusi — JANGAN kerjakan sebelum perintah)
- **Hapus tanda tangan statis yang kosong**: blok `.sign` di `pages/bayar/struk.php` ("Penerima / Orang Tua" + "Bagian Keuangan" hardcoded `Almutqon`) dan `StrukPdf::ttd()` + pemanggilannya di `config/pdf.php`. Alasan: tercetak tanpa makna (kolom kosong, nama hardcoded) dan keaslian sudah dijamin verifikasi digital.
- **Sisakan hanya QR verifikasi** sebagai bukti keaslian: blok verifikasi (tautan + kode + QR) di struk HTML tetap; QR di dalam PDF menyusul (butuh lib QR server-side + dukungan embed gambar di `StrukPdf` yang saat ini teks saja).
- Saat eksekusi nanti: sesuaikan juga `tests/perbaikan.php` bila ada asersi menyentuh blok `.sign`/TTD, lalu verifikasi ulang struk HTML + PDF + regresi penuh.

---

## 3. KONVENSI & JEBAKAN KODE

- **Cache-buster**: setiap ubah JS/CSS, naikkan `?v=` di `layouts/header.php` (css) & `layouts/footer.php` (js); halaman standalone (mis. `pages/bayar/tunggu.php`) punya link sendiri — samakan versinya. Versi terakhir: CSS publik `20260917h` + `admin.css 20260917g`, JS `20260917e`. (CSS: `base.css`, `layout.css`, `components.css`, `pages.css`, `theme.css`, `admin.css`)
- **TZ**: MySQL berjalan WIB; `config/database.php` memanggil `date_default_timezone_set('Asia/Jakarta')`. Jangan hapus — `strtotime()`/`date()` untuk `batas_bayar` & countdown bergantung padanya.
- **`getenv()` mengembalikan `false`** saat tak diset (bukan null) → wajib `!== false`, jangan `??`.
- **`[hidden]` harus selalu didukung** — jangan hapus rule `[hidden]{display:none!important}`.
- `update_password()` mengecek panjang password baru DULU, baru verifikasi password lama.
- Avatar disimpan relatif `assets/avatars/xxx.png`; di frontend dirender `"/" + avatar` (absolut).
- Jangan simpan akun uji (`uji.*`, dll) di DB; bersihkan setelah tes.
- `modal login cuma satu` — jangan re-introduce inline duplicate di halaman.

---

## 4. BUG TERSISA (prioritas)

### 🔴 P1 — Keamanan
*Semua P1 (CSRF, hapus POST, upload MIME, env DB) sudah selesai 14 Sep — lihat "YANG SUDAH SELESAI".*

### 🟠 P2 — Fungsional
*Semua P2 (form ke DB, select, tanggal, artikel DB, isKurikulumPage, label, dot aria, kontras) sudah selesai 14 Sep.*

### 🟡 P3 — Kualitas/Lainnya
1. **Logout & reset password sudah konsisten** (P3-1..3-3 selesai 14 Sep) — lihat "YANG SUDAH SELESAI".
2. **"Masuk dengan Google" sudah terpasang** (OAuth + PKCE) — tinggal isi `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` di `.env` dan daftarkan redirect URI di Google Cloud Console.
3. `pages/akun/profil.php` upload avatar & artikel beranda memakai `assets/` path relatif — verifikasi konsisten saat di-deploy ke subfolder live.
4. Test headless lama (`/tmp/pup-test`, `repro*.mjs`) — artefak sementara, bisa dihapus.

---

## 5. USULAN LANJUTAN (besok)
1. ~~Google OAuth~~ — **terpasang** (15 Sep); tinggal isi kredensial di `.env` + daftarkan redirect URI di Google Cloud Console.
3. ~~Setup SMTP~~ — **terpasang & teruji** (16 Sep, Gmail App Password): `php tests/test-mail.php` OK → email reset password benar-benar terkirim. Fallback link di layar tetap ada jika kirim gagal.
4. Deploy/copy perbaikan ke situs live `almutqon-verif.infinityfreeapp.com` + terapkan `.env`, semua skema (`schema-pendaftar.php`, `schema-password_resets.php`, `schema-donasi.php`, `schema-theme.php`) di sana. **Tambahan wajib (fitur kanal pembayaran, 17 Sep):** jalankan `ALTER TABLE pendaftar ADD COLUMN kanal_pembayaran VARCHAR(30) NULL AFTER metode_pembayaran;` dan `ALTER TABLE donasi ADD COLUMN kanal_pembayaran VARCHAR(30) NULL AFTER metode;` di DB live, isi kunci `DONASI_BANK_<KODE>_NOMOR` / `DONASI_EWALLET_<PROVIDER>_NOMOR` + `DONASI_BANK_NAMA`/`DONASI_EWALLET_NAMA` di `.env` live (nomor BRI/KROM/SeaBank masih dummy), dan upload folder `assets/pay/` bila memakai ikon logo asli. **Fitur Pendaftaran Saya + notifikasi email + hero lunas (17 Sep) tidak butuh migrasi/env baru** — cukup salin file (`pages/akun/pendaftaran-saya.php`, `api/pendaftar.php`, `pages/bayar/tunggu.php`, `layouts/header.php`, `pages/akun/pengaturan.php`, `tests/perbaikan.php`).
5. ~~Pagination artikel + kategori filter~~ — **selesai** (15 Sep), lihat §2.
6. Optimasi gambar artikel beranda (fallback Unsplash tsb bisa diganti konten asli).
7. **RENCANA DIPUTUSKAN (17 Sep): integrasi struk → verif-almt via API INTERNAL (Opsi B)** — project `/Users/mac/Herd/verif-almt` (`http://verif-almt.test`, DB `db_almutqon-verif`): staff + tabel `dokumen` (no_surat UNIQUE, token 64-char HMAC, kode 6-digit, PDF di `storage/uploads/`, status active/revoked) + verifikasi publik token/kode/scan QR + `activity_log`.
   - **Tujuan**: struk/invoice ALMUTQON (`INV-…`) terdaftar sebagai dokumen terverifikasi, sehingga struk memuat QR + link verifikasi publik.
   - **Keputusan**: pakai **(b) API internal**, tolak (a) bridge antar-DB. Perbandingan: bridge cepat (tanpa endpoint, 1 PDO kedua di `tandai_lunas()`) tapi kopling kredensial DB + path `copy()` antar-folder (rapuh di live), duplikasi logika HMAC/`nomor_urut FOR UPDATE`/validasi/retry `code_6digit`, audit kotor (admin ALMUTQON bukan `staff.id`), transaksi lintas DB tak atomik; API: batas bersih (1 token Bearer rotatable, `.env` tetap terpisah), logika tunggal milik verif (`dashboard.php:94-159` create + `192-211` revoke dipakai ulang), idempoten `no_surat=no_invoice`, file via multipart ke `storage/uploads/` sesuai aturan verif, audit via service-account "ALMUTQON-bridge". Kelemahan API: kerja awal lebih besar (endpoint + auth + klien + migrasi) + failure mode HTTP (timeout/retry) + QR-PNG ke `StrukPdf` (murni teks, tanpa lib gambar) jadi tahap 2.
   - **Kontrak API (verif-almt, belum dibuat)**: `POST /api/dokumen` (Bearer `VERIF_API_TOKEN`; validasi = create-doc; idempoten bila `no_surat` ada → kembalikan baris lama; terima PDF ≤10MB + `finfo`; return `{token, code_6digit, verify_url}`) + `POST /api/dokumen/revoke|restore`; tanpa token → 401/403.
   - **Klien (almutqon, belum dibuat)**: `config/verif-client.php` (`VERIF_API_URL/TOKEN` di `.env`); migrasi `pendaftar.verif_token/code/url` (**wajib juga di live**); hook `tandai_lunas()` (`config/pembayaran.php:113`) panggil API tanpa menggagalkan Lunas (flash + retry manual); hook `batal_lunas()` (`config/pendaftar-admin-actions.php:100`) → revoke.
   - **Tampil**: `struk.php` + `tunggu.php` LUNAS tambah blok "Verifikasi Dokumen" (link + kode + QR HTML); PDF tahap 1 hanya URL+kode.
   - **Uji**: buat → `index.php?token=`/`?code=` valid → revoke → "dicabut" + `download 404` → restore; regresi `tests/perbaikan.php` tetap hijau.
   - **Catatan**: kedua project PHP native + MySQL (pola CSRF/sesi mirip); DB dan `.env` keduanya terpisah — jangan campur kredensial.

---

## 6. ARTIKEL DUMMY UJI COBA (15 Sep)
- 10 artikel dummy `UJI-1..10` dibuat (prefix judul `UJI-`) di DB untuk menguji pagination/filter: Tips Belajar (3), Kabar (3), Pendidikan (4) — total artikel kini 12 (2 beranda asli). Tanggal sengaja di-`DATE_SUB` (16 Agu–12 Sep) agar urutan & pagination teruji.
- Seeder: `php tests/seed-dummy.php` (hapus `UJI-%` lalu insert ulang). **Hapus semua artikel `UJI-%` sebelum deploy ke live** (contoh: `DELETE FROM articles WHERE judul LIKE 'UJI-%'`).