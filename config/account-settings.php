<?php
declare(strict_types=1);

// Perubahan email terpusat agar user biasa dan pengajar memakai aturan yang sama.
function change_account_email(PDO $pdo, int $userId, string $newEmail, string $currentPassword = ''): array
{
    $email = strtolower(trim($newEmail));
    if ($email === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Format email tidak valid.'];
    }

    $stmt = $pdo->prepare('SELECT email, password FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        return ['ok' => false, 'message' => 'Akun tidak ditemukan.'];
    }
    if (strcasecmp((string) $account['email'], $email) === 0) {
        return ['ok' => false, 'message' => 'Email baru sama dengan email saat ini.'];
    }
    if (!empty($account['password']) && !password_verify($currentPassword, (string) $account['password'])) {
        return ['ok' => false, 'message' => 'Password saat ini salah.'];
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $stmt->execute([$email, $userId]);
    if ($stmt->fetchColumn()) {
        return ['ok' => false, 'message' => 'Email tersebut sudah digunakan akun lain.'];
    }

    $pdo->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $userId]);
    return ['ok' => true, 'message' => 'Email berhasil diperbarui.', 'email' => $email];
}
