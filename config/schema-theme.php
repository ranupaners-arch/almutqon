<?php
// config/schema-theme.php — migrasi sekali jalan: tambah kolom theme di tabel users
// Jalankan: php config/schema-theme.php
require_once __DIR__ . '/../config/database.php';

$exists = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'theme'")->fetchColumn();

if (!$exists) {
    $pdo->exec("ALTER TABLE users ADD COLUMN theme ENUM('light','dark') NOT NULL DEFAULT 'light' AFTER avatar");
}

// Tambah opsi 'system' (ikuti preferensi OS) bila belum ada.
$enum = (string) $pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'theme'")->fetchColumn();
if ($enum !== '' && strpos($enum, "'system'") === false) {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN theme ENUM('light','dark','system') NOT NULL DEFAULT 'light'");
}

echo "OK: kolom `theme` pada users siap.\n";