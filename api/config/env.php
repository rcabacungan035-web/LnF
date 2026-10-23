<?php

$envFile = dirname(__DIR__, 2) . '/.env';

if (is_file($envFile)) {
    $values = parse_ini_file($envFile, false, INI_SCANNER_RAW);

    if ($values === false) {
        throw new RuntimeException('Unable to parse the project environment file.');
    }

    foreach ($values as $name => $value) {
        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function muEnv(string $name, ?string $default = null): string
{
    $value = getenv($name);

    if ($value === false) {
        if ($default === null) {
            throw new RuntimeException('Required environment variable is not set: ' . $name);
        }

        return $default;
    }

    return $value;
}
