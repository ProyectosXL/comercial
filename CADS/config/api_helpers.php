<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
require_once __DIR__ . '/database.php';

/**
 * Enviar respuesta JSON y terminar ejecución.
 */
function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

/**
 * Enviar error JSON estandarizado.
 */
function jsonError(string $message, int $status = 500): never
{
    jsonResponse(['error' => true, 'message' => $message], $status);
}

/**
 * Sanitizar string de input: solo letras, números, espacios y guiones.
 */
function sanitizeString(?string $value, int $maxLen = 100): string
{
    if ($value === null || $value === '') {
        return '';
    }
    $value = strip_tags(trim($value));
    $value = substr($value, 0, $maxLen);
    return $value;
}

/**
 * Validar y retornar fecha en formato Y-m-d, o null si inválida.
 */
function sanitizeDate(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d', trim($value));
    return ($d && $d->format('Y-m-d') === trim($value)) ? trim($value) : null;
}

/**
 * Construir cláusula WHERE + parámetros a partir de filtros comunes.
 * Columnas esperadas: franquiciado, fecha_desde/hasta, estado.
 *
 * @param string $colFranquiciado  Nombre de columna en la vista
 * @param string $colFecha         Nombre de columna de fecha en la vista
 * @param string $colEstado        Nombre de columna de estado en la vista
 */
function buildFilters(
    array   $input,
    ?string $colFranquiciado = 'Franquiciado',  // null = la vista no tiene esta columna
    ?string $colFecha        = 'FechaDevolucion', // null = la vista no tiene columna de fecha filtrable
    ?string $colEstado       = 'Estado'           // null = la vista no tiene columna de estado
): array {
    $where  = [];
    $params = [];

    $franquiciado = sanitizeString($input['franquiciado'] ?? null);
    $fechaDesde   = sanitizeDate($input['fecha_desde'] ?? null);
    $fechaHasta   = sanitizeDate($input['fecha_hasta'] ?? null);
    $estadoRaw    = $input['estado'] ?? null;

    if ($colFranquiciado !== null && $franquiciado !== '') {
        $where[]  = "$colFranquiciado = ?";
        $params[] = $franquiciado;
    }

    if ($colFecha !== null && $fechaDesde !== null) {
        $where[]  = "$colFecha >= CAST(? AS DATE)";
        $params[] = $fechaDesde;
    }

    if ($colFecha !== null && $fechaHasta !== null) {
        $where[]  = "$colFecha < DATEADD(day, 1, CAST(? AS DATE))";
        $params[] = $fechaHasta;
    }

    if ($colEstado !== null) {
        if (is_array($estadoRaw)) {
            $estados = array_values(array_filter(array_map('sanitizeString', $estadoRaw)));
            if (!empty($estados)) {
                $where[]  = "$colEstado IN (" . implode(',', array_fill(0, count($estados), '?')) . ")";
                $params   = array_merge($params, $estados);
            }
        } elseif (is_string($estadoRaw) && trim($estadoRaw) !== '') {
            $where[]  = "$colEstado = ?";
            $params[] = sanitizeString($estadoRaw);
        }
    }

    $sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    return ['sql' => $sql, 'params' => $params];
}

// Manejar excepciones no capturadas en endpoints
set_exception_handler(function (Throwable $e) {
    error_log('[CADS Exception] ' . $e->getMessage());
    jsonError('Error interno del servidor.', 500);
});
