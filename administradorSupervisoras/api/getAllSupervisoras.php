<?php
require_once __DIR__ . '/../config/api_helpers.php';

try {
    // Trae todas las supervisoras (activas e inactivas) con conteo de sucursales asignadas
    $rows = dbFetchAll(
        "SELECT
             sup.ID,
             sup.NOMBRE,
             sup.MAIL,
             sup.ACTIVA,
             COUNT(s.NRO_SUCURSAL) AS CANT_SUCURSALES
         FROM [dbo].RO_T_SUPERVISORAS_COMERCIAL sup
         LEFT JOIN [dbo].SUCURSALES_LAKERS s
             ON s.ID_SUPERVISORA = sup.ID
             AND s.HABILITADO = 1
             AND s.CANAL = 'PROPIOS'
         GROUP BY sup.ID, sup.NOMBRE, sup.MAIL, sup.ACTIVA
         ORDER BY sup.NOMBRE"
    );

    jsonResponse(array_map(function ($row) {
        $row['ID']             = (int)$row['ID'];
        $row['ACTIVA']         = (int)$row['ACTIVA'];
        $row['CANT_SUCURSALES'] = (int)$row['CANT_SUCURSALES'];
        return $row;
    }, $rows));

} catch (Throwable $e) {
    error_log('[getAllSupervisoras] ' . $e->getMessage());
    jsonError('Error al obtener supervisoras.');
}
