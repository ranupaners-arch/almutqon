<?php
require_once __DIR__ . '/../tools/cli-only.php';

require __DIR__ . '/../config/database.php';
$pdo->exec("DELETE FROM articles WHERE judul LIKE 'UJI-%'");
$rows = [
    ['UJI-1 Cara Efektif Menghafal Mufradat', 'Tim Almutqon', 'Tips Belajar', '<p>Menghafal kosakata bahasa Arab tidak harus membosankan. Kuncinya ada pada pengulangan berjarak dan penggunaan kata dalam kalimat nyata.</p><h2>Mulai dari yang paling sering dipakai</h2><p>Fokus pada 10 kata baru per hari, lalu rangkai dalam percakapan sederhana. Catat di buku khusus mufradat agar mudah diulang.</p>', 30],
    ['UJI-2 Kabar Gembira: Kelas Baru Dibuka', 'Admin Almutqon', 'Kabar', '<p>Kami membuka pendaftaran kelas baru untuk semester ini. Kuota terbatas, silakan hubungi admin untuk informasi jadwal dan biaya.</p><p>Sistem belajar privat dan berkelompok tetap tersedia sesuai kebutuhan santri.</p>', 27],
    ['UJI-3 Mengenal Qawaid Dasar', 'Tim Almutqon', 'Pendidikan', '<p>Qawaid (nahwu dan shorof) adalah pondasi untuk memahami struktur kalimat Arab. Tanpa qawaid, terjemahan sering meleset.</p><p>Mulailah dari pengenalan isim, fiil, dan huruf sebelum melangkah ke bab-bab yang lebih dalam.</p>', 26],
    ['UJI-4 Program Dauroh Ramadan', 'Panitia Almutqon', 'Kabar', '<p>Dauroh intensif akan diadakan sepanjang Ramadan. Materi mencakup tahsin, tafsir singkat, dan praktik i\'rab sederhana.</p><p>Pendaftaran dibuka online dan offline. Jangan lupa catat tanggalnya!</p>', 23],
    ['UJI-5 Lima Adab Menuntut Ilmu', 'Ustadz Almutqon', 'Pendidikan', '<p>Adab lebih tinggi kedudukannya daripada ilmu. Sebelum belajar makin dalam, tanamkan niat ikhlas, menghormati guru, dan bersungguh-sungguh.</p><p>Rasulullah shallallahu alaihi wasallam mengajarkan bahwa ilmu yang bermanfaat berawal dari adab yang baik.</p>', 20],
    ['UJI-6 Tips Membaca Kitab Kuning untuk Pemula', 'Tim Almutqon', 'Tips Belajar', '<p>Mulailah dari kitab kecil dengan syakal lengkap. Biasakan membaca sambil menerjemah kosa kata, bukan sekadar membaca cepat.</p><p>Gunakan metode tanya jawab setiap pekan untuk mengukur pemahaman.</p>', 18],
    ['UJI-7 Pengumuman Libur Hari Santri', 'Admin Almutqon', 'Kabar', '<p>Kegiatan belajar diliburkan pada peringatan Hari Santri. Kegiatan akan kembali berjalan sesuai jadwal setelahnya.</p><p>Semoga kita semua terus semangat dalam menuntut ilmu.</p>', 15],
    ['UJI-8 Mengenal Huruf Hijaiyah dan Makhorijul Huruf', 'Tim Almutqon', 'Pendidikan', '<p>Makhorijul huruf adalah tempat keluarnya huruf saat diucapkan. Benarnya makhraj sangat menentukan bacaan Al-Quran.</p><p>Latihan bersama guru tahsin adalah cara terbaik untuk menjaga kualitas bacaan.</p>', 12],
    ['UJI-9 Kiat Mengatur Waktu Antara Sekolah dan Ngaji', 'Tim Almutqon', 'Tips Belajar', '<p>Gunakan prinsip prioritas: selesaikan tugas utama, lalu sisihkan waktu tetap untuk ngaji. Konsistensi lebih baik daripada durasi panjang.</p><p>Buat jadwal harian sederhana dan disiplin menjalaninya.</p>', 9],
    ['UJI-10 Laporan Kegiatan Belajar Minggu Ini', 'Admin Almutqon', 'Pendidikan', '<p>Kegiatan belajar minggu ini berjalan lancar. Santri baru mulai dikenalkan dengan metode pembelajaran privat dan berkelompok.</p><p>Terima kasih kepada para ustadz dan orang tua atas dukungannya.</p>', 3],
];
$ins = $pdo->prepare("INSERT INTO articles (judul, author, kategori, gambar, konten, tanggal) VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))");
$n = 0;
foreach ($rows as $r) {
    $ins->execute([$r[0], $r[1], $r[2], '', $r[3], $r[4]]);
    $n++;
}
echo "inserted: $n\n";
foreach ($pdo->query('SELECT kategori, COUNT(*) c FROM articles GROUP BY kategori ORDER BY c DESC') as $r) {
    printf("%s: %d\n", $r['kategori'], $r['c']);
}
echo 'total: ' . $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn() . "\n";
