<?php
require_once __DIR__ . '/env.php';

/**
 * Retorna una conexión PDO a SQL Server.
 * Lanza una excepción si la conexión falla.
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host   = getenv('DB_HOST') ?: '';
    $dbName = getenv('DB_NAME') ?: '';
    $user   = getenv('DB_USER') ?: '';
    $pass   = getenv('DB_PASS') ?: '';

    if (!$host || !$dbName || !$user) {
        throw new RuntimeException('Variables de entorno de base de datos no configuradas.');
    }

    $dsn = "sqlsrv:Server=$host;Database=$dbName;TrustServerCertificate=1";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // No exponer detalles de conexión al cliente
        error_log('[CADS DB Error] ' . $e->getMessage());
        throw new RuntimeException('No se pudo conectar a la base de datos. Verifique la configuración.');
    }

    return $pdo;
}

/**
 * Helper: ejecutar query con parámetros y devolver todos los resultados.
 */
function dbFetchAll(string $sql, array $params = []): array
{
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Helper: ejecutar query con parámetros y devolver primera fila.
 */
function dbFetchOne(string $sql, array $params = []): ?array
{
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}
