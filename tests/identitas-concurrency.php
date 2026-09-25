<?php
// Uji proses paralel terhadap atomic counter. Membuat satu scope uji lalu membersihkannya.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/identity.php';

if (!function_exists('pcntl_fork')) {
    fwrite(STDERR, "SKIP: ekstensi pcntl tidak tersedia.\n");
    exit(2);
}

$workers = 8;
$scope = 'test-concurrency:' . bin2hex(random_bytes(8));
$dir = sys_get_temp_dir() . '/almutqon-identity-' . bin2hex(random_bytes(6));
if (!mkdir($dir, 0700) && !is_dir($dir)) {
    throw new RuntimeException('Direktori sementara uji concurrency gagal dibuat.');
}
$pdo = null;

$children = [];
for ($i = 0; $i < $workers; $i++) {
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Fork proses uji gagal.');
    }
    if ($pid === 0) {
        try {
            $childPdo = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
            );
            $childPdo->beginTransaction();
            $value = identity_next_counter($childPdo, $scope, 9999);
            usleep(random_int(1000, 30000));
            $childPdo->commit();
            file_put_contents($dir . '/' . $i, (string) $value, LOCK_EX);
            exit(0);
        } catch (Throwable $e) {
            file_put_contents($dir . '/' . $i . '.error', $e->getMessage(), LOCK_EX);
            exit(1);
        }
    }
    $children[] = $pid;
}

$ok = true;
foreach ($children as $pid) {
    pcntl_waitpid($pid, $status);
    if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
        $ok = false;
    }
}

$values = [];
for ($i = 0; $i < $workers; $i++) {
    $file = $dir . '/' . $i;
    if (is_file($file)) {
        $values[] = (int) file_get_contents($file);
        unlink($file);
    }
    $errorFile = $dir . '/' . $i . '.error';
    if (is_file($errorFile)) {
        fwrite(STDERR, "Worker {$i}: " . file_get_contents($errorFile) . "\n");
        unlink($errorFile);
    }
}
rmdir($dir);
$cleanupPdo = new PDO(
    "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);
$cleanupPdo->prepare('DELETE FROM identity_sequences WHERE scope = ?')->execute([$scope]);

sort($values);
$unique = array_values(array_unique($values));
$expected = range(1, $workers);
$passed = $ok && $values === $expected && $unique === $values;
echo $passed
    ? "PASS concurrency: {$workers} proses menghasilkan sequence unik 1-{$workers}\n"
    : 'FAIL concurrency: values=' . json_encode($values) . "\n";
exit($passed ? 0 : 1);
