<?php
// abastecimiento/reposicion/config.php
// Punto único de configuración del módulo — al mover la carpeta, sólo tocar este archivo.

define('REPO_BASE_PATH', __DIR__);

// ─── Paths del filesystem ─────────────────────────────────────────────────────
define('REPO_CONEXION_PATH', $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php');

// ─── URLs / rutas de la app ───────────────────────────────────────────────────
define('REPO_AJAX_BASE', 'controller/');

// ─── Parámetros de dominio ────────────────────────────────────────────────────
// Stored procedure que arma el dataset base (stock/venta por sucursal, ya filtrado depósito 82)
define('REPO_SP_DATASET_BASE', 'RO_SP_ABASTECIMIENTO_SUCURSALES_UY');

// Tabla de categoría por artículo (servidor central, conexión 'uy')
define('REPO_TABLA_CATEGORIA', '[XL-LAKERBIS].[SUCURSALES_URUGUAY].[dbo].SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA');

// Tabla de ventas diarias (servidor XL-APPS, conexión 'power_uy')
define('REPO_TABLA_VENTAS', 'dbo.RO_VENTAS_COMERCIAL');

// Ventana de venta de los últimos N días (cerrada, sin incluir el día de hoy)
define('REPO_DIAS_VENTANA_VENTA', 7);

// Timeouts de la consulta al SP (puede tardar por los EXEC remotos que ejecuta)
define('REPO_SP_QUERY_TIMEOUT', 180);
define('REPO_SCRIPT_TIME_LIMIT', 300);

// Sucursales consideradas y su mapeo a sufijo de columna
define('REPO_SUCURSALES', [
    'NUEVOCENTRO'  => 'NC',
    'MONTEVIDEO'   => 'MV',
    'TRES CRUCES'  => '3C',
]);

// Rubro que separa la solapa Packaging del resto
define('REPO_RUBRO_PACKAGING', 'PACKAGING');

// Etiqueta usada para representar valores vacíos en los filtros
define('REPO_ETIQUETA_EN_BLANCO', '(En blanco)');
