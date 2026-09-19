<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_csrf();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$theme = trim($_POST['theme'] ?? '');

if (!in_array($theme, ['light', 'dark', 'system'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nilai theme tidak valid.']);
    exit;
}

$stmt = $pdo->prepare('UPDATE users SET theme = ? WHERE id = ?');
$stmt->execute([$theme, (int) $_SESSION['user']['id']]);

$_SESSION['user']['theme'] = $theme;

echo json_encode(['success' => true, 'theme' => $theme]);