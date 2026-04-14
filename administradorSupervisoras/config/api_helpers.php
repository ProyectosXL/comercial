<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
require_once __DIR__ . '/database.php';

function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $status = 500): never
{
    jsonResponse(['error' => true, 'message' => $message], $status);
}

function sanitizeInt(?string $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    $int = filter_var($value, FILTER_VALIDATE_INT);
    return ($int !== false) ? (int)$int : null;
}

function sanitizeString(?string $value, int $maxLen = 200): string
{
    if ($value === null || $value === '') {
        return '';
    }
    return substr(strip_tags(trim($value)), 0, $maxLen);
}

set_exception_handler(function (Throwable $e) {
    error_log('[AdmSupervisoras] ' . $e->getMessage());
    jsonError('Error interno del servidor.', 500);
});
