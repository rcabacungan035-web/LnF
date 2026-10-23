<?php

require_once __DIR__ . "/env.php";
require_once __DIR__ . "/../../vendor/autoload.php";

$client = new Google\Client();

$client->setClientId(muEnv('GOOGLE_CLIENT_ID'));
$client->setClientSecret(muEnv('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(muEnv('GOOGLE_REDIRECT_URI'));

$client->setScopes([
    "openid",
    "email",
    "profile"
]);

$client->setAccessType("offline");
$client->setPrompt("select_account");