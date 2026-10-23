<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$password = rtrim(stream_get_contents(STDIN), "\r\n");

if ($password === '') {
    fwrite(STDERR, "Password cannot be empty.\n");
    exit(1);
}

require_once __DIR__ . '/api/config/database.php';

$stmt = $pdo->prepare('UPDATE admin SET password = :password WHERE admin_id = 1');
$stmt->execute([
    ':password' => password_hash($password, PASSWORD_DEFAULT)
]);

if ($stmt->rowCount() === 0) {
    $stmt = $pdo->query('SELECT admin_id FROM admin WHERE admin_id = 1 LIMIT 1');
    if (!$stmt->fetch()) {
        fwrite(STDERR, "Admin account with ID 1 was not found.\n");
        exit(1);
    }
}

echo "Admin password reset successfully.\n";
