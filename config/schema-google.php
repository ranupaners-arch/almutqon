<?php
// config/schema-google.php — migrasi sekali jalan untuk Google OAuth
// Jalankan: php config/schema-google.php
require_once __DIR__ . '/../config/database.php';

// 1. Kolom google_id (unik) — untuk menautkan akun Google dengan users
$existsGoogle = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'google_id'")->fetchColumn();
if (!$existsGoogle) {
    $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(150) NULL AFTER email, ADD UNIQUE KEY uq_users_google_id (google_id)");
    echo "OK: kolom `google_id` ditambahkan.\n";
} else {
    echo "leh: kolom `google_id` sudah ada.\n";
}

// 2. password menjadi nullable — akun yang dibuat lewat Google belum punya password
$fieldPass = $pdo->query("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password'")->fetchColumn();
if ($fieldPass !== 'YES') {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
    echo "OK: kolom `password` kini nullable (untuk akun Google-only).\n";
} else {
    echo "leh: kolom `password` sudah nullable.\n";
}

echo "Selesai. Skema Google OAuth siap.\n";