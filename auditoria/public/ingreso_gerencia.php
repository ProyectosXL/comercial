<?php
session_start();

// Creamos la sesión para el rol 'gerencia'
$_SESSION['user'] = [
    'username' => 'gerencia_user',
    'name' => 'Gerencia Comercial',
    'role' => 'gerencia'
];

// Redirigimos a la página de inicio de la aplicación
header('Location: /comercial/auditoria/');
exit();