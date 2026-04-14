<?php
require_once __DIR__ . '/../config/api_helpers.php';

try {
    $rows = dbFetchAll(
        "SELECT
             s.NRO_SUCURSAL,
             s.COD_CLIENT,
             s.DESC_SUCURSAL,
             s.ID_SUPERVISORA,
             sup.NOMBRE AS NOMBRE_SUPERVISORA
         FROM [dbo].SUCURSALES_LAKERS s
         LEFT JOIN [dbo].RO_T_SUPERVISORAS_COMERCIAL sup
             ON sup.ID = s.ID_SUPERVISORA
         WHERE s.HABILITADO = 1
           AND s.CANAL = 'PROPIOS'
         ORDER BY s.DESC_SUCURSAL"
    );

    jsonResponse(array_map(function ($row) {
        $row['NRO_SUCURSAL']  = (int)$row['NRO_SUCURSAL'];
        $row['ID_SUPERVISORA'] = $row['ID_SUPERVISORA'] !== null ? (int)$row['ID_SUPERVISORA'] : null;
        return $row;
    }, $rows));

} catch (Throwable $e) {
    error_log('[getSucursales] ' . $e->getMessage());
    jsonError('Error al obtener sucursales.');
}
