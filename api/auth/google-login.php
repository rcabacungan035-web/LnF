<?php

session_start();

require_once __DIR__ . "/../config/google.php";

/*
 * Generate a unique state value.
 * This protects the OAuth login from CSRF attacks.
 */
$state = bin2hex(random_bytes(32));

$_SESSION["oauth_state"] = $state;

$client->setState($state);

$authUrl = $client->createAuthUrl();

header("Location: " . $authUrl);
exit;