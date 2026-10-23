<?php

require_once __DIR__ . '/api/config/env.php';

$pdo = new PDO(
    'mysql:host=' . muEnv('DB_HOST', 'localhost') . ';dbname=' . muEnv('DB_NAME', 'mu_lost_found') . ';charset=utf8mb4',
    muEnv('DB_USER', 'root'),
    muEnv('DB_PASSWORD', ''),
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);