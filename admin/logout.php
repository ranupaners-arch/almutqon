<?php
require_once __DIR__ . '/../config/auth.php';

destroy_session();

header('Location: login.php');
exit;