<?php
// src/Config/Database.php
// Conexiones a las bases de datos necesarias

class Database {

    private static $pdo_sistemas = null;
    private static $pdo_tango = null;

    /**
     * Crea una conexión PDO a SQL Server.
     */
    private static function createConnection($dsn, $user = null, $pass = null) {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $username = $user ?? Env::get('USER');
        $password = $pass ?? Env::get('PASS');

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Conexión a XL-APPS -> base sistemas (para FP_SOLICITUDES_TRANSFERENCIAS)
     */
    public static function getConnectionSistemas() {
        if (self::$pdo_sistemas === null) {
            $dsn = "sqlsrv:Server=" . Env::get('HOST_APPS') . ";Database=" . Env::get('DATABASE_APPS');
            self::$pdo_sistemas = self::createConnection($dsn);
        }
        return self::$pdo_sistemas;
    }

    /**
     * Conexión a XL-TANGO -> base LAKER_SA (para artículos y stock)
     */
    public static function getConnectionTango() {
        if (self::$pdo_tango === null) {
            $dsn = "sqlsrv:Server=" . Env::get('HOST_CENTRAL') . ";Database=" . Env::get('DATABASE_CENTRAL');
            self::$pdo_tango = self::createConnection($dsn);
        }
        return self::$pdo_tango;
    }
}
