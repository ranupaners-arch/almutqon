<?php
require_once __DIR__ . '/../config/pembayaran.php';
$__daurohMin = (int) (biaya_kursus_config()['dauroh']['min_peserta'] ?? 40);

// Render panel pilihan bank / e-wallet (dipakai 3 form program + modal donasi).
// Form hanya menampilkan logo + nama kanal; kode antarbank & nomor rekening
// sengaja TIDAK ditampilkan di sini — muncul di halaman status (tunggu.php) / langkah 2 donasi.
$__payBanks = donasi_bank_channels();
$__payWallets = donasi_ewallet_channels();
$__payBrand = [
    'BSI'       => ['bg' => '#00A39D', 'fg' => '#FFFFFF', 'mark' => 'bsi'],
    'BRI'       => ['bg' => '#00529C', 'fg' => '#FFFFFF', 'mark' => 'BRI'],
    'KROM'      => ['bg' => '#17153A', 'fg' => '#A594FF', 'mark' => 'K'],
    'SEABANK'   => ['bg' => '#F4552B', 'fg' => '#FFFFFF', 'mark' => 'SB'],
    'ShopeePay' => ['bg' => '#EE4D2D', 'fg' => '#FFFFFF', 'mark' => 'SP'],
    'DANA'      => ['bg' => '#118EEA', 'fg' => '#FFFFFF', 'mark' => 'DANA'],
    'GoPay'     => ['bg' => '#00AEDB', 'fg' => '#FFFFFF', 'mark' => 'GP'],
];
$payLogo = function (string $key) use ($__payBrand): string {
    // Ikon asli: taruh file di assets/pay/ dengan nama <slug>.<svg|png|webp>
    // (contoh: assets/pay/bsi.svg, assets/pay/gopay.png). Bila ada → dipakai; bila tidak → monogram.
    $slug = strtolower($key);
    foreach (['svg', 'png', 'webp'] as $ext) {
        $file = __DIR__ . '/../assets/pay/' . $slug . '.' . $ext;
        if (is_file($file)) {
            $ver = @filemtime($file) ?: 0;
            return '<img class="paychan__logo paychan__logo--img" src="/assets/pay/' . $slug . '.' . $ext . '?v=' . $ver . '" alt="' . htmlspecialchars($key, ENT_QUOTES) . '" loading="lazy" decoding="async">';
        }
    }

    $b = $__payBrand[$key] ?? ['bg' => '#64748B', 'fg' => '#FFFFFF', 'mark' => strtoupper(mb_substr($key, 0, 2))];
    $len = mb_strlen($b['mark']);
    $fs = $len >= 4 ? 10.5 : ($len === 3 ? 12 : ($len === 2 ? 15 : 17));
    return '<svg class="paychan__logo" viewBox="0 0 40 40" aria-hidden="true" focusable="false">'
        . '<rect width="40" height="40" rx="11" fill="' . $b['bg'] . '"/>'
        . '<text x="20" y="20.5" text-anchor="middle" dominant-baseline="central" font-family="Inter, Arial, sans-serif" font-size="' . $fs . '" font-weight="800" fill="' . $b['fg'] . '">' . htmlspecialchars($b['mark'], ENT_QUOTES) . '</text>'
        . '</svg>';
};
$renderPayPanels = function (string $prefix) use ($__payBanks, $__payWallets, $payLogo): string {
    ob_start(); ?>
    <div class="auth__input-group" data-pay-panel="bank" hidden>
        <label id="<?= $prefix ?>_bank_label">Pilih Bank Tujuan *</label>
        <div class="paychan" role="radiogroup" aria-labelledby="<?= $prefix ?>_bank_label">
            <?php foreach ($__payBanks as $key => $b): ?>
                <label class="paychan__item">
                    <input type="radio" name="bank_tujuan" value="<?= htmlspecialchars($key, ENT_QUOTES) ?>" required>
                    <span class="paychan__card"><?= $payLogo($key) ?><span class="paychan__name"><?= htmlspecialchars($b['label'], ENT_QUOTES) ?></span></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="auth__input-group" data-pay-panel="ewallet" hidden>
        <label id="<?= $prefix ?>_ewallet_label">Pilih E-Wallet *</label>
        <div class="paychan" role="radiogroup" aria-labelledby="<?= $prefix ?>_ewallet_label">
            <?php foreach ($__payWallets as $key => $w): ?>
                <label class="paychan__item">
                    <input type="radio" name="ewallet_tujuan" value="<?= htmlspecialchars($key, ENT_QUOTES) ?>" required>
                    <span class="paychan__card"><?= $payLogo($key) ?><span class="paychan__name"><?= htmlspecialchars($w['label'], ENT_QUOTES) ?></span></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<!-- =================== Offline FORM MODAL =================== -->
<div class="modal" id="offlineModal" aria-hidden="true">
    <div class="modal__overlay" data-close-offline></div>
    <div class="modal__content" role="dialog">
        <button class="modal__close" data-close-offline><span class="material-symbols-rounded">close</span></button>
        <div class="modal__header">
            <span class="section-label">Program Offline</span>
            <h2 class="modal__title">Konsultasi Offline</h2>
        </div>
        <form class="auth__form" id="offlineForm" onsubmit="app.submitOffline(event)">
            <?= csrf_field() ?>
            <div class="auth__input-group"><label for="offline_nama">Nama Lengkap *</label><input type="text" id="offline_nama" name="nama" maxlength="120" required></div>
            <div class="auth__input-group"><label for="offline_panggilan">Nama Panggilan</label><input type="text" id="offline_panggilan" name="nama_panggilan" maxlength="100"></div>
            <div class="auth__input-group"><label for="offline_gender">Jenis Kelamin *</label>
                <select id="offline_gender" name="jenis_kelamin" required>
                    <option value="" disabled selected>Pilih…</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>
            <div class="auth__input-group"><label for="offline_usia">Usia *</label><input type="number" id="offline_usia" name="usia" min="1" max="120" required placeholder="Contoh: 20"></div>
            <div class="auth__input-group"><label for="offline_whatsapp">Nomor WhatsApp *</label><input type="tel" id="offline_whatsapp" name="whatsapp" maxlength="30" required></div>
            <div class="auth__input-group"><label for="offline_metode">Metode Pembelajaran *</label>
                <select id="offline_metode" name="metode" required><option value="Privat">Privat</option><option value="Non-Privat">Non-Privat</option></select>
            </div>
            <div class="modal__price" data-price-box="offline">
                <div class="row"><span>Total Bayar</span><strong class="pb-total" id="offline_total">Rp636.000</strong></div>
                <div class="row"><span>Keterangan</span><span class="pb-ket" id="offline_ket">Privat · ±2 jam / sesi (Rp318.000 / jam)</span></div>
            </div>
            <div class="auth__input-group"><label for="offline_kode_diskon">Kode Diskon (opsional)</label><input type="text" id="offline_kode_diskon" name="kode_diskon" maxlength="40" autocomplete="off" placeholder="Masukkan kode promo jika ada"></div>
            <div class="modal__section" style="margin-bottom: 16px;" data-pay-group="offline">
                <h3 class="modal__label">Metode Pembayaran *</h3>
                <div class="modal__methods">
                    <label class="method">
                        <input type="radio" name="offline_metode_bayar" value="bank" required>
                        <span class="method__content"><span class="material-symbols-rounded">account_balance</span><span>Transfer Bank</span></span>
                    </label>
                    <label class="method">
                        <input type="radio" name="offline_metode_bayar" value="qris" required>
                        <span class="method__content"><span class="material-symbols-rounded">qr_code</span><span>QRIS</span></span>
                    </label>
                    <label class="method">
                        <input type="radio" name="offline_metode_bayar" value="ewallet" required>
                        <span class="method__content"><span class="material-symbols-rounded">wallet</span><span>E-Wallet</span></span>
                    </label>
                </div>
                <?= $renderPayPanels('offline') ?>
            </div>
            <div class="auth__input-group"><label for="offline_alamat">Alamat (Jika Home Visit)</label><input type="text" id="offline_alamat" name="alamat" maxlength="255"></div>
            <div class="auth__input-group"><label for="offline_catatan">Informasi Tambahan</label><textarea rows="3" id="offline_catatan" name="catatan" maxlength="5000"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Ajukan Program Offline</button>
        </form>
    </div>
</div>
<!-- =================== Online FORM MODAL =================== -->
<div class="modal" id="onlineModal" aria-hidden="true">
    <div class="modal__overlay" data-close-online></div>
    <div class="modal__content" role="dialog">
        <button class="modal__close" data-close-online><span class="material-symbols-rounded">close</span></button>
        <div class="modal__header">
            <span class="section-label">Program Online</span>
            <h2 class="modal__title">Konsultasi Online</h2>
        </div>
        <form class="auth__form" id="onlineForm" onsubmit="app.submitOnline(event)">
            <?= csrf_field() ?>
            <div class="auth__input-group"><label for="online_nama">Nama Lengkap *</label><input type="text" id="online_nama" name="nama" maxlength="120" required></div>
            <div class="auth__input-group"><label for="online_panggilan">Nama Panggilan</label><input type="text" id="online_panggilan" name="nama_panggilan" maxlength="100"></div>
            <div class="auth__input-group"><label for="online_gender">Jenis Kelamin *</label>
                <select id="online_gender" name="jenis_kelamin" required>
                    <option value="" disabled selected>Pilih…</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>
            <div class="auth__input-group"><label for="online_usia">Usia *</label><input type="number" id="online_usia" name="usia" min="1" max="120" required placeholder="Contoh: 20"></div>
            <div class="auth__input-group"><label for="online_whatsapp">Nomor WhatsApp *</label><input type="tel" id="online_whatsapp" name="whatsapp" maxlength="30" required></div>
            <div class="auth__input-group"><label for="online_metode">Metode Pembelajaran *</label>
                <select id="online_metode" name="metode" required><option value="Privat">Privat</option><option value="Non-Privat">Non-Privat</option></select>
            </div>
            <div class="modal__price" data-price-box="online">
                <div class="row"><span>Total Bayar</span><strong class="pb-total" id="online_total">Rp298.500</strong></div>
                <div class="row"><span>Keterangan</span><span class="pb-ket" id="online_ket">Privat · ±90 menit / sesi (Rp199.000 / jam)</span></div>
            </div>
            <div class="auth__input-group"><label for="online_kode_diskon">Kode Diskon (opsional)</label><input type="text" id="online_kode_diskon" name="kode_diskon" maxlength="40" autocomplete="off" placeholder="Masukkan kode promo jika ada"></div>
            <div class="modal__section" style="margin-bottom: 16px;" data-pay-group="online">
                <h3 class="modal__label">Metode Pembayaran *</h3>
                <div class="modal__methods">
                    <label class="method">
                        <input type="radio" name="online_metode_bayar" value="bank" required>
                        <span class="method__content"><span class="material-symbols-rounded">account_balance</span><span>Transfer Bank</span></span>
                    </label>
                    <label class="method">
                        <input type="radio" name="online_metode_bayar" value="qris" required>
                        <span class="method__content"><span class="material-symbols-rounded">qr_code</span><span>QRIS</span></span>
                    </label>
                    <label class="method">
                        <input type="radio" name="online_metode_bayar" value="ewallet" required>
                        <span class="method__content"><span class="material-symbols-rounded">wallet</span><span>E-Wallet</span></span>
                    </label>
                </div>
                <?= $renderPayPanels('online') ?>
            </div>
            <div class="auth__input-group"><label for="online_alamat">Alamat (Jika Home Visit)</label><input type="text" id="online_alamat" name="alamat" maxlength="255"></div>
            <div class="auth__input-group"><label for="online_catatan">Informasi Tambahan</label><textarea rows="3" id="online_catatan" name="catatan" maxlength="5000"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Ajukan Program Online</button>
        </form>
    </div>
</div>

<!-- =================== DAUROH FORM MODAL =================== -->
<div class="modal" id="daurohModal" aria-hidden="true">
    <div class="modal__overlay" data-close-dauroh></div>
    <div class="modal__content" role="dialog">
        <button class="modal__close" data-close-dauroh><span class="material-symbols-rounded">close</span></button>
        <div class="modal__header">
            <span class="section-label">Dauroh Umum</span>
            <h2 class="modal__title">Ajukan Dauroh</h2>
        </div>
        <form class="auth__form" id="daurohForm" onsubmit="app.submitDauroh(event)">
            <?= csrf_field() ?>
            <div class="auth__input-group"><label for="dauroh_nama">Nama Penanggung Jawab *</label><input type="text" id="dauroh_nama" name="nama" maxlength="120" required></div>
            <div class="auth__input-group"><label for="dauroh_panggilan">Nama Panggilan</label><input type="text" id="dauroh_panggilan" name="nama_panggilan" maxlength="100"></div>
            <div class="auth__input-group"><label for="dauroh_gender">Jenis Kelamin *</label>
                <select id="dauroh_gender" name="jenis_kelamin" required>
                    <option value="" disabled selected>Pilih…</option>
                    <option value="Umum">Umum</option>
                    <option value="Banin">Banin</option>
                    <option value="Banat">Banat</option>
                </select>
            </div>
            <div class="auth__input-group"><label for="dauroh_usia">Usia *</label><input type="number" id="dauroh_usia" name="usia" min="1" max="120" required placeholder="Contoh: 20"></div>
            <div class="auth__input-group"><label for="dauroh_whatsapp">Nomor WhatsApp *</label><input type="tel" id="dauroh_whatsapp" name="whatsapp" maxlength="30" required></div>
            <div class="auth__input-group"><label for="dauroh_instansi">Instansi/Komunitas</label><input type="text" id="dauroh_instansi" name="instansi" maxlength="120"></div>
            <div class="auth__input-group"><label for="dauroh_jumlah">Jumlah Peserta * (min <?= $__daurohMin ?>)</label><input type="number" id="dauroh_jumlah" name="jumlah_peserta" required min="<?= $__daurohMin ?>" value="<?= $__daurohMin ?>"></div>
            <div class="auth__input-group"><label for="dauroh_metode">Metode *</label>
                <select id="dauroh_metode" name="metode" required><option value="Online">Online</option><option value="Offline">Offline</option></select>
            </div>
            <div class="modal__price" data-price-box="dauroh">
                <div class="row"><span>Total Bayar</span><strong class="pb-total" id="dauroh_total">Rp2.800.000</strong></div>
                <div class="row"><span>Keterangan</span><span class="pb-ket" id="dauroh_ket">Online · 70.000 / orang × 40 peserta</span></div>
            </div>
            <div class="auth__input-group"><label for="dauroh_kode_diskon">Kode Diskon (opsional)</label><input type="text" id="dauroh_kode_diskon" name="kode_diskon" maxlength="40" autocomplete="off" placeholder="Masukkan kode promo jika ada"></div>
            <div class="modal__section" style="margin-bottom: 16px;" data-pay-group="dauroh">
                <h3 class="modal__label">Metode Pembayaran *</h3>
                <div class="modal__methods">
                    <label class="method">
                        <input type="radio" name="dauroh_metode_bayar" value="bank" required>
                        <span class="method__content"><span class="material-symbols-rounded">account_balance</span><span>Transfer Bank</span></span>
                    </label>
                    <label class="method">
                        <input type="radio" name="dauroh_metode_bayar" value="qris" required>
                        <span class="method__content"><span class="material-symbols-rounded">qr_code</span><span>QRIS</span></span>
                    </label>
                    <label class="method">
                        <input type="radio" name="dauroh_metode_bayar" value="ewallet" required>
                        <span class="method__content"><span class="material-symbols-rounded">wallet</span><span>E-Wallet</span></span>
                    </label>
                </div>
                <?= $renderPayPanels('dauroh') ?>
            </div>
            <div class="auth__input-group"><label for="dauroh_catatan">Informasi Tambahan</label><textarea rows="3" id="dauroh_catatan" name="catatan" maxlength="5000"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Ajukan Dauroh</button>
        </form>
    </div>
</div>

<!-- =================== DONATION MODAL =================== -->
<div class="modal" id="donationModal" aria-hidden="true">
    <div class="modal__overlay" data-close></div>
    <div class="modal__content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <button class="modal__close" data-close aria-label="Tutup modal">
            <span class="material-symbols-rounded">close</span>
        </button>
        <!-- STEP 1: FORM -->
        <div id="donationForm">
            <div class="modal__header">
                <span class="badge">
                    <span class="badge__dot"></span>
                    Donasi
                </span>
                <h2 class="modal__title" id="modalTitle">Dukung Perjalanan Al-Mutqon</h2>
                <p class="modal__subtitle">Pilih nominal kontribusi dan metode pembayaran yang paling nyaman untuk Anda.</p>
            </div>
            <?= csrf_field() ?>
            <div class="modal__section">
                <h3 class="modal__label">Data Donatur</h3>
                <div class="auth__input-group"><label for="donasi_nama">Nama Lengkap *</label><input type="text" id="donasi_nama" name="nama" placeholder="Nama Anda" required></div>
                <div class="auth__input-group"><label for="donasi_whatsapp">Nomor WhatsApp *</label><input type="tel" id="donasi_whatsapp" name="whatsapp" placeholder="08xxxxxxxxxx" required></div>
            </div>
            <div class="modal__section">
                <h3 class="modal__label">Pilih Nominal</h3>
                <div class="modal__presets">
                    <button class="preset-btn" data-amount="25000">Rp25.000</button>
                    <button class="preset-btn" data-amount="50000">Rp50.000</button>
                    <button class="preset-btn active" data-amount="100000">Rp100.000</button>
                    <button class="preset-btn" data-amount="250000">Rp250.000</button>
                    <button class="preset-btn preset-btn--other" data-amount="other">Nominal Lainnya</button>
                </div>
                <div class="modal__custom" id="customAmountWrap">
                    <label for="customAmountInput" class="modal__label">Masukkan Nominal</label>
                    <div class="modal__custom-input">
                        <span class="modal__currency">Rp</span>
                        <input type="number" id="customAmountInput" placeholder="0" min="10000">
    </div>
</div>

<script>
window.AMQ = <?= json_encode([
    'biaya' => biaya_for_js(),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.AMQ_RP = n => 'Rp' + n.toLocaleString('id-ID');
</script>
            </div>
            <div class="modal__section" data-pay-group="donasi">
                <h3 class="modal__label">Metode Pembayaran *</h3>
                <div class="modal__methods">
                    <label class="method">
                        <input type="radio" name="payment" value="bank" required>
                        <span class="method__content">
                            <span class="material-symbols-rounded">account_balance</span>
                            <span>Transfer Bank</span>
                        </span>
                    </label>
                    <label class="method">
                        <input type="radio" name="payment" value="qris" required>
                        <span class="method__content">
                            <span class="material-symbols-rounded">qr_code</span>
                            <span>QRIS</span>
                        </span>
                    </label>
                    <label class="method">
                        <input type="radio" name="payment" value="ewallet" required>
                        <span class="method__content">
                            <span class="material-symbols-rounded">wallet</span>
                            <span>E-Wallet</span>
                        </span>
                    </label>
                </div>
                <?= $renderPayPanels('donasi') ?>
            </div>
            <button class="btn btn-primary btn-block" id="donationSubmit">
                Lanjutkan Donasi
                <span class="material-symbols-rounded btn__icon">arrow_forward</span>
            </button>
            <p class="modal__note">
                <span class="material-symbols-rounded">lock</span>
                Kontribusi Anda akan dialokasikan untuk program pendidikan Al-Mutqon.
            </p>
        </div>
        <!-- STEP 2: INSTRUKSI TRANSFER -->
        <div id="donationSuccess" hidden>
            <div class="modal__header" style="text-align: center;">
                <span class="badge" style="margin: 0 auto 12px;">
                    <span class="badge__dot"></span>
                    Menunggu Konfirmasi
                </span>
                <h2 class="modal__title">Donasi Tercatat!</h2>
                <p class="modal__subtitle">Transfer <strong id="donasiAmount">Rp100.000</strong> ke tujuan berikut:</p>
            </div>
            <div class="modal__section">
                <div class="modal__methods" id="donationTarget"></div>
            </div>
            <p class="modal__note" id="donasiUniqueNote">
                <span class="material-symbols-rounded">info</span>
                Tambahkan kode unik <strong>010</strong> di akhir nominal agar mudah dilacak. Kirim bukti transfer ke WhatsApp kami.
            </p>
            <button class="btn btn-primary btn-block" id="donationDone">Selesai</button>
        </div>
    </div>
</div>

<!-- =================== LOGOUT CONFIRM MODAL =================== -->
<div class="modal" id="logoutModal" aria-hidden="true">
    <div class="modal__overlay" data-close-logout></div>
    <div class="modal__content" role="dialog" aria-modal="true" style="max-width: 400px; text-align: center;">
        <button class="modal__close" data-close-logout aria-label="Tutup modal" style="top: 16px; right: 16px;">
            <span class="material-symbols-rounded">close</span>
        </button>
        <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <span class="material-symbols-rounded" style="font-size: 32px;">logout</span>
        </div>
        <h2 class="modal__title" style="margin-bottom: 8px;">Konfirmasi Keluar</h2>
        <p class="modal__subtitle" style="margin-bottom: 24px;">Apakah Anda yakin ingin mengakhiri sesi belajar dan keluar dari akun ini?</p>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-outline btn-block" data-close-logout>Batal</button>
            <button class="btn btn-primary btn-block" id="confirmLogoutBtn" style="background: #EF4444; box-shadow: 0 4px 14px -4px rgba(239, 68, 68, .4);">
                Ya, Keluar
            </button>
        </div>
    </div>
</div>

<!-- =================== LOGIN MODAL =================== -->
<div class="modal" id="loginModal" aria-hidden="true">
    <div class="modal__overlay" data-close-login></div>
    <div class="modal__content modal__content--auth" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
        <button class="modal__close" data-close-login aria-label="Tutup modal">
            <span class="material-symbols-rounded">close</span>
        </button>
        <div class="auth__logo">
            <img src="assets/1.png" alt="Logo Almutqon" class="logo__img">
            <div class="logo__text">
                <span class="logo__name">ALMUTQON</span>
                <span class="logo__sub">ARABIC LEARNING</span>
            </div>
        </div>
        <div class="modal__header">
            <h2 class="modal__title" id="authTitle">Masuk</h2>
            <p class="modal__subtitle" id="authSubtitle">Gunakan akun Anda untuk melanjutkan belajar.</p>
        </div>
        <form class="auth__form" id="authForm">
            <?= csrf_field() ?>
            <div class="auth__input-group" id="authNameGroup" style="display:none;">
                <label for="authName">Nama Lengkap <span class="auth__required">*</span></label>
                <input type="text" id="authName" placeholder="Nama lengkap Anda" maxlength="100">
            </div>
            <div class="auth__input-group">
                <label for="authEmail">Email <span class="auth__required">*</span></label>
                <input type="email" id="authEmail" placeholder="nama@email.com" maxlength="150" required>
            </div>
            <div class="auth__input-group">
                <label for="authPass">Password <span class="auth__required">*</span></label>
                <input type="password" id="authPass" placeholder="••••••••" required>
            </div>
            <div class="auth__input-group" id="authConfirmGroup" style="display:none;">
                <label for="authConfirm">Konfirmasi Password <span class="auth__required">*</span></label>
                <input type="password" id="authConfirm" placeholder="Ulangi password">
            </div>
            <div class="auth__options" id="authOptions">
                <label class="auth__checkbox">
                    <input type="checkbox" id="authRemember" name="remember">
                    <span>Ingat saya</span>
                </label>
                <a href="/auth/lupa-password.php" class="auth__link">Lupa password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="authSubmitBtn">Masuk</button>
        </form>
        <div class="auth__divider">
            <span>atau</span>
        </div>
        <a href="/auth/google-login.php" class="btn btn-outline btn-block auth__google-btn">
            <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/><path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/><path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/><path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/></svg>
            Masuk dengan Google
        </a>
        <p class="auth__footer-text" id="authFooterText">Belum punya akun? <a href="#" class="auth__link" id="authToggleBtn">Daftar</a></p>
    </div>
</div>
