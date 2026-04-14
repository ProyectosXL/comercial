<?php
require_once __DIR__ . '/../config/api_helpers.php';

try {
    // Trae todas las supervisoras (activas e inactivas) con conteo de sucursales asignadas
    $rows = dbFetchAll(
        "SELECT
             sup.ID,
             sup.NOMBRE,
             sup.ACTIVA,
             COUNT(s.NRO_SUCURSAL) AS CANT_SUCURSALES
         FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL sup
         LEFT JOIN [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS s
             ON s.ID_SUPERVISORA = sup.ID
             AND s.HABILITADO = 1
             AND s.CANAL = 'PROPIOS'
         GROUP BY sup.ID, sup.NOMBRE, sup.ACTIVA
         ORDER BY sup.NOMBRE"
    );

    jsonResponse($rows);

} catch (Throwable $e) {
    error_log('[getAllSupervisoras] ' . $e->getMessage());
    jsonError('Error al obtener supervisoras.');
}
