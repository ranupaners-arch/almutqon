<?php
require_once __DIR__ . '/../config/auth.php';

destroy_session();

header('Content-Type: application/json');
echo json_encode(['success' => true]);