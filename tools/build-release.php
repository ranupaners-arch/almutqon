<?php
declare(strict_types=1);

require_once __DIR__ . '/cli-only.php';

$root = dirname(__DIR__);
$stamp = date('Ymd-His');
$target = $root . '/release/almutqon-' . $stamp;
$directories = ['admin', 'api', 'assets', 'auth', 'components', 'config', 'css', 'database', 'js', 'layouts', 'pages', 'pengajar', 'storage'];
$files = ['.env.example', '.htaccess', 'index.php', 'README.md', 'DEPLOYMENT.md'];

function release_copy_tree(string $source, string $destination): void
{
    global $root;
    if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
        throw new RuntimeException('Tidak dapat membuat ' . $destination);
    }
    $iterator = new FilesystemIterator($source, FilesystemIterator::SKIP_DOTS);
    foreach ($iterator as $item) {
        $name = $item->getFilename();
        $destinationPath = $destination . '/' . $name;
        if ($item->isDir()) {
            if ($source === $root . '/assets' && in_array($name, ['avatars', 'jurnal', 'materi', 'uploads'], true)) {
                continue;
            }
            if (str_starts_with($source, dirname(__DIR__) . '/storage') && $name === 'backups') {
                mkdir($destinationPath, 0755, true);
                continue;
            }
            release_copy_tree($item->getPathname(), $destinationPath);
            continue;
        }
        if ($item->getExtension() === 'log' || in_array($name, ['.DS_Store'], true)) {
            continue;
        }
        if (!copy($item->getPathname(), $destinationPath)) {
            throw new RuntimeException('Gagal menyalin ' . $item->getPathname());
        }
        chmod($destinationPath, 0644);
    }
}

foreach ($directories as $directory) {
    release_copy_tree($root . '/' . $directory, $target . '/' . $directory);
}
foreach ($files as $file) {
    if (is_file($root . '/' . $file)) {
        copy($root . '/' . $file, $target . '/' . $file);
    }
}

foreach (['avatars', 'uploads'] as $publicUploadDirectory) {
    $directory = $target . '/assets/' . $publicUploadDirectory;
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
}

// Migrasi memerlukan guard ini, tetapi tool operator lain sengaja tidak dikirim.
mkdir($target . '/tools', 0755, true);
copy(__DIR__ . '/cli-only.php', $target . '/tools/cli-only.php');
copy(__DIR__ . '/preflight.php', $target . '/tools/preflight.php');
copy(__DIR__ . '/.htaccess', $target . '/tools/.htaccess');

echo $target . PHP_EOL;
