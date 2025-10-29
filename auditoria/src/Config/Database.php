<?php
// src/Config/Database.php
// VERSIÓN FINAL Y DEFINITIVA - CORRECCIÓN DE ATRIBUTOS DE CONEXIÓN

class Database {

    private static $pdo_powerbi = null;
    private static $pdo_laker = null;
    private static $pdo_sistemas = null;

    /**
     * Crea una conexión PDO a SQL Server con la configuración más compatible.
     * La única opción que se establece es el modo de error, que es universal.
     */
    private static function createConnection($dsn) {
        // Opciones de conexión a prueba de balas.
        // Se elimina PDO::ATTR_EMULATE_PREPARES y PDO::ATTR_DEFAULT_FETCH_MODE de la conexión inicial.
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        try {
            // Creamos la nueva conexión PDO con las credenciales y las opciones seguras.
            return new PDO($dsn, Env::get('USER'), Env::get('PASS'), $options);
        } catch (\PDOException $e) {
            // Si la conexión falla, lanzamos una excepción clara.
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Obtiene la conexión a la base de datos POWER_BI_CONTROL.
     */
    public static function getConnectionPowerBI() {
        if (self::$pdo_powerbi === null) {
            $dsn = "sqlsrv:Server=" . Env::get('HOST_APPS') . ";Database=" . Env::get('DATABASE_POWER');
            self::$pdo_powerbi = self::createConnection($dsn);
        }
        return self::$pdo_powerbi;
    }

    /**
     * Obtiene la conexión a la base de datos LAKER_SA.
     */
    public static function getConnectionLaker() {
        if (self::$pdo_laker === null) {
            $dsn = "sqlsrv:Server=" . Env::get('HOST_CENTRAL') . ";Database=" . Env::get('DATABASE_CENTRAL');
            self::$pdo_laker = self::createConnection($dsn);
        }
        return self::$pdo_laker;
    }

    /**
     * Obtiene la conexión a la base de datos sistemas.
     */
    public static function getConnectionSistemas() {
        if (self::$pdo_sistemas === null) {
            $dsn = "sqlsrv:Server=" . Env::get('HOST_APPS') . ";Database=" . Env::get('DATABASE_APPS');
            self::$pdo_sistemas = self::createConnection($dsn);
        }
        return self::$pdo_sistemas;
    }
}