<?php

require_once __DIR__ . '/env.php';

$host = muEnv('DB_HOST', 'localhost');
$dbname = muEnv('DB_NAME', 'mu_lost_found');
$username = muEnv('DB_USER', 'root');
$password = muEnv('DB_PASSWORD', '');

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    die(
        "Database connection failed: " .
        htmlspecialchars($e->getMessage())
    );

}