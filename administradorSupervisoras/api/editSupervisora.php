<?php
require_once __DIR__ . '/../config/api_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido.', 405);
}

$body   = json_decode(file_get_contents('php://input'), true);
$id     = sanitizeInt((string)($body['id'] ?? ''));
$nombre = strtoupper(trim(sanitizeString($body['nombre'] ?? '')));

if ($id === null) {
    jsonError('ID inválido.', 400);
}
if ($nombre === '') {
    jsonError('El nombre es obligatorio.', 400);
}

try {
    // Verificar que existe
    $existe = dbFetchOne(
        "SELECT ID FROM [dbo].RO_T_SUPERVISORAS_COMERCIAL WHERE ID = ?",
        [$id]
    );
    if (!$existe) {
        jsonError('Supervisora no encontrada.', 404);
    }

    // Verificar que el nombre no lo use otra
    $duplicado = dbFetchOne(
        "SELECT ID FROM [dbo].RO_T_SUPERVISORAS_COMERCIAL
         WHERE UPPER(NOMBRE) = ? AND ID <> ?",
        [$nombre, $id]
    );
    if ($duplicado) {
        jsonError('Ya existe otra supervisora con ese nombre.', 409);
    }

    dbExecute(
        "UPDATE [dbo].RO_T_SUPERVISORAS_COMERCIAL
         SET NOMBRE = ?
         WHERE ID = ?",
        [$nombre, $id]
    );

    jsonResponse(['success' => true, 'message' => 'Nombre actualizado correctamente.']);

} catch (Throwable $e) {
    error_log('[editSupervisora] ' . $e->getMessage());
    jsonError('Error al editar la supervisora.');
}
