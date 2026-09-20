<?php
// config/qrcode.php — Encoder QR Code murni PHP (tanpa library pihak ketiga).
// Spesifikasi: ISO/IEC 18004. Mode BYTE (8-bit), EC level default 'M',
// versi otomatis 1–10, mask dipilih dengan skor penalti terendah.
// Output: matriks modul (int[][] 0/1), pemanggil (mis. StrukPdf) yang menggambar.
// Pemakaian:  $modules = qr_matrix('https://...');

if (!class_exists('QrEncoder')) {
    final class QrEncoder
    {
        private const BYTE_CAP = [
            // Kapasitas dalam MODE BYTE (data codeword dikurangi header mode 4 bit
            // + count 8 bit (v<=9) / 16 bit (v>=10)) — bukan jumlah codeword.
            1  => ['L' => 17, 'M' => 14, 'Q' => 11, 'H' => 7],
            2  => ['L' => 32, 'M' => 26, 'Q' => 20, 'H' => 14],
            3  => ['L' => 53, 'M' => 42, 'Q' => 32, 'H' => 24],
            4  => ['L' => 78, 'M' => 62, 'Q' => 46, 'H' => 34],
            5  => ['L' => 106, 'M' => 84, 'Q' => 60, 'H' => 44],
            6  => ['L' => 134, 'M' => 106, 'Q' => 74, 'H' => 58],
            7  => ['L' => 154, 'M' => 122, 'Q' => 86, 'H' => 64],
            8  => ['L' => 192, 'M' => 152, 'Q' => 108, 'H' => 84],
            9  => ['L' => 230, 'M' => 180, 'Q' => 130, 'H' => 98],
            10 => ['L' => 271, 'M' => 213, 'Q' => 151, 'H' => 119],
        ];

        // [eccPerBlock, [[jumlahBlok, dataPerBlok], ...]]
        private const RS = [
            1  => ['L' => [7,  [[1, 19]]], 'M' => [10, [[1, 16]]], 'Q' => [13, [[1, 13]]], 'H' => [17, [[1, 9]]]],
            2  => ['L' => [10, [[1, 34]]], 'M' => [16, [[1, 28]]], 'Q' => [22, [[1, 22]]], 'H' => [28, [[1, 16]]]],
            3  => ['L' => [15, [[1, 55]]], 'M' => [26, [[1, 44]]], 'Q' => [18, [[2, 17]]], 'H' => [22, [[2, 13]]]],
            4  => ['L' => [20, [[1, 80]]], 'M' => [18, [[2, 32]]], 'Q' => [26, [[2, 24]]], 'H' => [16, [[4, 9]]]],
            5  => ['L' => [26, [[1, 108]]], 'M' => [24, [[2, 43]]], 'Q' => [18, [[2, 15], [2, 16]]], 'H' => [20, [[2, 11], [2, 12]]]],
            6  => ['L' => [18, [[2, 68]]], 'M' => [16, [[4, 27]]], 'Q' => [24, [[4, 19]]], 'H' => [28, [[4, 15]]]],
            7  => ['L' => [20, [[2, 78]]], 'M' => [18, [[4, 31]]], 'Q' => [18, [[2, 14], [4, 15]]], 'H' => [26, [[4, 13], [1, 14]]]],
            8  => ['L' => [24, [[2, 97]]], 'M' => [22, [[2, 38], [2, 39]]], 'Q' => [22, [[4, 18], [2, 19]]], 'H' => [26, [[4, 14], [2, 15]]]],
            9  => ['L' => [30, [[2, 116]]], 'M' => [22, [[3, 36], [2, 37]]], 'Q' => [20, [[4, 16], [4, 17]]], 'H' => [24, [[4, 12], [4, 13]]]],
            10 => ['L' => [18, [[2, 68], [2, 69]]], 'M' => [26, [[4, 43], [1, 44]]], 'Q' => [24, [[6, 19], [2, 20]]], 'H' => [28, [[6, 15], [2, 16]]]],
        ];

        private const ALIGN = [
            1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34],
            7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
        ];

        private const G_FORMAT = 0x537;
        private const FORMAT_MASK = 0x5412;
        private const G_VERSION = 0x1F25;
        private const EC_BITS = ['L' => 1, 'M' => 0, 'Q' => 3, 'H' => 2];

        private static array $gfExp = [];
        private static array $gfLog = [];
        private static bool $gfReady = false;

        public static function matrix(string $data, string $ec = 'M'): array
        {
            $ec = strtoupper($ec);
            if (!isset(self::BYTE_CAP[1][$ec])) {
                $ec = 'M';
            }

            $version = 0;
            $byteLen = strlen($data);
            foreach (self::BYTE_CAP as $v => $capByEc) {
                if ($capByEc[$ec] >= $byteLen) {
                    $version = $v;
                    break;
                }
            }
            if ($version === 0) {
                throw new \LengthException('Konten QR melebihi kapasitas versi 10/' . $ec . ' (' . $byteLen . ' byte).');
            }

            $size = 17 + 4 * $version;
            $m = array_fill(0, $size, array_fill(0, $size, -1));
            $func = array_fill(0, $size, array_fill(0, $size, false));

            self::placeFunctionPatterns($m, $func, $version);

            $codewords = self::buildCodewords($data, $version, $ec);
            self::placeDataBits($m, $codewords);

            $mask = self::pickMask($m, $func, $version, $ec);
            self::applyMask($m, $func, $mask);
            self::placeFormatInfo($m, self::formatBits($ec, $mask));
            if ($version >= 7) {
                self::placeVersionInfo($m, self::versionBits($version));
            }

            foreach ($m as $r => $row) {
                foreach ($row as $c => $v) {
                    $m[$r][$c] = ($v === 1) ? 1 : 0;
                }
            }
            return $m;
        }

        // --- Pola fungsi ---

        private static function placeFunctionPatterns(array &$m, array &$func, int $version): void
        {
            $n = count($m);

            self::placeFinder($m, $func, 0, 0);
            self::placeFinder($m, $func, 0, $n - 7);
            self::placeFinder($m, $func, $n - 7, 0);

            for ($i = 8; $i < $n - 8; $i++) {
                if ($m[6][$i] === -1) {
                    $m[6][$i] = ($i % 2 === 0) ? 1 : 0;
                    $func[6][$i] = true;
                }
                if ($m[$i][6] === -1) {
                    $m[$i][6] = ($i % 2 === 0) ? 1 : 0;
                    $func[$i][6] = true;
                }
            }

            $poses = self::ALIGN[$version];
            for ($pr = 0; $pr < count($poses); $pr++) {
                for ($pc = 0; $pc < count($poses); $pc++) {
                    $r = $poses[$pr];
                    $c = $poses[$pc];
                    if (($r === 6 && $c === 6) || ($r === 6 && $c === $n - 7) || ($r === $n - 7 && $c === 6)) {
                        continue;
                    }
                    self::placeAlignment($m, $func, $r, $c);
                }
            }

            $m[$n - 8][8] = 1;
            $func[$n - 8][8] = true;

            self::reserveFormatCells($m, $func);
            if ($version >= 7) {
                self::reserveVersionCells($m, $func);
            }
        }

        private static function placeFinder(array &$m, array &$func, int $r, int $c): void
        {
            $n = count($m);
            for ($dr = -1; $dr <= 7; $dr++) {
                for ($dc = -1; $dc <= 7; $dc++) {
                    $r2 = $r + $dr;
                    $c2 = $c + $dc;
                    if ($r2 < 0 || $r2 >= $n || $c2 < 0 || $c2 >= $n || $m[$r2][$c2] !== -1) {
                        continue;
                    }
                    $in = $dr >= 0 && $dr <= 6 && $dc >= 0 && $dc <= 6;
                    $dark = $in && ($dr === 0 || $dr === 6 || $dc === 0 || $dc === 6
                        || ($dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4));
                    $m[$r2][$c2] = $dark ? 1 : 0;
                    $func[$r2][$c2] = true;
                }
            }
        }

        private static function placeAlignment(array &$m, array &$func, int $r, int $c): void
        {
            $n = count($m);
            for ($dr = -2; $dr <= 2; $dr++) {
                for ($dc = -2; $dc <= 2; $dc++) {
                    $r2 = $r + $dr;
                    $c2 = $c + $dc;
                    if ($r2 < 0 || $r2 >= $n || $c2 < 0 || $c2 >= $n || $m[$r2][$c2] !== -1) {
                        continue;
                    }
                    $dark = $dr === -2 || $dr === 2 || $dc === -2 || $dc === 2 || ($dr === 0 && $dc === 0);
                    $m[$r2][$c2] = $dark ? 1 : 0;
                    $func[$r2][$c2] = true;
                }
            }
        }

        private static function reserveFormatCells(array &$m, array &$func): void
        {
            $n = count($m);
            $v = 0;
            $h = 0;
            for ($i = 0; $i < 8; $i++) {
                if ($i === 6) {
                    $v++;
                    $h = 1;
                }
                $r1 = $i + $v; // baris 0..5, 7, 8 pada kolom 8
                $c1 = $i + $h; // kolom 0..5, 7, 8 pada baris 8
                $m[$r1][8] = 0;
                $func[$r1][8] = true;
                $m[8][$c1] = 0;
                $func[8][$c1] = true;
                $m[8][$n - 1 - $i] = 0;
                $func[8][$n - 1 - $i] = true;
                $m[$n - 1 - $i][8] = 0;
                $func[$n - 1 - $i][8] = true;
            }
            $m[$n - 8][8] = 1; // dark module
            $func[$n - 8][8] = true;
        }

        private static function reserveVersionCells(array &$m, array &$func): void
        {
            $n = count($m);
            for ($i = 0; $i < 18; $i++) {
                $r1 = (int) ($i / 3);
                $c1 = ($i % 3) + $n - 8 - 3;
                $m[$r1][$c1] = 0;
                $func[$r1][$c1] = true;

                $r2 = ($i % 3) + $n - 8 - 3;
                $c2 = (int) ($i / 3);
                $m[$r2][$c2] = 0;
                $func[$r2][$c2] = true;
            }
        }

        // --- Encode data ---

        private static function buildCodewords(string $data, int $version, string $ec): array
        {
            $cap = self::BYTE_CAP[$version][$ec];
            [$ecLen, $blocks] = self::RS[$version][$ec];

            $datCw = 0;
            foreach ($blocks as [$num, $len]) {
                $datCw += $num * $len;
            }

            $mode = '0100'; // mode: byte
            $cnt = strlen($data);
            $bits = $mode;
            $bits .= ($version <= 9) ? str_pad(decbin($cnt), 8, '0', STR_PAD_LEFT)
                                     : str_pad(decbin($cnt), 16, '0', STR_PAD_LEFT);
            for ($i = 0; $i < $cnt; $i++) {
                $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
            }

            $capBits = $datCw * 8;
            // 7.4.9 Terminator: min(4, sisa kapasitas)
            $term = min(4, $capBits - strlen($bits));
            if ($term > 0) {
                $bits .= str_repeat('0', $term);
            }
            // 7.4.10 Pad bit ke batas codeword (8 bit), termasuk 8 bit bila sudah pas
            $need = 8 - (strlen($bits) % 8);
            $room = $capBits - strlen($bits);
            if ($need > $room) {
                $need = $room;
            }
            $bits .= str_repeat('0', $need);
            // 7.4.10 Pad codeword EC/11 bergantian, selalu mulai dari EC
            $i = 0;
            while (strlen($bits) < $capBits) {
                $bits .= ($i % 2 === 0) ? '11101100' : '00010001';
                $i++;
            }

            $dataBytes = [];
            for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
                $dataBytes[] = (int) bindec(substr($bits, $i, 8));
            }

            $blockData = [];
            $idx = 0;
            foreach ($blocks as [$num, $len]) {
                for ($b = 0; $b < $num; $b++) {
                    $blockData[] = array_slice($dataBytes, $idx, $len);
                    $idx += $len;
                }
            }

            $blockEcc = [];
            foreach ($blockData as $b) {
                $blockEcc[] = self::rsEcc($b, $ecLen);
            }

            $out = [];
            $maxData = 0;
            foreach ($blockData as $b) {
                $maxData = max($maxData, count($b));
            }
            for ($i = 0; $i < $maxData; $i++) {
                foreach ($blockData as $b) {
                    if (isset($b[$i])) {
                        $out[] = $b[$i];
                    }
                }
            }
            for ($i = 0; $i < $ecLen; $i++) {
                foreach ($blockEcc as $e) {
                    $out[] = $e[$i];
                }
            }
            return $out;
        }

        private static function rsEcc(array $data, int $len): array
        {
            self::gfTables();
            $gen = [1];
            for ($i = 0; $i < $len; $i++) {
                $gen = self::gfPolyMul($gen, [1, self::$gfExp[$i]]);
            }

            $res = array_merge($data, array_fill(0, $len, 0));
            for ($i = 0; $i < count($data); $i++) {
                $f = $res[$i];
                if ($f === 0) {
                    continue;
                }
                for ($j = 0; $j < count($gen); $j++) {
                    $res[$i + $j] ^= self::gfMul($gen[$j], $f);
                }
            }
            return array_slice($res, count($data));
        }

        private static function gfTables(): void
        {
            if (self::$gfReady) {
                return;
            }
            $x = 1;
            for ($i = 0; $i < 255; $i++) {
                self::$gfExp[$i] = $x;
                self::$gfLog[$x] = $i;
                $x <<= 1;
                if ($x & 0x100) {
                    $x ^= 0x11D;
                }
            }
            self::$gfReady = true;
        }

        private static function gfMul(int $a, int $b): int
        {
            if ($a === 0 || $b === 0) {
                return 0;
            }
            self::gfTables();
            return self::$gfExp[(self::$gfLog[$a] + self::$gfLog[$b]) % 255];
        }

        private static function gfPolyMul(array $a, array $b): array
        {
            $out = array_fill(0, count($a) + count($b) - 1, 0);
            for ($i = 0; $i < count($a); $i++) {
                if ($a[$i] === 0) {
                    continue;
                }
                for ($j = 0; $j < count($b); $j++) {
                    $out[$i + $j] ^= self::gfMul($a[$i], $b[$j]);
                }
            }
            return $out;
        }

        // Bit data ditulis ke dalam sel non-fungsi sesuai urutan zigzag standar.
        private static function placeDataBits(array &$m, array $codewords): void
        {
            $n = count($m);
            $bits = [];
            foreach ($codewords as $cw) {
                for ($b = 7; $b >= 0; $b--) {
                    $bits[] = ($cw >> $b) & 1;
                }
            }

            $i = 0;
            $total = count($bits);
            $col = $n - 1;
            $dir = -1; // naik (dari bawah ke atas)
            while ($col > 0) {
                if ($col === 6) {
                    $col--;
                }
                $start = ($dir > 0) ? 0 : $n - 1;
                for ($r = $start; $r >= 0 && $r < $n; $r += $dir) {
                    for ($cc = 0; $cc < 2; $cc++) {
                        $c = $col - $cc;
                        if ($m[$r][$c] === -1) {
                            $m[$r][$c] = ($i < $total) ? $bits[$i] : 0;
                            $i++;
                        }
                    }
                }
                $col -= 2;
                $dir = -$dir;
            }
        }

        // --- Mask & format ---

        private static function maskBit(int $r, int $c, int $mask): bool
        {
            switch ($mask) {
                case 0: return ($r + $c) % 2 === 0;
                case 1: return $r % 2 === 0;
                case 2: return $c % 3 === 0;
                case 3: return ($r + $c) % 3 === 0;
                case 4: return ((int) ($r / 2) + (int) ($c / 3)) % 2 === 0;
                case 5: return (($r * $c) % 2 + ($r * $c) % 3) === 0;
                case 6: return ((($r * $c) % 2 + ($r * $c) % 3) % 2) === 0;
                default: return ((($r + $c) % 2 + ($r * $c) % 3) % 2) === 0;
            }
        }

        private static function applyMask(array &$m, array $func, int $mask): void
        {
            $n = count($m);
            for ($r = 0; $r < $n; $r++) {
                for ($c = 0; $c < $n; $c++) {
                    if ($func[$r][$c] || $m[$r][$c] === -1 || !self::maskBit($r, $c, $mask)) {
                        continue;
                    }
                    $m[$r][$c] ^= 1;
                }
            }
        }

        private static function pickMask(array $m, array $func, int $version, string $ec): int
        {
            $best = 0;
            $bestScore = PHP_INT_MAX;
            for ($mask = 0; $mask < 8; $mask++) {
                $cand = $m;
                self::applyMask($cand, $func, $mask);
                self::placeFormatInfo($cand, self::formatBits($ec, $mask));
                if ($version >= 7) {
                    self::placeVersionInfo($cand, self::versionBits($version));
                }
                $score = self::penalty($cand);
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = $mask;
                }
            }
            return $best;
        }

        private static function penalty(array $m): int
        {
            $n = count($m);
            $p = 0;

            for ($r = 0; $r < $n; $r++) {
                $run = 1;
                for ($c = 1; $c <= $n; $c++) {
                    if ($c < $n && $m[$r][$c] === $m[$r][$c - 1]) {
                        $run++;
                    } else {
                        if ($run >= 5) {
                            $p += 3 + ($run - 5);
                        }
                        $run = 1;
                    }
                }
            }
            for ($c = 0; $c < $n; $c++) {
                $run = 1;
                for ($r = 1; $r <= $n; $r++) {
                    if ($r < $n && $m[$r][$c] === $m[$r - 1][$c]) {
                        $run++;
                    } else {
                        if ($run >= 5) {
                            $p += 3 + ($run - 5);
                        }
                        $run = 1;
                    }
                }
            }

            for ($r = 0; $r < $n - 1; $r++) {
                for ($c = 0; $c < $n - 1; $c++) {
                    $v = $m[$r][$c];
                    if ($v === $m[$r][$c + 1] && $v === $m[$r + 1][$c] && $v === $m[$r + 1][$c + 1]) {
                        $p += 3;
                    }
                }
            }

            $pat = [1, 0, 1, 1, 1, 0, 1];
            $patLen = 7;
            $find = [$pat, array_reverse($pat)];
            for ($r = 0; $r < $n; $r++) {
                for ($c = 0; $c <= $n - $patLen; $c++) {
                    foreach ($find as $t) {
                        $match = true;
                        for ($k = 0; $k < $patLen; $k++) {
                            if ($m[$r][$c + $k] !== $t[$k]) {
                                $match = false;
                                break;
                            }
                        }
                        if ($match && ($c < 4 || $m[$r][$c - 4] === 0) && ($c + $patLen + 3 >= $n || $m[$r][$c + $patLen + 3] === 0)) {
                            $p += 40;
                        }
                    }
                }
            }
            for ($c = 0; $c < $n; $c++) {
                for ($r = 0; $r <= $n - $patLen; $r++) {
                    foreach ($find as $t) {
                        $match = true;
                        for ($k = 0; $k < $patLen; $k++) {
                            if ($m[$r + $k][$c] !== $t[$k]) {
                                $match = false;
                                break;
                            }
                        }
                        if ($match && ($r < 4 || $m[$r - 4][$c] === 0) && ($r + $patLen + 3 >= $n || $m[$r + $patLen + 3][$c] === 0)) {
                            $p += 40;
                        }
                    }
                }
            }

            $dark = 0;
            for ($r = 0; $r < $n; $r++) {
                for ($c = 0; $c < $n; $c++) {
                    if ($m[$r][$c] === 1) {
                        $dark++;
                    }
                }
            }
            $pct = ($dark * 100) / ($n * $n);
            $prev5 = (int) floor($pct / 5) * 5;
            $d = min(abs($pct - $prev5), abs($pct - ($prev5 + 5)));
            $p += (int) ($d / 5) * 10;

            return $p;
        }

        private static function formatBits(string $ec, int $mask): int
        {
            $data = (self::EC_BITS[$ec] << 3) | $mask;
            $rem = $data << 10;
            for ($i = 14; $i >= 10; $i--) {
                if (($rem >> $i) & 1) {
                    $rem ^= self::G_FORMAT << ($i - 10);
                }
            }
            return (($data << 10) | $rem) ^ self::FORMAT_MASK;
        }

        private static function placeFormatInfo(array &$m, int $bits): void
        {
            $n = count($m);
            $v = 0;
            $h = 0;
            for ($i = 0; $i < 8; $i++) {
                $vbit = ($bits >> $i) & 1;
                $hbit = ($bits >> (14 - $i)) & 1;
                if ($i === 6) {
                    $v++;
                    $h = 1;
                }
                $m[$i + $v][8] = $vbit;          // vertikal kiri-atas
                $m[8][$i + $h] = $hbit;          // horizontal kiri-atas
                $m[8][$n - 1 - $i] = $vbit;      // horizontal kanan-atas
                $m[$n - 1 - $i][8] = $hbit;      // vertikal kiri-bawah
            }
            $m[$n - 8][8] = 1; // dark module
        }

        private static function versionBits(int $version): int
        {
            $rem = $version << 12;
            for ($i = 17; $i >= 12; $i--) {
                if (($rem >> $i) & 1) {
                    $rem ^= self::G_VERSION << ($i - 12);
                }
            }
            return ($version << 12) | $rem;
        }

        private static function placeVersionInfo(array &$m, int $bits): void
        {
            $n = count($m);
            for ($i = 0; $i < 18; $i++) {
                $bit = ($bits >> $i) & 1;
                $m[(int) ($i / 3)][($i % 3) + $n - 8 - 3] = $bit;
                $m[($i % 3) + $n - 8 - 3][(int) ($i / 3)] = $bit;
            }
        }
    }
}

if (!function_exists('qr_matrix')) {
    /**
     * Enkode string menjadi matriks QR Code (baris[] kolom[] 0/1).
     */
    function qr_matrix(string $data, string $ec = 'M'): array
    {
        return QrEncoder::matrix($data, $ec);
    }
}