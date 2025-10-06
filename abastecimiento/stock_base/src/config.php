<?php
// src/config.php

// 1. Cargar la clase que maneja el archivo .env
require_once __DIR__ . '/../classEnv.php';

// 2. Crear el objeto DotEnv para cargar todas las variables de tu .env
try {
    new DotEnv(__DIR__ . '/../../../');
} catch (InvalidArgumentException $e) {
    die("Error Crítico: " . $e->getMessage());
}

// 3. MAPEO DE VARIABLES: Aquí está la magia.
//    Tomamos los valores de tu .env y los asignamos a las constantes que usa la aplicación.

// Para la conexión a la base de datos principal, usamos las variables _CENTRAL
define('DB_HOST', getenv('HOST_CENTRAL'));        // Toma el valor de HOST_CENTRAL
define('DB_NAME', getenv('DATABASE_CENTRAL'));    // Toma el valor de DATABASE_CENTRAL
define('DB_USER', getenv('USER'));                // Toma el valor de USER
define('DB_PASS', getenv('PASS'));                // Toma el valor de PASS

// Si en el futuro necesitaras conectar a otra base de datos,
// podrías definir otras constantes aquí, por ejemplo:
// define('DB_HOST_LOCALES', getenv('HOST_LOCALES'));
// define('DB_PASS_LOCALES', getenv('PASS_LOCALES'));