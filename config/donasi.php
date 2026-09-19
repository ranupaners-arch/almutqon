<?php
// config/donasi.php — info rekening / QRIS / e-wallet tujuan transfer manual (Opsi B, tanpa gateway)
// Nilai diambil dari env/.env (lihat bagian DONASI di .env). Ganti dengan data asli lembaga.

function donasi_env(string $key, string $default): string
{
    $val = getenv($key);
    return $val !== false && $val !== '' ? $val : $default;
}

function donasi_info(): array
{
    return [
        // Transfer Bank
        'bank_nama'  => donasi_env('DONASI_BANK_NAMA', 'Yayasan Pendidikan Al-Mutqon'),
        'bank_bank'  => donasi_env('DONASI_BANK_BANK', 'BSI'),
        'bank_nomor' => donasi_env('DONASI_BANK_NOMOR', '1234567890'),

        // E-Wallet (DANA/OVO/GoPay)
        'ewallet_tipe'  => donasi_env('DONASI_EWALLET_TIPE', 'DANA'),
        'ewallet_nama'  => donasi_env('DONASI_EWALLET_NAMA', 'Almutqon'),
        'ewallet_nomor' => donasi_env('DONASI_EWALLET_NOMOR', '081234567890'),

        // QRIS (path gambar, relatif ke root; kosongkan bila belum ada)
        'qris_gambar' => donasi_env('DONASI_QRIS_GAMBAR', ''),
    ];
}

// Daftar bank tujuan + kode transfer antarbank (3 digit).
// Nomor rekening dummy — ganti lewat DONASI_BANK_<KODE>_NOMOR di .env dengan data asli.
function donasi_bank_channels(): array
{
    return [
        'BSI'     => ['label' => 'BSI',     'kode' => '451', 'nomor' => donasi_env('DONASI_BANK_BSI_NOMOR', '7252363372')],
        'BRI'     => ['label' => 'BRI',     'kode' => '002', 'nomor' => donasi_env('DONASI_BANK_BRI_NOMOR', '123456789012')],
        'KROM'    => ['label' => 'KROM',    'kode' => '459', 'nomor' => donasi_env('DONASI_BANK_KROM_NOMOR', '1234567890')],
        'SEABANK' => ['label' => 'SeaBank', 'kode' => '535', 'nomor' => donasi_env('DONASI_BANK_SEABANK_NOMOR', '9012345678')],
    ];
}

// Daftar e-wallet tujuan.
function donasi_ewallet_channels(): array
{
    return [
        'ShopeePay' => ['label' => 'ShopeePay', 'nomor' => donasi_env('DONASI_EWALLET_SHOPEEPAY_NOMOR', '082258442647')],
        'DANA'      => ['label' => 'DANA',      'nomor' => donasi_env('DONASI_EWALLET_DANA_NOMOR', '082258442647')],
        'GoPay'     => ['label' => 'GoPay',     'nomor' => donasi_env('DONASI_EWALLET_GOPAY_NOMOR', '082258442647')],
    ];
}

// Resolusi kanal (bank/provider) yang dipilih: kosong/tak dikenal → default dari .env/label pertama.
function donasi_resolve_bank(string $kanal): string
{
    $banks = donasi_bank_channels();
    if ($kanal !== '' && isset($banks[$kanal])) {
        return $kanal;
    }
    $def = donasi_info()['bank_bank'];
    return isset($banks[$def]) ? $def : (string) array_key_first($banks);
}

function donasi_resolve_ewallet(string $kanal): string
{
    $wallets = donasi_ewallet_channels();
    if ($kanal !== '' && isset($wallets[$kanal])) {
        return $kanal;
    }
    $def = donasi_info()['ewallet_tipe'];
    return isset($wallets[$def]) ? $def : (string) array_key_first($wallets);
}

// Bangun instruksi transfer untuk halaman tunggu / respon API.
function donasi_target(string $metode, string $kanal = ''): array
{
    $info = donasi_info();

    if ($metode === 'bank') {
        $kanal = donasi_resolve_bank($kanal);
        $b = donasi_bank_channels()[$kanal];
        return [
            'type'   => 'bank',
            'kanal'  => $kanal,
            'judul'  => 'Transfer Bank ' . $b['label'],
            'baris'  => [
                $b['label'] . ' · Kode antarbank ' . $b['kode'],
                $b['nomor'],
                'a.n. ' . $info['bank_nama'],
            ],
            'gambar' => '',
        ];
    }

    if ($metode === 'ewallet') {
        $kanal = donasi_resolve_ewallet($kanal);
        $w = donasi_ewallet_channels()[$kanal];
        return [
            'type'   => 'ewallet',
            'kanal'  => $kanal,
            'judul'  => 'E-Wallet ' . $w['label'],
            'baris'  => [$w['label'], $w['nomor'], 'a.n. ' . $info['ewallet_nama']],
            'gambar' => '',
        ];
    }

    return [
        'type'   => 'qris',
        'kanal'  => '',
        'judul'  => 'QRIS',
        'baris'  => $info['qris_gambar'] !== '' ? [] : ['QRIS belum tersedia.'],
        'gambar' => $info['qris_gambar'],
    ];
}

// Label kanal untuk ditampilkan di admin (mis. "BSI", "GoPay"); '' bila kanal kosong.
function donasi_kanal_label(string $metode, string $kanal): string
{
    if ($metode === 'bank') {
        $banks = donasi_bank_channels();
        return isset($banks[$kanal]) ? $banks[$kanal]['label'] : '';
    }
    if ($metode === 'ewallet') {
        $wallets = donasi_ewallet_channels();
        return isset($wallets[$kanal]) ? $wallets[$kanal]['label'] : '';
    }
    return '';
}
