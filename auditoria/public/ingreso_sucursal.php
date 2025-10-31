<?php
session_start();

// Creamos la sesión para el rol 'sucursal'
$_SESSION['user'] = [
    'username' => 'sucursal_user',
    'name' => 'Usuario Sucursal',
    'role' => 'sucursal'
];

// Redirigimos a la página de inicio de la aplicación
header('Location: /comercial/auditoria/');
exit();