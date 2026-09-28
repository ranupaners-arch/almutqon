<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/tools/cli-only.php';
require_once dirname(__DIR__) . '/config/database.php';

$migrations = array_merge(
    glob(__DIR__ . '/baseline/*.php') ?: [],
    glob(__DIR__ . '/migrations/*.php') ?: []
);
sort($migrations, SORT_STRING);

$dryRun = in_array('--dry-run', $argv, true);
$statusOnly = in_array('--status', $argv, true);
$migrationTableExists = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'schema_migrations'"
)->fetchColumn() === 1;
$applied = $migrationTableExists
    ? array_fill_keys($pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN), true)
    : [];
$pending = [];

foreach ($migrations as $path) {
    $name = basename($path);
    $done = isset($applied[$name]);
    echo sprintf("[%s] %s\n", $done ? 'done' : 'pending', $name);
    if (!$done) {
        $pending[] = $path;
    }
}

if ($statusOnly || $dryRun) {
    echo count($pending) . " migrasi belum dijalankan.\n";
    exit(0);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    migration VARCHAR(190) NOT NULL PRIMARY KEY,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

foreach ($pending as $path) {
    $name = basename($path);
    echo "Menjalankan {$name}...\n";
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($path);
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "Migrasi {$name} gagal dengan kode {$exitCode}.\n");
        exit($exitCode);
    }
    $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
    $stmt->execute([$name]);
}

echo "Migrasi selesai.\n";
