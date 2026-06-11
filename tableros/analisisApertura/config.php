<?php
// tableros/analisisApertura/config.php
// Punto único de configuración — al mover la carpeta, solo tocar este archivo.

define('AA_BASE_PATH',        __DIR__);

// ─── URLs públicas ────────────────────────────────────────────────────────────
define('AA_HOME_URL',         'http://192.168.0.13:8000/');
define('AA_AJAX_BASE',        'Controller/');

// ─── Paths del filesystem ─────────────────────────────────────────────────────
define('AA_CONEXION_PATH',    $_SERVER['DOCUMENT_ROOT'] . '/comercial/class/conexion.php');
define('AA_SUCURSAL_PATH',    $_SERVER['DOCUMENT_ROOT'] . '/comercial/tableros/costoPersonal/Class/Sucursal.php');

// ─── Parámetros de dominio ────────────────────────────────────────────────────
define('AA_FECHA_MIN',        '2025-01-01');
define('AA_SUCURSAL_DEFAULT', 72);
