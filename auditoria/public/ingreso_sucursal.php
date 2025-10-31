<?php
// public/ingreso_sucursal.php

session_start();

// 1. Verificamos si existe una sesión activa de XL Gestión.
if (!isset($_SESSION['username']) || !isset($_SESSION['numsuc'])) {
    die('Acceso Denegado. Debe iniciar sesión en XL Gestión primero.');
}

// 2. Leemos la información real del usuario desde la sesión de XL Gestión.
$nro_sucursal = $_SESSION['numsuc'];
$nombre_sucursal = $_SESSION['local'];
$username = $_SESSION['username'];

// 3. Creamos la sesión para nuestra aplicación con los datos reales.
// ¡OJO! Estamos creando una sesión 'user' separada para no interferir con la de XL Gestión.
$_SESSION['user'] = [
    'username' => $username,
    'name' => $nombre_sucursal, // Usamos el nombre del local que ya viene en la sesión
    'role' => 'sucursal',
    'nro_sucursal' => $nro_sucursal // ¡Guardamos el número de sucursal!
];

// 4. Redirigimos a la página de inicio de la aplicación de auditoría.
header('Location: /comercial/auditoria/');
exit();