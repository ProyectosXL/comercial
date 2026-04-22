<?php
require_once __DIR__ . '/classEnv.php';
Env::load(__DIR__ . '/../../.env');

try {
    $dsn = "sqlsrv:Server=" . Env::get('HOST_APPS') . ";Database=" . Env::get('DATABASE_APPS');
    $pdo = new PDO($dsn, Env::get('USER'), Env::get('PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='FP_SOLICITUDES_TRANSFERENCIAS_CONFIG' and xtype='U')
            CREATE TABLE FP_SOLICITUDES_TRANSFERENCIAS_CONFIG (
                ID INT IDENTITY(1,1) PRIMARY KEY,
                CATEGORIA VARCHAR(50), -- 'TIPO_TRANSFERENCIA', 'USUARIO', 'SUCURSAL'
                VALOR VARCHAR(100),
                ACTIVO BIT DEFAULT 1
            )";
    
    $pdo->exec($sql);

    // Insertar valores iniciales si la tabla está vacía
    $checkSql = "SELECT COUNT(*) FROM FP_SOLICITUDES_TRANSFERENCIAS_CONFIG";
    $count = $pdo->query($checkSql)->fetchColumn();

    if ($count == 0) {
        $initialData = [
            // Tipos de Transferencia
            ['TIPO_TRANSFERENCIA', 'Transferencias'],
            ['TIPO_TRANSFERENCIA', 'Retiros y Obsequios'],
            ['TIPO_TRANSFERENCIA', 'Muestras'],
            ['TIPO_TRANSFERENCIA', 'Fotos/Prensa'],
            
            // Usuarios
            ['USUARIO', 'Ramiro'], ['USUARIO', 'Eduardo'], ['USUARIO', 'Valeria'],
            ['USUARIO', 'Juliana'], ['USUARIO', 'Carolina'], ['USUARIO', 'Andrea'],
            ['USUARIO', 'Magali'], ['USUARIO', 'Barbara'], ['USUARIO', 'Claudia'],
            ['USUARIO', 'Facundo'], ['USUARIO', 'Montana'], ['USUARIO', 'Analia'],
            ['USUARIO', 'Felicitas'], ['USUARIO', 'Tamara'], ['USUARIO', 'Barbara G'],
            ['USUARIO', 'Veronica'],

            // Sucursales
            ['SUCURSAL', '01-Central'], ['SUCURSAL', '02-Mayorista'], ['SUCURSAL', '03-Reserva'],
            ['SUCURSAL', '04-Outlet'], ['SUCURSAL', '07-Retiros'], ['SUCURSAL', '08-Showroom'],
            ['SUCURSAL', '09-ecommerce'], ['SUCURSAL', '12-Dafiti']
        ];

        $insertSql = "INSERT INTO FP_SOLICITUDES_TRANSFERENCIAS_CONFIG (CATEGORIA, VALOR) VALUES (?, ?)";
        $stmt = $pdo->prepare($insertSql);
        foreach ($initialData as $row) {
            $stmt->execute($row);
        }
        echo "Tabla de configuración creada e inicializada correctamente.";
    } else {
        echo "La tabla de configuración ya existe.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
