<?php
// Migrasi preferensi tema pengguna.
require_once dirname(__DIR__, 2) . '/tools/cli-only.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

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
