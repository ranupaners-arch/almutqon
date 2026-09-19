<?php
// pages/akun/jurnal-materi.php — URL lama "Mulai Belajar", kini dipecah per menu
// (Materi Kursus / Unggah Tugas / Riwayat Tugas Saya). Alihkan ke Materi Kursus.
// Guard login tetap: tamu → '/'.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}
header('Location: /pages/akun/materi.php');
exit;
