<?php
// config/pengajar.php — peran tenaga pengajar + generator ID pengajar
require_once __DIR__ . '/database.php';

// Daftar peran tenaga pengajar (value => label). 'user' = peserta biasa (bukan pengajar).
function peran_pengajar_options(): array
{
    return [
        'direktur'         => 'Direktur',
        'koor_mubtadi'     => "Koordinator Marhalah Mubtadi'",
        'koor_mutawassith' => 'Koordinator Marhalah Mutawassith',
        'koor_mahir'       => 'Koordinator Marhalah Mahir',
        'koor_mutafawwiq'  => 'Koordinator Marhalah Mutafawwiq',
        'pengajar'         => 'Pengajar',
    ];
}

function peran_label(string $peran): string
{
    if ($peran === 'user') {
        return 'Peserta';
    }
    return peran_pengajar_options()[$peran] ?? $peran;
}

// Opsi nilai huruf tugas (A+ tertinggi, E terendah). '' = belum dinilai.
function nilai_huruf_options(): array
{
    return ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'E'];
}

// Tampilkan nama tanpa label ID "(PGJ-XXXX)" di belakangnya.
function nama_tanpa_id(string $nama): string
{
    return trim((string) preg_replace('/\s*\([^()]*\)$/', '', $nama));
}

// Petakan peran almutqon ke role staff verif.almutqon (sinkron ID pengajar).
// Aturan: direktur tetap direktur; koordinator/pengajar menjadi staff biasa
// (menghormati matriks izin verif: admin tak boleh mengelola direktur).
function peran_to_verif_role(string $peran): string
{
    if ($peran === 'direktur') {
        return 'direktur';
    }
    return 'staff';
}

// Buat ID pengajar unik format PGJ-XXXX (bebas duplikat walau ada yang dihapus).
function buat_id_pengajar(PDO $pdo): string
{
    for ($i = 0; $i < 50; $i++) {
        $id = 'PGJ-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id_pengajar = ?");
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $id;
        }
    }
    return 'PGJ-' . substr((string) time(), -8);
}
