<?php
// src/Config/Database.php
// VERSIÓN FINAL Y DEFINITIVA - CORRECCIÓN DE ATRIBUTOS DE CONEXIÓN

class Database {

    private static $pdo_powerbi = null;
    private static $pdo_laker = null;
    private static $pdo_locales = null;
    private static $pdo_sistemas = null;

    /**
     * Crea una conexión PDO a SQL Server con la configuración más compatible.
     */
    private static function createConnection($dsn, $user = null, $pass = null) {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        // Usamos los parámetros pasados, o por defecto las variables de entorno principales
        $username = $user ?? Env::get('USER');
        $password = $pass ?? Env::get('PASS');

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
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
     * Obtiene la conexión a la base de datos locales_lakers (XL-LAKERBIS).
     */
    public static function getConnectionLocales() {
        if (self::$pdo_locales === null) {
            $dsn = "sqlsrv:Server=" . Env::get('HOST_LOCALES') . ";Database=" . Env::get('DATABASE_LOCALES');
            // Usamos específicamente el PASS_LOCALES para este servidor
            self::$pdo_locales = self::createConnection($dsn, Env::get('USER'), Env::get('PASS_LOCALES'));
        }
        return self::$pdo_locales;
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