<?php
declare(strict_types=1);

function private_storage_directory(string $type): string
{
    if (!in_array($type, ['jurnal', 'materi'], true)) {
        throw new InvalidArgumentException('Jenis penyimpanan tidak valid.');
    }
    $directory = dirname(__DIR__) . '/storage/' . $type;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Direktori penyimpanan tidak dapat dibuat.');
    }
    return $directory;
}

function private_storage_database_path(string $type, string $filename): string
{
    return 'storage/' . $type . '/' . basename($filename);
}

function private_storage_resolve(string $type, string $storedPath): string|false
{
    $filename = basename($storedPath);
    if ($filename === '' || $filename === '.' || $filename === '..') {
        return false;
    }

    $candidates = [
        dirname(__DIR__) . '/storage/' . $type . '/' . $filename,
        dirname(__DIR__) . '/assets/' . $type . '/' . $filename,
    ];
    foreach ($candidates as $candidate) {
        $resolved = realpath($candidate);
        if ($resolved !== false && is_file($resolved)) {
            return $resolved;
        }
    }
    return false;
}
