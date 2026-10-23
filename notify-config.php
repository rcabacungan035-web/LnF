<?php

require_once __DIR__ . '/api/config/env.php';

return [
    'smtp' => [
        'host'       => muEnv('SMTP_HOST'),
        'port'       => muEnv('SMTP_PORT'),
        'username'   => muEnv('SMTP_USERNAME'),
        'password'   => muEnv('SMTP_PASSWORD'),
        'from_email' => muEnv('SMTP_FROM_EMAIL'),
        'from_name'  => muEnv('SMTP_FROM_NAME', 'MU Lost & Found'),
    ],

    // Table at column kung saan makukuha ang email ng mga nakarehistro
   'recipients' => [
    ['table' => 'student', 'email_col' => 'email'],
    ['table' => 'users',   'email_col' => 'email'],
    ['table' => 'admin',   'email_col' => 'Email'],
],
    'site_url'   => muEnv('APP_URL'),
    'batch_size' => (int) muEnv('NOTIFY_BATCH_SIZE', '40'),
];