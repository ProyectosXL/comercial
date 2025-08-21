<?php
$serverName = "SERVIDOR"; // Reemplaza con el nombre de tu servidor SQL Server
$database = "LAKER_SA"; // Reemplaza con el nombre de tu base de datos
$username = "sa"; // Reemplaza con tu usuario de SQL Server
$password = "Axoft1988"; // Reemplaza con tu contraseña de SQL Server

try {
    $conn = new PDO("sqlsrv:server=$serverName;database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>