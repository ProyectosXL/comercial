<?php
require_once __DIR__ . '/../config/api_helpers.php';

try {
    $rows = dbFetchAll(
        "SELECT ID, NOMBRE
         FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL
         WHERE ACTIVA = 1
         ORDER BY NOMBRE"
    );

    jsonResponse($rows);

} catch (Throwable $e) {
    error_log('[getSupervisoras] ' . $e->getMessage());
    jsonError('Error al obtener supervisoras.');
}
