<?php
header('Content-Type: application/json');
require_once 'conexion.php';

// 0. CARGAR ENTRO
if (!isset($_ENV['QUICKPASS_ID'])) load_env(__DIR__ . '/../../.env');

$nroSucursal = $_GET['sucursal'] ?? 0;
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';

if (!$nroSucursal || !$fechaInicio || !$fechaFin) {
    die(json_encode(['error' => 'Faltan parámetros']));
}

// =========================================================
// 1. OBTENER PLANIFICADO (Usa COD_VENDEDOR, ej: 220)
// =========================================================
$conn = getConexionSistemas();
$sql = "SELECT COD_VENDED, FECHA, HORA FROM FP_GESTION_HORARIOS 
        WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
$stmt = sqlsrv_query($conn, $sql, [$nroSucursal, $fechaInicio, $fechaFin]);

$planificado = [];
$codigosVendedor = []; // Lista para buscar equivalencias

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $codVendedor = trim($row['COD_VENDED']); // Ej: "220"
    $fecha = $row['FECHA']->format('Y-m-d');
    $hora = intval($row['HORA']);
    
    if (!isset($planificado[$codVendedor])) $planificado[$codVendedor] = [];
    if (!isset($planificado[$codVendedor][$fecha])) $planificado[$codVendedor][$fecha] = [];
    
    $planificado[$codVendedor][$fecha][] = $hora;
    $codigosVendedor[$codVendedor] = true;
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// =========================================================
// 1.5. TRADUCTOR: OBTENER NOMBRE Y LEGAJO REAL
// =========================================================
$mapaLegajos = []; // Array [ '220' => 1367 ]
$mapaNombres = []; // Array [ '220' => 'DITTRICH DANIELA' ]

if (!empty($codigosVendedor)) {
    $connCentral = getConexionCentral();
    
    // Preparamos los IDs
    $idsParaQuery = array_keys($codigosVendedor);
    $placeholders = implode(',', array_fill(0, count($idsParaQuery), '?'));
    
    // CONSULTA MEJORADA: Trae Nombre (GVA23) y Legajo Real (RRHH)
    $sqlMapa = "
        SELECT 
            G.COD_VENDED, 
            G.NOMBRE_VEN,
            L.NRO_LEGAJO 
        FROM GVA23 G
        LEFT JOIN RO_T_LEGAJOS_PERSONAL L 
            ON G.COD_VENDED COLLATE Modern_Spanish_CI_AI = L.COD_VENDEDOR
        WHERE G.COD_VENDED IN ($placeholders)
    ";
    
    $paramsMapa = array_map('strval', $idsParaQuery);
    
    $stmtMapa = sqlsrv_query($connCentral, $sqlMapa, $paramsMapa);
    
if ($stmtMapa) {
        while ($row = sqlsrv_fetch_array($stmtMapa, SQLSRV_FETCH_ASSOC)) {
            $cVen = trim($row['COD_VENDED']);
            $nLeg = intval($row['NRO_LEGAJO']); 
            
            // --- CAMBIO AQUÍ: LIMPIEZA DE NOMBRE ---
            $nombreCrudo = trim($row['NOMBRE_VEN']); 
            
            // Usamos una Expresión Regular para borrar " - NUMERO" del final
            // Esto convierte "CABAÑA ROSAURA - 1869" en "CABAÑA ROSAURA"
            $nombreLimpio = preg_replace('/[\s-]*[0-9]+$/', '', $nombreCrudo);
            $nombreLimpio = trim($nombreLimpio); // Quitar espacios sobrantes

            $mapaLegajos[$cVen] = $nLeg;
            $mapaNombres[$cVen] = $nombreLimpio;
        }
        sqlsrv_free_stmt($stmtMapa);
    }
    sqlsrv_close($connCentral);
}

// =========================================================
// 2. OBTENER REAL (QUICKPASS API)
// =========================================================
$urlAPI = "https://apireporting.quickpassweb.com/HorasTrabajadas";

$fechaInicioObj = new DateTime($fechaInicio);
$fechaFinObj = new DateTime($fechaFin);

// Formato numérico YmdHi
$strDesde = $fechaInicioObj->format('YmdHi');
$strHasta = $fechaFinObj->format('YmdHi');

$queryString = "fechaDesde={$strDesde}&fechaHasta={$strHasta}&noContarImpares=false&mostrarSoloDosFichadas=true";
$fullUrl = $urlAPI . '?' . $queryString;

$headers = [
    'Content-Type: application/json',
    'idEmpresa: ' . $_ENV['QUICKPASS_ID'],
    'apiKey: ' . $_ENV['QUICKPASS_KEY']
];

$ch = curl_init($fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$fichadasReales = json_decode($response, true);

// =========================================================
// 3. PROCESAR Y CRUZAR
// =========================================================

$resultado = [];

foreach ($planificado as $codVendedor => $dias) {
    
    // Obtener datos reales para comparar y mostrar
    $legajoReal = isset($mapaLegajos[$codVendedor]) ? $mapaLegajos[$codVendedor] : intval($codVendedor);
    $nombreVendedor = isset($mapaNombres[$codVendedor]) ? $mapaNombres[$codVendedor] : "Vendedor $codVendedor";

    // Creamos la etiqueta visual: "NOMBRE - LEGAJO"
    $etiquetaVisual = "$nombreVendedor - $legajoReal";

    foreach ($dias as $fechaSQL => $horas) {
        if (empty($horas)) continue;
        
        $hMin = min($horas);
        $hMax = max($horas) + 1;
        $planEntrada = sprintf("%02d:00", $hMin);
        $planSalida = sprintf("%02d:00", $hMax);
        
        $realEntrada = "---";
        $realSalida = "---";
        $estado = "AUSENTE"; 
        $color = "table-danger"; 

        $fechaSQL_Compacta = str_replace('-', '', $fechaSQL);

        if ($fichadasReales && is_array($fichadasReales)) {
            foreach ($fichadasReales as $fichada) {
                
                $apiLegajo = isset($fichada['legajo']) ? intval($fichada['legajo']) : 0;
                $apiFecha = strval($fichada['fFecha'] ?? '');
                
                // Comparamos usando el LEGAJO REAL (1869)
                if ($apiLegajo === $legajoReal && $apiFecha === $fechaSQL_Compacta) {
                    
                    $movString = $fichada['movimientos'] ?? '';
                    
                    if (!empty($movString) && trim($movString) !== '') {
                        $partes = explode(' - ', $movString);
                        $realEntrada = trim($partes[0]); 
                        if (count($partes) > 1) {
                            $realSalida = trim($partes[1]);
                        }
                        $estado = "PRESENTE";
                        $color = "table-success";
                    } 
                    else if (isset($fichada['tieneFranco']) && $fichada['tieneFranco'] === true) {
                        $estado = "FRANCO QP";
                        $color = "table-secondary"; 
                        $realEntrada = "FRANCO";
                        $realSalida = "FRANCO";
                    }
                    break; 
                }
            }
        }

        $resultado[] = [
            'vendedor_visual' => $etiquetaVisual, // CAMPO NUEVO: Nombre - Legajo
            'fecha' => $fechaSQL,
            'plan_entrada' => $planEntrada,
            'plan_salida' => $planSalida,
            'real_entrada' => $realEntrada,
            'real_salida' => $realSalida,
            'estado' => $estado,
            'color' => $color
        ];
    }
}

echo json_encode([
    'cruce' => $resultado
]);
?>