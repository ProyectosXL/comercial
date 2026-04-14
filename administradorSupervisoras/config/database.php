<?php
require_once __DIR__ . '/env.php';

/**
 * Retorna una conexión PDO al servidor central (SERVIDOR).
 * Desde allí se accede a LAKERBIS vía servidor vinculado [XL-LAKERBIS].
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('HOST_LOCALES') ?: '';
    $db   = getenv('DATABASE_LOCALES') ?: '';
    $user = getenv('USER') ?: '';
    $pass = getenv('PASS_LOCALES') ?: '';

    if (!$host || !$db || !$user) {
        throw new RuntimeException('Variables de entorno de base de datos no configuradas.');
    }

    $dsn = "sqlsrv:Server=$host;Database=$db;TrustServerCertificate=1";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log('[AdmSupervisoras DB Error] ' . $e->getMessage());
        throw new RuntimeException('No se pudo conectar a la base de datos.');
    }

    return $pdo;
}

function dbFetchAll(string $sql, array $params = []): array
{
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $stmt->closeCursor();
    return $rows;
}

function dbFetchOne(string $sql, array $params = []): ?array
{
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    return $row ?: null;
}

function dbExecute(string $sql, array $params = []): int
{
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    $stmt->closeCursor();
    return $count;
}
