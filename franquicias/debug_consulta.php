<?php
// Archivo temporal para debug - verificar la subconsulta

// Cargar configuración del .env
function parseEnvFile($filePath) {
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $vars = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || empty($line)) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $vars[trim($key)] = trim($value);
        }
    }
    return $vars;
}

$envVars = parseEnvFile(__DIR__ . '/../.env');

// Conectar a LAKER_SA
$connectionInfo = array(
    "Database" => $envVars['DATABASE_CENTRAL'],
    "UID" => $envVars['USER'],
    "PWD" => $envVars['PASS']
);

$laker_conn = sqlsrv_connect($envVars['HOST_CENTRAL'], $connectionInfo);

if (!$laker_conn) {
    die("Error conectando a LAKER_SA: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>Debug de subconsulta</h2>";

// Ejecutar la subconsulta sola
$subQuery = "SELECT s.NRO_SUCURSAL FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS s WHERE s.CANAL = 'FRANQUICIAS' AND s.HABILITADO = 1 AND s.NRO_SUC_MADRE IS NULL ORDER BY s.NRO_SUCURSAL";

echo "<h3>Ejecutando subconsulta:</h3>";
echo "<pre>$subQuery</pre>";

$stmt = sqlsrv_query($laker_conn, $subQuery);
$sucursalesValidas = [];

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $sucursalesValidas[] = $row['NRO_SUCURSAL'];
    }
    sqlsrv_free_stmt($stmt);
} else {
    echo "<p style='color: red;'>Error en subconsulta: " . print_r(sqlsrv_errors(), true) . "</p>";
}

echo "<h3>Resultados:</h3>";
echo "<p><strong>Total sucursales válidas:</strong> " . count($sucursalesValidas) . "</p>";
echo "<p><strong>¿Está la 812?</strong> " . (in_array(812, $sucursalesValidas) ? "SÍ" : "NO") . "</p>";

if (in_array(812, $sucursalesValidas)) {
    echo "<p style='color: red; font-weight: bold;'>PROBLEMA IDENTIFICADO: La sucursal 812 SÍ está en la subconsulta, pero no debería estar según tu consulta manual.</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>CORRECTO: La sucursal 812 NO está en la subconsulta.</p>";
}

echo "<h3>Primeras 20 sucursales válidas:</h3>";
echo "<pre>";
for ($i = 0; $i < min(20, count($sucursalesValidas)); $i++) {
    echo $sucursalesValidas[$i] . "\n";
}
echo "</pre>";

// Verificar específicamente la 812
echo "<h3>Verificación específica de la sucursal 812:</h3>";
$check812 = "SELECT s.NRO_SUCURSAL, s.CANAL, s.HABILITADO, s.NRO_SUC_MADRE, s.DESCRIPCION FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS s WHERE s.NRO_SUCURSAL = 812";
$stmt812 = sqlsrv_query($laker_conn, $check812);

if ($stmt812) {
    echo "<table border='1'>";
    echo "<tr><th>NRO_SUCURSAL</th><th>CANAL</th><th>HABILITADO</th><th>NRO_SUC_MADRE</th><th>DESCRIPCION</th></tr>";
    while ($row = sqlsrv_fetch_array($stmt812, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['NRO_SUCURSAL'] . "</td>";
        echo "<td>" . $row['CANAL'] . "</td>";
        echo "<td>" . $row['HABILITADO'] . "</td>";
        echo "<td>" . ($row['NRO_SUC_MADRE'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['DESCRIPCION'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    sqlsrv_free_stmt($stmt812);
}

sqlsrv_close($laker_conn);
?>