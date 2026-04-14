<?php
require_once __DIR__ . '/../config/api_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido.', 405);
}

$body = json_decode(file_get_contents('php://input'), true);

$nroSucursal    = sanitizeInt((string)($body['nro_sucursal'] ?? ''));
$idSupervisora  = isset($body['id_supervisora']) && $body['id_supervisora'] !== '' && $body['id_supervisora'] !== null
    ? sanitizeInt((string)$body['id_supervisora'])
    : 0; // 0 = sin asignar

if ($nroSucursal === null) {
    jsonError('Número de sucursal inválido.', 400);
}

try {
    // Verificar que la sucursal existe
    $suc = dbFetchOne(
        "SELECT NRO_SUCURSAL FROM [dbo].SUCURSALES_LAKERS
         WHERE NRO_SUCURSAL = ? AND HABILITADO = 1 AND CANAL = 'PROPIOS'",
        [$nroSucursal]
    );

    if (!$suc) {
        jsonError('Sucursal no encontrada.', 404);
    }

    // Verificar que la supervisora existe (si se asigna una)
    if ($idSupervisora !== 0) {
        $sup = dbFetchOne(
            "SELECT ID FROM [dbo].RO_T_SUPERVISORAS_COMERCIAL
             WHERE ID = ? AND ACTIVA = 1",
            [$idSupervisora]
        );

        if (!$sup) {
            jsonError('Supervisora no encontrada.', 404);
        }
    }

    $nuevoValor = ($idSupervisora === 0) ? null : $idSupervisora;

    dbExecute(
        "UPDATE [dbo].SUCURSALES_LAKERS
         SET ID_SUPERVISORA = ?
         WHERE NRO_SUCURSAL = ?",
        [$nuevoValor, $nroSucursal]
    );

    jsonResponse(['success' => true, 'message' => 'Supervisora actualizada correctamente.']);

} catch (Throwable $e) {
    error_log('[updateSupervisora] ' . $e->getMessage());
    jsonError('Error al actualizar la supervisora.');
}
