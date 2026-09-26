<?php
// config/pembayaran.php — harga program/diskon + generator invoice + terbilang
require_once __DIR__ . '/donasi.php';

// Fallback dipakai sebelum migrasi konfigurasi dijalankan. Setelah migrasi,
// nilai aktif dibaca dari program_prices agar admin dapat mengubahnya.
function biaya_kursus_config(): array
{
    $fallback = [
        'offline' => [
            'Privat'     => ['harga' => 636000, 'durasi_label' => '±2 jam / sesi'],
            'Non-Privat' => ['flat' => 299000],
            'label'      => 'Offline Privat', 'label_non' => 'Offline Non-Privat',
        ],
        'online' => [
            'Privat'     => ['harga' => 298500, 'durasi_label' => '±90 menit / sesi'],
            'Non-Privat' => ['flat' => 160000],
            'label'      => 'Online Privat', 'label_non' => 'Online Non-Privat',
        ],
        'dauroh' => [
            'per_orang' => ['Offline' => 99000, 'Online' => 70000],
            'min_peserta' => 40,
            'label' => 'Dauroh',
        ],
    ];
    global $pdo;
    if (!isset($pdo) || !($pdo instanceof PDO)) return $fallback;
    try {
        $rows = $pdo->query("SELECT program, metode, harga, durasi_label, min_peserta FROM program_prices WHERE aktif = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return $fallback;
        $cfg = [];
        foreach ($rows as $r) {
            $p = (string) $r['program'];
            $m = (string) $r['metode'];
            $cfg[$p] ??= [];
            $entry = ['harga' => (int) $r['harga'], 'durasi_label' => (string) $r['durasi_label']];
            if ($p === 'dauroh') {
                $cfg[$p]['per_orang'][$m] = (int) $r['harga'];
                $cfg[$p]['min_peserta'] = (int) $r['min_peserta'];
            } elseif ($m === 'Privat') {
                $cfg[$p][$m] = $entry;
                $cfg[$p]['label'] = ucfirst($p) . ' Privat';
            } else {
                $entry['flat'] = (int) $r['harga'];
                $cfg[$p][$m] = $entry;
                $cfg[$p]['label_non'] = ucfirst($p) . ' Non-Privat';
            }
        }
        return $cfg + $fallback;
    } catch (Throwable $e) {
        return $fallback;
    }
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
        $biaya = (int) ($k[$metode]['harga'] ?? $k[$metode]['flat'] ?? 0);
        $ket = (string) ($k[$metode]['durasi_label'] ?? 'paket per sesi');
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

function cari_diskon(PDO $pdo, string $kode, string $program, string $metode): ?array
{
    $kode = strtoupper(trim($kode));
    if ($kode === '') return null;
    $stmt = $pdo->prepare("SELECT * FROM program_discounts WHERE kode = ? AND aktif = 1 AND (mulai IS NULL OR mulai <= NOW()) AND (berakhir IS NULL OR berakhir >= NOW()) AND (batas_penggunaan IS NULL OR terpakai < batas_penggunaan) AND (program = 'semua' OR program = ?) AND (metode = '' OR metode = ?) LIMIT 1");
    $stmt->execute([$kode, $program, $metode]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function hitung_diskon(array $diskon, int $biayaAwal): int
{
    if (($diskon['tipe'] ?? '') === 'persen') return min($biayaAwal, (int) floor($biayaAwal * min(100, (int) $diskon['nilai']) / 100));
    return min($biayaAwal, max(0, (int) ($diskon['nilai'] ?? 0)));
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
    $durasi = static function (array $item, string $fallback): string {
        return (string) ($item['durasi_label'] ?? '') !== '' ? (string) $item['durasi_label'] : $fallback;
    };
    return [
        'offline' => [
            'Privat'     => ['total' => hitung_biaya_kursus('offline', 'Privat', 1)['biaya'], 'ket' => 'Privat · ' . $durasi($cfg['offline']['Privat'], 'per sesi')],
            'Non-Privat' => ['total' => hitung_biaya_kursus('offline', 'Non-Privat', 1)['biaya'], 'ket' => 'Non-Privat · ' . $durasi($cfg['offline']['Non-Privat'], 'per sesi')],
        ],
        'online' => [
            'Privat'     => ['total' => hitung_biaya_kursus('online', 'Privat', 1)['biaya'], 'ket' => 'Privat · ' . $durasi($cfg['online']['Privat'], 'per sesi')],
            'Non-Privat' => ['total' => hitung_biaya_kursus('online', 'Non-Privat', 1)['biaya'], 'ket' => 'Non-Privat · ' . $durasi($cfg['online']['Non-Privat'], 'per sesi')],
        ],
        'dauroh' => [
            'per_orang'  => $cfg['dauroh']['per_orang'],
            'min'        => $cfg['dauroh']['min_peserta'],
            'ket'        => 'Biaya per orang × jumlah peserta',
        ],
    ];
}
