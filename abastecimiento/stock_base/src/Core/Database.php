<?php
// src/core/Database.php

class Database {
    private static $pdo = null;

    public static function connect() {
        if (self::$pdo === null) {
            // Incluye tu configuración de conexión
            // Asumo que config.php define DB_HOST, DB_NAME, DB_USER, DB_PASS
            require_once __DIR__ . '/../config.php'; 
            
            try {
                $dsn = "sqlsrv:Server=" . DB_HOST . ";Database=" . DB_NAME;
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // En un entorno de producción, nunca muestres el error directamente.
                // Guárdalo en un log y muestra un mensaje genérico.
                die("Error de conexión a la base de datos: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}