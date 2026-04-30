<?php
// administracion/comercial/costoPersonal/config.php
// Punto único de configuración — al mover la carpeta, solo tocar este archivo.

define('CP_BASE_PATH', __DIR__);

// ─── URLs públicas (HTTP) ─────────────────────────────────────────────────────
define('CP_HOME_URL',            'http://192.168.0.13:8000/');
define('CP_AJAX_BASE',           'Controller/');    
define('CP_CAMBIAR_ENTORNO_URL', '../../tableros/costoPersonal/Controller/cambiarEntorno.php');

// ─── Paths del filesystem (PHP require_once) ──────────────────────────────────
define('CP_CONEXION_PATH', $_SERVER['DOCUMENT_ROOT'] . '/comercial/class/conexion.php');
define('CP_SUCURSAL_PATH', __DIR__ . '/../../tableros/costoPersonal/Class/Sucursal.php');
