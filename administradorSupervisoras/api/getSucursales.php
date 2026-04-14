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
         FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS s
         LEFT JOIN [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL sup
             ON sup.ID = s.ID_SUPERVISORA
         WHERE s.HABILITADO = 1
           AND s.CANAL = 'PROPIOS'
         ORDER BY s.DESC_SUCURSAL"
    );

    jsonResponse($rows);

} catch (Throwable $e) {
    error_log('[getSucursales] ' . $e->getMessage());
    jsonError('Error al obtener sucursales.');
}
