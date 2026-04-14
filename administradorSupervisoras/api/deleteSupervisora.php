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
        "SELECT ID, NOMBRE FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL WHERE ID = ?",
        [$id]
    );

    if (!$sup) {
        jsonError('Supervisora no encontrada.', 404);
    }

    // Bloquear eliminación si tiene sucursales asignadas
    $row = dbFetchOne(
        "SELECT COUNT(*) AS CANT
         FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS
         WHERE ID_SUPERVISORA = ? AND HABILITADO = 1 AND CANAL = 'PROPIOS'",
        [$id]
    );
    $cant = (int)($row['CANT'] ?? 0);

    if ($cant > 0) {
        jsonError("No se puede eliminar: tiene $cant sucursal(es) asignada(s). Reasignelas primero.", 409);
    }

    dbExecute(
        "DELETE FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL WHERE ID = ?",
        [$id]
    );

    jsonResponse(['success' => true, 'message' => 'Supervisora eliminada correctamente.']);

} catch (Throwable $e) {
    error_log('[deleteSupervisora] ' . $e->getMessage());
    jsonError('Error al eliminar la supervisora.');
}
