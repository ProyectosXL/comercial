<?php
// src/Config/Database.php

class Database {
    private static $pdo_powerbi = null;
    private static $pdo_laker = null;

    public static function getConnectionPowerBI() {
        if (self::$pdo_powerbi === null) {
            $host = Env::get('HOST_APPS'); // Usando la IP de 192.168.0.143
            $db   = Env::get('DATABASE_POWER');
            $user = Env::get('USER');
            $pass = Env::get('PASS');
            $charset = 'utf8';

            $dsn = "sqlsrv:Server=$host;Database=$db";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo_powerbi = new PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        return self::$pdo_powerbi;
    }

    public static function getConnectionLaker() {
        if (self::$pdo_laker === null) {
            $host = Env::get('HOST_CENTRAL');
            $db   = Env::get('DATABASE_CENTRAL');
            $user = Env::get('USER');
            $pass = Env::get('PASS');
            
            $dsn = "sqlsrv:Server=$host;Database=$db";
             $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo_laker = new PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        return self::$pdo_laker;
    }
        private static $pdo_sistemas = null;

    public static function getConnectionSistemas() {
        if (self::$pdo_sistemas === null) {
            $host = Env::get('HOST_APPS'); // 192.168.0.143
            $db   = Env::get('DATABASE_APPS'); // 'sistemas'
            $user = Env::get('USER');
            $pass = Env::get('PASS');
            
            $dsn = "sqlsrv:Server=$host;Database=$db";
             $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo_sistemas = new PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        return self::$pdo_sistemas;
    }
}