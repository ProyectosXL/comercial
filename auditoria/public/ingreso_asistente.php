<?php
session_start();

// Creamos la sesión para el rol 'asistente'
$_SESSION['user'] = [
    'username' => 'asistente_user',
    'name' => 'Asistente Comercial',
    'role' => 'asistente'
];

// Redirigimos a la página de inicio de la aplicación
header('Location: /comercial/auditoria/');
exit();