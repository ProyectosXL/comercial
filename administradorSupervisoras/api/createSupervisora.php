<?php
require_once __DIR__ . '/../config/api_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido.', 405);
}

$body  = json_decode(file_get_contents('php://input'), true);
$nombre = strtoupper(trim(sanitizeString($body['nombre'] ?? '')));

if ($nombre === '') {
    jsonError('El nombre es obligatorio.', 400);
}

try {
    // Verificar que no exista ya con ese nombre
    $existe = dbFetchOne(
        "SELECT ID FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL
         WHERE UPPER(NOMBRE) = ?",
        [$nombre]
    );

    if ($existe) {
        jsonError('Ya existe una supervisora con ese nombre.', 409);
    }

    dbExecute(
        "INSERT INTO [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL
             (NOMBRE, ACTIVA)
         VALUES (?, 1)",
        [$nombre]
    );

    // Recuperar el registro recién creado
    $nueva = dbFetchOne(
        "SELECT ID, NOMBRE, ACTIVA FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].RO_T_SUPERVISORAS_COMERCIAL
         WHERE UPPER(NOMBRE) = ?",
        [$nombre]
    );

    jsonResponse(['success' => true, 'supervisora' => $nueva]);

} catch (Throwable $e) {
    error_log('[createSupervisora] ' . $e->getMessage());
    jsonError('Error al crear la supervisora.');
}
