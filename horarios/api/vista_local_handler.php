<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$nroSucursal = $_GET['sucursal'] ?? ''; 
$idVendedor = $_GET['vendedor'] ?? '';
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';

if ((!$nroSucursal && !$idVendedor) || !$fechaInicio || !$fechaFin) {
    die(json_encode([]));
}

// =========================================================
// PASO 1: CARGAR HORARIOS Y FRANCOS (Base Sistemas)
// =========================================================
$connSistemas = getConexionSistemas();

// A. Horarios
$sqlHorarios = "SELECT COD_VENDED, FECHA, HORA, TIPO_HORA, NRO_SUCURSAL FROM FP_GESTION_HORARIOS WHERE FECHA BETWEEN ? AND ?";
$paramsHorarios = [$fechaInicio, $fechaFin];

if (!empty($idVendedor)) {
    $sqlHorarios .= " AND COD_VENDED = ?";
    $paramsHorarios[] = $idVendedor;
} else {
    $sqlHorarios .= " AND NRO_SUCURSAL = ?";
    $paramsHorarios[] = $nroSucursal;
}

$stmtHorarios = sqlsrv_query($connSistemas, $sqlHorarios, $paramsHorarios);

$agenda = [];
$vendedoresIds = [];
$sucursalesInvolucradas = [];

