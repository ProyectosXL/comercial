<?php
// Cargar las variables de entorno desde el archivo .env
function load_env($path) {
    if (!file_exists($path)) {
        // Mostramos un error más claro si no se encuentra el archivo
        die("Error crítico: No se encontró el archivo de configuración .env en la ruta esperada: " . realpath(dirname($path)) );
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// CAMBIO: Apuntamos dos niveles arriba para llegar a /COMERCIAL/
load_env(__DIR__ . '/../../../.env');

function getConexionLocales() {
    $serverName = $_ENV['HOST_LOCALES'];
    $connectionInfo = array(
        "Database" => $_ENV['DATABASE_LOCALES'],
        "UID" => $_ENV['USER'],
        "PWD" => $_ENV['PASS_LOCALES'],
        "CharacterSet" => "UTF-8"
    );
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }
    return $conn;
}

function getConexionCentral() {
    $serverName = $_ENV['HOST_CENTRAL'];
    $connectionInfo = array(
        "Database" => $_ENV['DATABASE_CENTRAL'],
        "UID" => $_ENV['USER'],
        "PWD" => $_ENV['PASS'],
        "CharacterSet" => "UTF-8"
    );
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }
    return $conn;
}

/**
 * NUEVA FUNCIÓN DE CONEXIÓN
 * Para la tabla de gestión de horarios
 */
function getConexionSistemas() {
    // Usamos las variables del .env: HOST_APPS, DATABASE_APPS
    $serverName = $_ENV['HOST_APPS']; 
    $connectionInfo = array(
        "Database" => $_ENV['DATABASE_APPS'],
        "UID" => $_ENV['USER'],
        "PWD" => $_ENV['PASS'], // Asumo que usa la misma contraseña que la central
        "CharacterSet" => "UTF-8"
    );
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }
    return $conn;
}
/**
 * CONEXIÓN A RRHH (LAKERS_CORP_SA)
 */
function getConexionCorp() {
    // Usamos el mismo servidor que la Central (192.168.0.143)
    $serverName = $_ENV['HOST_SUELDOS']; 
    $connectionInfo = array(
        "Database" => $_ENV['DATABASE_SUELDOS'], // <--- Base Nueva
        "UID" => $_ENV['USER'],
        "PWD" => $_ENV['PASS_SUELDOS'],
        "CharacterSet" => "UTF-8"
    );
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }
    return $conn;
}
?>