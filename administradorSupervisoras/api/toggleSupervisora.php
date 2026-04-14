<?php
require_once __DIR__ . '/../config/api_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido.', 405);
}

$body = json_decode(file_get_contents('php://input'), true);
$id   = sanitizeInt((string)($body['id'] ?? ''));

if ($id === null) {
    jsonError('ID inválido.', 400);
}

try {
    $sup = dbFetchOne(
        "SELECT ID, ACTIVA FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL WHERE ID = ?",
        [$id]
    );

    if (!$sup) {
        jsonError('Supervisora no encontrada.', 404);
    }

    $nuevoEstado = $sup['ACTIVA'] ? 0 : 1;

    // Si se va a deshabilitar, advertir si tiene sucursales asignadas (pero permitirlo)
    $cantSucursales = 0;
    if ($nuevoEstado === 0) {
        $row = dbFetchOne(
            "SELECT COUNT(*) AS CANT
             FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS
             WHERE ID_SUPERVISORA = ? AND HABILITADO = 1 AND CANAL = 'PROPIOS'",
            [$id]
        );
        $cantSucursales = (int)($row['CANT'] ?? 0);
    }

    dbExecute(
        "UPDATE [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL
         SET ACTIVA = ?
         WHERE ID = ?",
        [$nuevoEstado, $id]
    );

    $msg = $nuevoEstado
        ? 'Supervisora habilitada.'
        : 'Supervisora deshabilitada.' . ($cantSucursales > 0 ? " Tiene $cantSucursales sucursal(es) aún asignada(s)." : '');

    jsonResponse([
        'success'         => true,
        'nuevo_estado'    => $nuevoEstado,
        'cant_sucursales' => $cantSucursales,
        'message'         => $msg,
    ]);

} catch (Throwable $e) {
    error_log('[toggleSupervisora] ' . $e->getMessage());
    jsonError('Error al cambiar el estado de la supervisora.');
}