// Inicializamos Horarios
if ($stmtHorarios) {
    while ($row = sqlsrv_fetch_array($stmtHorarios, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['FECHA']->format('Y-m-d');
        $id = trim($row['COD_VENDED']);
        $suc = intval($row['NRO_SUCURSAL']);
        
        $vendedoresIds[$id] = true;
        $sucursalesInvolucradas[$suc] = true;

        if (!isset($agenda[$fecha][$id])) {
            $agenda[$fecha][$id] = [
                'horas' => [], 
                'tipos' => [], 
                'nota' => '', 
                'sucursal' => $suc, 
                'es_franco' => false // Nuevo Flag
            ];
        }
        $agenda[$fecha][$id]['horas'][] = intval($row['HORA']);
        $agenda[$fecha][$id]['tipos'][] = $row['TIPO_HORA'];
    }
    sqlsrv_free_stmt($stmtHorarios);
}

// B. Francos (CORREGIDO PARA VISTA VENDEDOR)
// Buscamos francos para los vendedores que YA aparecieron en horarios (vendedoresIds)
// O si buscamos por un vendedor específico, traemos sus francos directamente.

$sqlFrancos = "SELECT COD_VENDED, FECHA_FRANCO FROM FP_GESTION_FRANCOS WHERE FECHA_FRANCO BETWEEN ? AND ?";
$paramsFrancos = [$fechaInicio, $fechaFin];

// Si hay filtro de vendedor, lo agregamos a la consulta SQL
if (!empty($idVendedor)) {
    $sqlFrancos .= " AND COD_VENDED = ?";
    $paramsFrancos[] = $idVendedor;
}

$stmtFrancos = sqlsrv_query($connSistemas, $sqlFrancos, $paramsFrancos);

if ($stmtFrancos) {
    while ($row = sqlsrv_fetch_array($stmtFrancos, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['FECHA_FRANCO']->format('Y-m-d');
        $id = trim($row['COD_VENDED']);
        
        // REGLA DE ORO: 
        // 1. Si buscamos por vendedor -> AGREGAR SIEMPRE
        // 2. Si buscamos por sucursal -> Solo agregar si el vendedor trabaja en esa sucursal esa semana (está en $vendedoresIds)
        
        $mostrarFranco = false;
        
        if (!empty($idVendedor)) {
            $mostrarFranco = true; // Si busco a Rosaura, quiero ver todos sus francos
        } elseif (isset($vendedoresIds[$id])) {
            $mostrarFranco = true; // Si estoy viendo Tortugas y Rosaura trabaja ahí, muestro su franco
        }

        if ($mostrarFranco) {
            // Si no existe la entrada del día, la creamos
            if (!isset($agenda[$fecha][$id])) {
                $agenda[$fecha][$id] = [
                    'horas' => [], 
                    'tipos' => [], 
                    'nota' => '', 
                    'sucursal' => 0, // Franco no tiene sucursal
                    'es_franco' => true
                ];
                $vendedoresIds[$id] = true; // Para buscar nombre después
            } else {
                // Si ya existía (ej: tenía nota pero no horario), marcamos que es franco
                $agenda[$fecha][$id]['es_franco'] = true;
            }
        }
    }
    sqlsrv_free_stmt($stmtFrancos);
}

// C. Notas
$sqlNotas = "SELECT COD_VENDED, FECHA, NOTA FROM FP_GESTION_NOTAS_VENDEDOR WHERE FECHA BETWEEN ? AND ?";
$paramsNotas = [$fechaInicio, $fechaFin];
if (!empty($idVendedor)) { $sqlNotas .= " AND COD_VENDED = ?"; $paramsNotas[] = $idVendedor; } 
else { $sqlNotas .= " AND NRO_SUCURSAL = ?"; $paramsNotas[] = $nroSucursal; }

$stmtNotas = sqlsrv_query($connSistemas, $sqlNotas, $paramsNotas);
if ($stmtNotas) {
    while ($row = sqlsrv_fetch_array($stmtNotas, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['FECHA']->format('Y-m-d');
        $id = trim($row['COD_VENDED']);
        if (isset($agenda[$fecha][$id])) {
            $agenda[$fecha][$id]['nota'] = $row['NOTA'];
        }
    }
    sqlsrv_free_stmt($stmtNotas);
}
sqlsrv_close($connSistemas);

// =========================================================
// PASO 2: CARGAR PROMOCIONES (Para las sucursales encontradas)
// =========================================================
$connPromos = getConexionCentral(); // O Locales, donde funcione la query
$mapaPromosPorSucursal = []; 

// Si filtramos por sucursal directo
if (!empty($nroSucursal)) $sucursalesInvolucradas[intval($nroSucursal)] = true;

foreach ($sucursalesInvolucradas as $sucID => $bool) {
    if ($sucID == 0) continue; // Ignorar si es 0 (franco huerfano)
    $sucString = strval($sucID);
    
    // Consulta Promos (La que ya sabemos que funciona)
    $sqlP = "
        SELECT 
            A.DESC_PROMOCION_TARJETA AS DESC_PROMOCION,
            A.APLICA_LUNES, A.APLICA_MARTES, A.APLICA_MIERCOLES, A.APLICA_JUEVES, 
            A.APLICA_VIERNES, A.APLICA_SABADO, A.APLICA_DOMINGO,
            A.DESFE_VIGENCIA, A.HASFE_VIGENCIA
        FROM PROMOCION_TARJETA A
        INNER JOIN CTA_PROMOCION_TARJETA_POR_SUCURSAL B ON A.ID_PROMOCION_TARJETA = B.ID_PROMOCION_TARJETA
        INNER JOIN SUCURSAL C ON B.ID_SUCURSAL = C.ID_SUCURSAL
        WHERE C.NRO_SUCURSAL = ? AND A.HABILITADO = 'S'
    ";
    
    $stmtP = sqlsrv_query($connPromos, $sqlP, [$sucString]);
    
    if ($stmtP) {
        $promosRaw = [];
        while($r = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC)) $promosRaw[] = $r;
        
        $diasSemana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        $fIter = new DateTime($fechaInicio);
        $fFinDT = new DateTime($fechaFin);

        while ($fIter <= $fFinDT) {
            $iso = $fIter->format('Y-m-d');
            $columnaDia = 'APLICA_' . strtoupper($diasSemana[$fIter->format('w')]);
            
            if (!isset($mapaPromosPorSucursal[$sucID])) $mapaPromosPorSucursal[$sucID] = [];
            if (!isset($mapaPromosPorSucursal[$sucID][$iso])) $mapaPromosPorSucursal[$sucID][$iso] = [];

            foreach ($promosRaw as $p) {
                $desde = ($p['DESFE_VIGENCIA'] instanceof DateTime) ? $p['DESFE_VIGENCIA'] : new DateTime($p['DESFE_VIGENCIA']);
                $hasta = ($p['HASFE_VIGENCIA'] instanceof DateTime) ? $p['HASFE_VIGENCIA'] : new DateTime($p['HASFE_VIGENCIA']);
                $desde->setTime(0,0,0); $hasta->setTime(0,0,0);
                $fCheck = clone $fIter; $fCheck->setTime(0,0,0);

                if ($fCheck >= $desde && $fCheck <= $hasta) {
                    $aplica = $p[$columnaDia];
                    if (!empty($aplica) && ($aplica === 'S' || $aplica === 's' || $aplica == 1)) {
                        $mapaPromosPorSucursal[$sucID][$iso][] = $p['DESC_PROMOCION'];
                    }
                }
            }
            $fIter->modify('+1 day');
        }
        sqlsrv_free_stmt($stmtP);
    }
}

// =========================================================
// PASO 3: NOMBRES VENDEDORES Y SUCURSALES
// =========================================================
$nombresMap = [];
$sucursalesMap = [];

// A. Nombres Vendedores (Base Central)
if (!empty($vendedoresIds)) {
    $listaIds = implode("','", array_keys($vendedoresIds));
    $sqlNombres = "SELECT COD_VENDED, NOMBRE_VEN FROM GVA23 WHERE COD_VENDED IN ('$listaIds')";
    $stmtNombres = sqlsrv_query($connPromos, $sqlNombres); // Reusamos connPromos (Central)
    if ($stmtNombres) {
        while ($row = sqlsrv_fetch_array($stmtNombres, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $nombreCrudo = trim($row['NOMBRE_VEN']);
            $nombresMap[$id] = trim(preg_replace('/[\s-]*[0-9]+$/', '', $nombreCrudo));
        }
        sqlsrv_free_stmt($stmtNombres);
    }
}

// B. Nombres Sucursales (Base Locales - SUCURSALES_LAKERS es la que tiene los nombres lindos)
// Usamos connLocales para esto
$connLocales = getConexionLocales();
if (!empty($sucursalesInvolucradas)) {
    $listaSucs = implode(",", array_keys($sucursalesInvolucradas));
    // OJO: Si la lista está vacía no ejecutamos
    if($listaSucs) {
        $sqlSucs = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM SUCURSALES_LAKERS WHERE NRO_SUCURSAL IN ($listaSucs)";
        $stmtSucs = sqlsrv_query($connLocales, $sqlSucs);
        if ($stmtSucs) {
            while ($row = sqlsrv_fetch_array($stmtSucs, SQLSRV_FETCH_ASSOC)) {
                $nro = intval($row['NRO_SUCURSAL']);
                $sucursalesMap[$nro] = trim($row['DESC_SUCURSAL']);
            }
            sqlsrv_free_stmt($stmtSucs);
        }
    }
}
sqlsrv_close($connLocales);
sqlsrv_close($connPromos); // Cerramos Central

// =========================================================
// PASO 4: ESTRUCTURA FINAL
// =========================================================
$respuesta = [];
$diasNombres = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

$fechaIter = new DateTime($fechaInicio);
$fechaFinDT = new DateTime($fechaFin);

while ($fechaIter <= $fechaFinDT) {
    $iso = $fechaIter->format('Y-m-d');
    $diaSemana = $diasNombres[$fechaIter->format('w')];
    $diaMes = $fechaIter->format('d/m');
    
    // Promos del día
    $promosDelDia = [];
    if (!empty($nroSucursal) && isset($mapaPromosPorSucursal[intval($nroSucursal)][$iso])) {
        $promosDelDia = $mapaPromosPorSucursal[intval($nroSucursal)][$iso];
    } else if (isset($agenda[$iso])) {
        foreach ($agenda[$iso] as $datos) {
            $sucTrabajo = $datos['sucursal'];
            if (isset($mapaPromosPorSucursal[$sucTrabajo][$iso])) {
                $promosDelDia = array_merge($promosDelDia, $mapaPromosPorSucursal[$sucTrabajo][$iso]);
            }
        }
    }
    $promosDelDia = array_unique($promosDelDia);

    $diaObj = [
        'fecha' => $iso,
        'titulo' => "$diaSemana $diaMes",
        'promociones' => array_values($promosDelDia),
        'personal' => []
    ];

    if (isset($agenda[$iso])) {
        foreach ($agenda[$iso] as $legajo => $datos) {
            
            // --- LÓGICA DE VISUALIZACIÓN ---
            $nombrePersona = $nombresMap[$legajo] ?? "Vendedor $legajo";
            
            // Nombre de Sucursal (Usamos el mapa, si no el número)
            $sucID = $datos['sucursal'];
            $nombreSucursal = $sucursalesMap[$sucID] ?? $sucID;
            
            // Si buscamos por Vendedor, mostramos dónde trabaja. Si es "Mi Local", no hace falta (o sí, por si es refuerzo)
            $nombreShow = "$nombrePersona";
            if (!empty($idVendedor) || $sucID != $nroSucursal) {
                // Solo mostrar sucursal si estoy viendo vista global o es refuerzo
                $nombreShow .= " ($nombreSucursal)";
            }

            // Horario vs Franco
            $horarioTexto = "";
            $claseBorde = 'border-success'; 

            if ($datos['es_franco']) {
                $horarioTexto = "FRANCO";
                $claseBorde = 'border-purple'; // Nueva clase para Franco
            } else {
                $horas = $datos['horas'];
                if (count($horas) > 0) {
                    sort($horas);
                    $entrada = min($horas);
                    $salida = max($horas) + 1;
                    $horarioTexto = sprintf("%02d:00 - %02d:00", $entrada, $salida);
                    
                    if (in_array('extra', $datos['tipos'])) $claseBorde = 'border-primary';
                    if (in_array('devuelve', $datos['tipos'])) $claseBorde = 'border-warning';
                } else {
                    // Raro: tiene datos pero no horas y no es franco
                    $horarioTexto = "-";
                }
            }

            $diaObj['personal'][] = [
                'legajo' => $legajo,
                'nombre' => $nombreShow,
                'horario' => $horarioTexto,
                'nota' => $datos['nota'],
                'clase_borde' => $claseBorde,
                'tiene_extra' => in_array('extra', $datos['tipos']),
                'tiene_devuelve' => in_array('devuelve', $datos['tipos']),
                'es_franco' => $datos['es_franco']
            ];
        }
        usort($diaObj['personal'], function($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
    }
    
    $respuesta[] = $diaObj;
    $fechaIter->modify('+1 day');
}

echo json_encode($respuesta);
?>