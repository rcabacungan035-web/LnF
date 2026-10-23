<?php

require_once __DIR__ . '/api/config/env.php';

return [
    'host' => muEnv('MAILTRAP_HOST'),
    'port' => muEnv('MAILTRAP_PORT'),
    'username' => muEnv('MAILTRAP_USERNAME'),
    'password' => muEnv('MAILTRAP_PASSWORD'),
    'from_email' => muEnv('MAILTRAP_FROM_EMAIL'),
    'from_name' => muEnv('SMTP_FROM_NAME', 'MU Lost & Found'),
];