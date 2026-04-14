<?php
require_once __DIR__ . '/../config/api_helpers.php';

try {
    $rows = dbFetchAll(
        "SELECT ID, NOMBRE
         FROM [dbo].RO_T_SUPERVISORAS_COMERCIAL
         WHERE ACTIVA = 1
         ORDER BY NOMBRE"
    );

    jsonResponse(array_map(function ($row) {
        $row['ID'] = (int)$row['ID'];
        return $row;
    }, $rows));

} catch (Throwable $e) {
    error_log('[getSupervisoras] ' . $e->getMessage());
    jsonError('Error al obtener supervisoras.');
}
