<?php
// config/schema-tugas-nilai.php — migrasi idempoten: routing tugas ke pengajar + jejak penilai.
// - jurnal_peserta.pengajar_id : pengajar tujuan (dipilih user saat unggah), NULL = lama/tanpa tujuan
// - jurnal_peserta.dinilai_oleh / dinilai_pada : jejak siapa & kapan menilai (untuk pemantauan)
// - materi_kursus.diunggah_oleh : nama/ID pengunggah materi (admin/pengajar)
// - jurnal_peserta.nilai : nilai huruf (A+, A, A-, B+, B, B-, C+, C, D, E), NULL = belum dinilai
// - jurnal_peserta.hapus_user / hapus_pengajar : soft delete per sisi (1 = disembunyikan
//   untuk user / pengajar; sisi lain & admin tetap melihat; file fisik dipertahankan)
// Jalankan: php config/schema-tugas-nilai.php
require_once __DIR__ . '/database.php';

function tambah_kolom_nilai(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    $stmt->execute([$table, $column]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "  + {$table}.`{$column}` ditambahkan\n";
    } else {
        echo "  = {$table}.`{$column}` sudah ada\n";
    }
}

echo "Migrasi routing & jejak nilai...\n";
tambah_kolom_nilai($pdo, 'jurnal_peserta', 'pengajar_id', "INT UNSIGNED NULL DEFAULT NULL AFTER pendaftar_id");
tambah_kolom_nilai($pdo, 'jurnal_peserta', 'dinilai_oleh', "VARCHAR(100) NULL DEFAULT NULL AFTER status_review");
tambah_kolom_nilai($pdo, 'jurnal_peserta', 'dinilai_pada', "DATETIME NULL DEFAULT NULL AFTER dinilai_oleh");
tambah_kolom_nilai($pdo, 'materi_kursus', 'diunggah_oleh', "VARCHAR(100) NULL DEFAULT NULL AFTER path_file");
tambah_kolom_nilai($pdo, 'jurnal_peserta', 'nilai', "VARCHAR(5) NULL DEFAULT NULL AFTER dinilai_pada");
tambah_kolom_nilai($pdo, 'jurnal_peserta', 'hapus_user', "TINYINT(1) NOT NULL DEFAULT 0 AFTER nilai");
tambah_kolom_nilai($pdo, 'jurnal_peserta', 'hapus_pengajar', "TINYINT(1) NOT NULL DEFAULT 0 AFTER hapus_user");

echo "OK: routing tugas & jejak nilai siap.\n";
