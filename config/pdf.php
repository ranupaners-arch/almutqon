<?php
// config/pdf.php — generator PDF murni PHP (tanpa library pihak ketiga)
// Membuat struk/kwitansi pembayaran kursus satu halaman (A4 portrait).
// Font: Helvetica / Helvetica-Bold (core font WinAnsi) — konten struk Latin/Indonesia.
require_once __DIR__ . '/donasi.php';
require_once __DIR__ . '/qrcode.php';

if (!function_exists('pdf_winansi')) {
    function pdf_winansi(string $s): string
    {
        if (function_exists('mb_convert_encoding')) {
            $s = mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
        } elseif (function_exists('iconv')) {
            $s = iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s) ?: '';
        }
        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
    }

    function pdf_escape(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }
}

class StrukPdf
{
    private const MARGIN = 42;
    private const W = 595; // A4
    private const H = 842;

    private array $lines = [];
    private float $y;

    public function __construct()
    {
        $this->y = self::H - self::MARGIN;
    }

    private function size(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2), '0'), '.');
    }

    private function widthRaw(string $raw, float $size, bool $bold): float
    {
        return strlen($raw) * ($bold ? 0.62 : 0.55) * $size;
    }

    private function text(string $s, float $x, float $y, float $size, bool $bold = false, array $rgb = [0, 0, 0], string $align = 'L'): void
    {
        $raw = pdf_winansi($s);
        $w = $this->widthRaw($raw, $size, $bold);
        if ($align === 'C') $x -= $w / 2;
        if ($align === 'R') $x -= $w;
        $this->lines[] = '/' . ($bold ? 'F2' : 'F1') . ' ' . $this->size($size) . ' Tf';
        $this->lines[] = sprintf('%.3f %.3f %.3f rg', $rgb[0], $rgb[1], $rgb[2]);
        $this->lines[] = sprintf('BT %.2f %.2f Td (%s) Tj ET', $x, $y, pdf_escape($raw));
    }

    private function drawLine(float $x1, float $y1, float $x2, float $y2, array $rgb = [0, 0, 0], float $lw = 0.9): void
    {
        $this->lines[] = sprintf('%.3f %.3f %.3f RG %.2f w', $rgb[0], $rgb[1], $rgb[2], $lw);
        $this->lines[] = sprintf('%.2f %.2f m %.2f %.2f l S', $x1, $y1, $x2, $y2);
    }

    private function rect(float $x, float $y, float $w, float $h, array $rgb = [0, 0, 0]): void
    {
        $this->lines[] = sprintf('%.3f %.3f %.3f rg', $rgb[0], $rgb[1], $rgb[2]);
        $this->lines[] = sprintf('%.2f %.2f %.2f %.2f re f', $x, $y, $w, $h);
    }

    private function center(string $s, float $size, bool $bold = false, array $rgb = [0, 0, 0]): void
    {
        $this->y -= $size * 1.75;
        $this->text($s, self::W / 2, $this->y, $size, $bold, $rgb, 'C');
    }

    private function baris(string $label, string $nilai, float $kolom = 150, float $size = 11.5, bool $nilaiTebal = false): void
    {
        $this->y -= 23;
        $this->text($label, self::MARGIN, $this->y, $size, false, [0.25, 0.25, 0.35]);
        $this->text($nilai, self::MARGIN + $kolom, $this->y, $size, $nilaiTebal, [0, 0, 0]);
    }

    private function gap(float $v): void
    {
        $this->y -= $v;
    }

    private function separator(): void
    {
        $this->y -= 9;
        $this->drawLine(self::MARGIN, $this->y, self::W - self::MARGIN, $this->y, [0.55, 0.55, 0.6], 0.8);
        $this->y -= 9;
    }

    // Tanda tangan terpusat (satu kolom) + QR verifikasi di antara label dan nama bila $verif ada.
    // Urutan dari atas: label -> QR -> "Kode: ..." -> garis tanda tangan -> nama — menyamai blok
    // tanda tangan web (pages/bayar/struk.php) agar email, unduhan, dan web seragam.
    // Nama penandatangan = identitas TETAP 'Admin Almutqon' (bukan nama admin sesi) agar sama
    // di email, unduhan, dan web — tidak berubah tergantung siapa yang mengeklik/unduh.
    private function ttd(string $label, string $nama, ?array $verif = null): void
    {
        $nama = 'Admin Almutqon';
        $this->y -= 26;
        $this->text($label, self::W / 2, $this->y, 11, false, [0.25, 0.25, 0.35], 'C');

        $verifUrl = trim((string) ($verif['url'] ?? ''));
        if ($verifUrl !== '') {
            $qrPx = 96.0;
            $tTop = $this->y - 14;   // tepi atas QR (di bawah label)
            $qBottom = $tTop - $qrPx;
            $this->text('Kode: ' . (string) ($verif['code'] ?? ''), self::W / 2, $qBottom - 4, 9.5, true, [0, 0, 0], 'C');

            $modules = qr_matrix($verifUrl, 'M');
            if ($modules) {
                $n = count($modules);
                $total = $n + 8; // quiet zone 4 modul tiap sisi
                $m = $qrPx / $total;
                $x0 = (self::W - $qrPx) / 2 + 4 * $m; // QR (beserta quiet zone) di tengah halaman
                $y0 = $qBottom + 4 * $m;              // pojok kiri-bawah modul (baris bawah)
                $this->lines[] = '0 0 0 rg';
                foreach ($modules as $r => $mrow) {
                    foreach ($mrow as $c => $dark) {
                        if ($dark) {
                            $this->lines[] = sprintf('%.2f %.2f %.2f %.2f re f', $x0 + $c * $m, $y0 + ($n - 1 - $r) * $m, $m, $m);
                        }
                    }
                }
            }
            $this->y = $qBottom - 34; // sisakan ruang di bawah "Kode" sebelum garis tanda tangan
        } else {
            $this->y -= 20;
        }

        $half = 62.0; // setengah lebar garis tanda tangan (total 124 pt) — di tengah halaman
        $this->drawLine(self::W / 2 - $half, $this->y + 14, self::W / 2 + $half, $this->y + 14, [0.35, 0.35, 0.4], 0.8);
        $this->text($nama, self::W / 2, $this->y + 6, 11.5, true, [0, 0, 0], 'C');
    }

    // Bangun dokumen struk. $row = baris `pendaftar`, $pembayaran = config pembayaran_info(), $admin dipakai tapi
    // penandatangan SELALU "Admin Almutqon" (identitas tetap, lihat ttd()).
    // $verif = ['url' => ..., 'code' => ...] — bila ada, QR verifikasi disisipkan di blok tanda tangan (label -> QR -> kode -> garis -> nama).
    public function struk(array $row, array $pembayaran, string $admin, ?array $verif = null): string
    {
        $biaya = (int) $row['biaya'];
        $lunas = ($row['status_pembayaran'] ?? 'belum') === 'lunas';

        $labelProgram = match ($row['program']) {
            'offline' => ($row['metode'] === 'Privat') ? 'Offline Privat' : 'Offline Non-Privat',
            'online'  => ($row['metode'] === 'Privat') ? 'Online Privat' : 'Online Non-Privat',
            'dauroh'  => 'Dauroh ' . ($row['metode'] ?: 'Offline'),
        };

        // --- Kop ---
        $this->center('AL MUTQON', 24, true, [0.08, 0.12, 0.30]);
        $this->center('Arabic Learning Center', 11.5, false, [0.35, 0.35, 0.48]);
        $this->gap(6);
        $this->y -= 22;
        $this->drawLine(self::MARGIN, $this->y + 8, self::W - self::MARGIN, $this->y + 8, [0.08, 0.12, 0.30], 1.7);

        $this->center('STRUK KONFIRMASI PEMBAYARAN KURSUS', 13, true, [0.08, 0.12, 0.30]);
        $this->gap(12);

        // --- Info umum ---
        $this->baris("No. Invoice", ':  ' . $row['no_invoice']);
        $this->baris("Tanggal Cetak", ':  ' . date('d M Y'));
        if ($lunas && !empty($row['tanggal_bayar'])) {
            $this->baris("Tanggal Bayar", ':  ' . date('d M Y', strtotime($row['tanggal_bayar'])));
        }
        $this->separator();

        // --- Data penerima ---
        $this->baris("Diterima dari", ':  ' . $row['nama']);
        $this->baris("WhatsApp", ':  ' . $row['whatsapp']);
        $this->baris("Program", ':  ' . $labelProgram);
        if ($row['program'] === 'dauroh' && (int) $row['jumlah_peserta'] > 1) {
            $this->baris("Jumlah Peserta", ':  ' . (int) $row['jumlah_peserta'] . ' orang');
        }
        if (!empty($row['instansi'])) {
            $this->baris("Instansi", ':  ' . $row['instansi']);
        }
        $this->separator();

        // --- Pembayaran ---
        $this->baris("Total Pembayaran", ':  ' . rupiah($biaya), 150, 12.5, true);
        $this->baris("Terbilang", ':  ' . ucfirst(terbilang($biaya)) . ' rupiah');
        $metodeBayar = $row['metode_pembayaran'] ?: '';
        if ($metodeBayar !== '') {
            $t = donasi_target($metodeBayar, (string) ($row['kanal_pembayaran'] ?? ''));
            $metodeBayar = $t['judul'];
        }
        $this->baris("Metode Pembayaran", ':  ' . ($metodeBayar ?: '-'));
        $this->baris('Status', ':  ' . ($lunas ? 'LUNAS' : strtoupper($row['status_pembayaran'] ?? 'belum')), 150, 12.5, true);
        $this->separator();

        // --- Tanda tangan + verifikasi (QR) ---
        $namaAdmin = $admin !== '' ? $admin : 'Admin Almutqon';
        $verifArr = null;
        $verifUrl = trim((string) ($verif['url'] ?? ''));
        if ($verifUrl !== '') {
            $verifArr = ['url' => $verifUrl, 'code' => (string) ($verif['code'] ?? '')];
        }
        $this->ttd('Bagian Keuangan', $namaAdmin, $verifArr);

        // --- Footer ---
        $this->gap(46);
        $this->text('Dokumen ini sah sebagai bukti pembayaran kursus AL MUTQON. Disimpan untuk arsip.', self::W / 2, 40, 8.5, false, [0.45, 0.45, 0.5], 'C');
        $this->text('Dicetak otomatis oleh sistem pendaftaran AL MUTQON.', self::W / 2, 32, 8.5, false, [0.45, 0.45, 0.5], 'C');

        return $this->output();
    }

    // Serialisasi file PDF
    private function output(): string
    {
        $content = implode("\n", $this->lines);
        $stream = $content . "\n";

        $objects = [
            '1 0 obj' . "\n" . '<< /Type /Catalog /Pages 2 0 R >>' . "\n" . 'endobj',
            '2 0 obj' . "\n" . '<< /Type /Pages /Kids [3 0 R] /Count 1 >>' . "\n" . 'endobj',
            '3 0 obj' . "\n" . '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                . ' /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>' . "\n" . 'endobj',
            '4 0 obj' . "\n" . '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>' . "\n" . 'endobj',
            '5 0 obj' . "\n" . '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>' . "\n" . 'endobj',
            '6 0 obj' . "\n" . '<< /Length ' . strlen($stream) . ' >>' . "\n" . 'stream' . "\n" . $stream . 'endstream' . "\n" . 'endobj',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj . "\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF\n";

        return $pdf;
    }
}