<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = getopt('', ['output-dir:', 'stamp:']);
$outputDir = (string) ($options['output-dir'] ?? '');
$stamp = (string) ($options['stamp'] ?? date('Ymd-His'));
if ($outputDir === '' || !is_dir($outputDir) || !preg_match('/^[0-9]{8}-[0-9]{6}$/', $stamp)) {
    fwrite(STDERR, "Gunakan --output-dir=/path --stamp=YYYYMMDD-HHMMSS\n");
    exit(2);
}

require_once dirname(__DIR__) . '/config/database.php';

$verifEnv = parse_ini_file(dirname(__DIR__, 2) . '/verif.almutqon/.env', false, INI_SCANNER_RAW);
if (!is_array($verifEnv)) throw new RuntimeException('Konfigurasi database verifikasi tidak ditemukan.');
$verif = new PDO(
    'mysql:host=' . ($verifEnv['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($verifEnv['DB_PORT'] ?? '3306') . ';dbname=' . ($verifEnv['DB_DATABASE'] ?? 'db_almutqon-verif') . ';charset=utf8mb4',
    (string) ($verifEnv['DB_USERNAME'] ?? 'root'),
    (string) ($verifEnv['DB_PASSWORD'] ?? ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function sql_literal(PDO $connection, mixed $value): string
{
    if ($value === null) return 'NULL';
    return $connection->quote((string) $value);
}

function dump_database(PDO $connection, string $path): array
{
    $handle = fopen($path, 'xb');
    if ($handle === false) throw new RuntimeException("Tidak dapat membuat {$path}");
    $counts = [];
    try {
        fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        $tables = $connection->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $table)) throw new RuntimeException('Nama tabel tidak aman.');
            $create = $connection->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM);
            fwrite($handle, 'DROP TABLE IF EXISTS `' . $table . "`;\n" . $create[1] . ";\n\n");
            $rows = $connection->query('SELECT * FROM `' . $table . '`');
            $count = 0;
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_map(static fn(string $column): string => '`' . $column . '`', array_keys($row));
                $values = array_map(static fn(mixed $value): string => sql_literal($connection, $value), array_values($row));
                fwrite($handle, 'INSERT INTO `' . $table . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n");
                $count++;
            }
            fwrite($handle, "\n");
            $counts[(string) $table] = $count;
        }
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    } finally {
        fclose($handle);
    }
    return $counts;
}

$websitePath = rtrim($outputDir, '/') . "/almutqon-before-reset-{$stamp}.sql";
$verifPath = rtrim($outputDir, '/') . "/verif-almutqon-before-reset-{$stamp}.sql";
$summary = [
    'website' => ['path' => $websitePath, 'tables' => dump_database($pdo, $websitePath)],
    'verif' => ['path' => $verifPath, 'tables' => dump_database($verif, $verifPath)],
];
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
