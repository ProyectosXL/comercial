<?php
// src/config.php

require_once __DIR__ . '/../classEnv.php';

try {
    new DotEnv(__DIR__ . '/../../../../');
} catch (InvalidArgumentException $e) {
    die("Error Crítico: " . $e->getMessage());
}
define('DB_HOST', getenv('HOST_APPS'));        // Toma el valor de HOST_CENTRAL
define('DB_NAME', getenv('DATABASE_APPS'));    // Toma el valor de DATABASE_CENTRAL
define('DB_USER', getenv('USER'));                // Toma el valor de USER
define('DB_PASS', getenv('PASS'));                // Toma el valor de PASS
