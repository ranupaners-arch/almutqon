<?php
// auth/logout-pengajar.php — keluar dashboard pengajar (JSON, tanpa redirect).
require_once __DIR__ . '/../config/auth.php';

unset($_SESSION['pengajar']);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
