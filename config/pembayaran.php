<?php
// config/pembayaran.php — biaya kursus tetap per program + generator no. invoice + terbilang
require_once __DIR__ . '/donasi.php';

// Fee (16 Sep, sumber: pemilik). Privat dihitung per jam × jam per sesi; sisanya flat/per orang.
function biaya_kursus_config(): array
{
    return [
        'offline' => [
            'Privat'     => ['per_jam' => 318000, 'jam' => 2],    // 318.000/jam × 2 jam = 636.000
            'Non-Privat' => ['flat' => 299000],
            'label'      => 'Offline Privat', 'label_non' => 'Offline Non-Privat',
        ],
        'online' => [
            'Privat'     => ['per_jam' => 199000, 'jam' => 1.5],  // 199.000/jam × 1,5 jam = 298.500
            'Non-Privat' => ['flat' => 160000],
            'label'      => 'Online Privat', 'label_non' => 'Online Non-Privat',
        ],
        'dauroh' => [
            'per_orang' => ['Offline' => 99000, 'Online' => 70000],
            'min_peserta' => 40,
            'label' => 'Dauroh',
        ],
    ];
}

// Hitung biaya total sekali sesi (per pertemuan). Return int Rupiah + keterangan.
function hitung_biaya_kursus(string $program, string $metode, int $jumlah_peserta): array
{
    $cfg = biaya_kursus_config();
    $program = strtolower($program);

    if ($program === 'offline' || $program === 'online') {
        $k = $cfg[$program];
        if (!isset($k[$metode])) {
            throw new InvalidArgumentException("Metode '{$metode}' tidak valid untuk program {$program}.");
        }
        if (isset($k[$metode]['per_jam'])) {
            $biaya = (int) round($k[$metode]['per_jam'] * $k[$metode]['jam']);
            $ket = "{$k[$metode]['per_jam']} / jam × " . trim(rtrim(rtrim(number_format($k[$metode]['jam'], 1, ',', '.'), '0'), ',') . " jam");
        } else {
            $biaya = (int) ($k[$metode]['flat'] ?? 0);
            $ket = 'paket per sesi';
        }
        return ['biaya' => $biaya, 'ket' => $ket, 'label' => $k[$metode === 'Privat' ? 'label' : 'label_non']];
    }

    // dauroh — biaya per orang × jumlah peserta
    if ($program !== 'dauroh' || !isset($cfg['dauroh']['per_orang'][$metode])) {
        throw new InvalidArgumentException("Program/metode tidak valid ('{$program}'/'{$metode}').");
    }
    $per  = (int) $cfg['dauroh']['per_orang'][$metode];
    $biaya = $per * max(1, $jumlah_peserta);
    return ['biaya' => $biaya, 'ket' => $per . ' / orang × ' . $jumlah_peserta . ' peserta', 'label' => 'Dauroh ' . $metode];
}

// Buat nomor invoice baru berbasis tanggal + counter bebas duplikat
function buat_no_invoice(PDO $pdo): string
{
    for ($i = 0; $i < 5; $i++) {
        $no = 'INV-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pendaftar WHERE no_invoice = ?");
        $stmt->execute([$no]);
        if ((int) $stmt->fetchColumn() === 0) return $no;
    }
    // fallback unix-timestamp (praktis pasti unik)
    return 'INV-' . date('Ymd') . '-' . substr((string) time(), -8);
}

// Cari pendaftar berdasarkan no. invoice (untuk struk)
function cari_pendaftar_by_invoice(PDO $pdo, string $no_invoice): array|false
{
    $stmt = $pdo->prepare(
        "SELECT * FROM pendaftar WHERE no_invoice = ? LIMIT 1"
    );
    $stmt->execute([$no_invoice]);
    return $stmt->fetch();
}

// Terbilang Rupiah (mis. 636000 -> "enam ratus tiga puluh enam ribu rupiah")
function terbilang(int $n): string
{
    $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    if ($n < 12) return $satuan[$n] ?? '';
    if ($n < 20) return terbilang($n - 10) . ' belas';
    if ($n < 100) return terbilang(intdiv($n, 10)) . ' puluh ' . terbilang($n % 10);
    if ($n < 200) return 'seratus ' . terbilang($n - 100);
    if ($n < 1000) return terbilang(intdiv($n, 100)) . ' ratus ' . terbilang($n % 100);
    if ($n < 2000) return 'seribu ' . terbilang($n - 1000);
    if ($n < 1000000) return terbilang(intdiv($n, 1000)) . ' ribu ' . terbilang($n % 1000);
    if ($n < 1000000000) {
        if ($n < 2000000) return 'satu juta ' . terbilang($n - 1000000);
        return terbilang(intdiv($n, 1000000)) . ' juta ' . terbilang($n % 1000000);
    }
    if ($n < 2000000000) return 'satu miliar ' . terbilang($n - 1000000000);
    return terbilang(intdiv($n, 1000000000)) . ' miliar ' . terbilang($n % 1000000000);
}

// "Rp1.234.567" standar
function rupiah(int $n): string
{
    return 'Rp' . number_format($n, 0, ',', '.');
}

// Info rekening tujuan pembayaran kursus (reuse config donasi)
function pembayaran_info(): array
{
    return donasi_info();
}

// Terapkan status pembayaran "LUNAS" — nomor invoice baru dibuat DI SINI (hanya saat admin mengonfirmasi pembayaran diterima).
// Return: no_invoice (string).
function tandai_lunas(PDO $pdo, int $id, string $metode_pembayaran, int $biaya): string
{
    $stmt = $pdo->prepare("SELECT no_invoice FROM pendaftar WHERE id = ?");
    $stmt->execute([$id]);
    $noInvoice = (string) ($stmt->fetchColumn() ?: '');

    if ($noInvoice === '') {
        $noInvoice = buat_no_invoice($pdo);
    }

    $stmt = $pdo->prepare(
        "UPDATE pendaftar
         SET status_pembayaran = 'lunas',
             metode_pembayaran = ?,
             no_invoice = ?,
             biaya = ?,
             tanggal_bayar = NOW()
         WHERE id = ?"
    );
    $stmt->execute([$metode_pembayaran, $noInvoice, $biaya, $id]);
    return $noInvoice;
}

// Struktur biaya ringkas untuk dipakai front-end (harga live di modal pendaftaran).
function biaya_for_js(): array
{
    $cfg = biaya_kursus_config();
    return [
        'offline' => [
            'Privat'     => ['total' => hitung_biaya_kursus('offline', 'Privat', 1)['biaya'], 'ket' => 'Privat · ±2 jam / sesi (Rp318.000 / jam)'],
            'Non-Privat' => ['total' => hitung_biaya_kursus('offline', 'Non-Privat', 1)['biaya'], 'ket' => 'Non-Privat · per sesi'],
        ],
        'online' => [
            'Privat'     => ['total' => hitung_biaya_kursus('online', 'Privat', 1)['biaya'], 'ket' => 'Privat · ±90 menit / sesi (Rp199.000 / jam)'],
            'Non-Privat' => ['total' => hitung_biaya_kursus('online', 'Non-Privat', 1)['biaya'], 'ket' => 'Non-Privat · per sesi'],
        ],
        'dauroh' => [
            'per_orang'  => $cfg['dauroh']['per_orang'],
            'min'        => $cfg['dauroh']['min_peserta'],
            'ket'        => 'Biaya per orang × jumlah peserta',
        ],
    ];
}