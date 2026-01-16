<?php
header('Content-Type: application/json');
require_once 'conexion.php';

session_start();
$usuarioActual = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'SISTEMA';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'POST') {
    guardarDatos($usuarioActual);
} elseif ($metodo === 'GET') {
    // NUEVA LÓGICA: Distinguir entre cargar todo o solo verificar disponibilidad
    if (isset($_GET['accion']) && $_GET['accion'] === 'verificar_disponibilidad') {
        verificarDisponibilidad();
    } else {
        cargarDatos();
    }
}

function guardarDatos($usuario) {
    $input = json_decode(file_get_contents('php://input'), true);
    $conn = getConexionSistemas();

    // --- VALIDACIÓN DE CRUCE DE HORARIOS (Punto 4) ---
    // Antes de borrar nada, verificamos si estos empleados ya trabajan en OTRO local esos días
    $nroSucursal = $input['nroSucursal'];
    $fechaInicio = $input['fechaInicio'];
    $fechaFin = $input['fechaFin'];
    $horarios = $input['horarios'];

    // Obtenemos todos los vendedores que estamos intentando guardar
    $vendedoresInvolucrados = array_unique(array_column($horarios, 'codVended'));
    
    if (!empty($vendedoresInvolucrados)) {
        $placeholders = implode(',', array_fill(0, count($vendedoresInvolucrados), '?'));
        
        // 1. Traemos TODAS las horas ocupadas en OTRAS sucursales para esta gente en esta semana
        $sqlCheck = "SELECT COD_VENDED, FECHA, HORA, NRO_SUCURSAL 
                     FROM FP_GESTION_HORARIOS 
                     WHERE FECHA BETWEEN ? AND ? 
                     AND NRO_SUCURSAL <> ? 
                     AND COD_VENDED IN ($placeholders)";
        
        $paramsCheck = array_merge([$fechaInicio, $fechaFin, $nroSucursal], $vendedoresInvolucrados);
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);
        
        // 2. Construimos un mapa de ocupación: [Legajo][Fecha][Hora] = Sucursal
        $ocupacionExterna = [];
        if ($stmtCheck) {
            while ($row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
                $lid = trim($row['COD_VENDED']);
                $fec = $row['FECHA']->format('Y-m-d');
                $hr  = intval($row['HORA']);
                $suc = $row['NRO_SUCURSAL'];
                
                if (!isset($ocupacionExterna[$lid])) $ocupacionExterna[$lid] = [];
                if (!isset($ocupacionExterna[$lid][$fec])) $ocupacionExterna[$lid][$fec] = [];
                
                $ocupacionExterna[$lid][$fec][$hr] = $suc;
            }
            sqlsrv_free_stmt($stmtCheck);
        }

        // 3. Comparamos lo que queremos guardar contra el mapa de ocupación
        foreach ($horarios as $h) {
            $nuevoLegajo = trim($h['codVended']);
            $nuevaFecha = $h['fecha'];
            $nuevaHora = intval($h['hora']);

            // ¿Existe este slot ocupado en el mapa externo?
            if (isset($ocupacionExterna[$nuevoLegajo][$nuevaFecha][$nuevaHora])) {
                $sucursalConflicto = $ocupacionExterna[$nuevoLegajo][$nuevaFecha][$nuevaHora];
                $fechaFormateada = date('d/m/Y', strtotime($nuevaFecha));
                
                sqlsrv_close($conn);
                die(json_encode([
                    'status' => 'error', 
                    'message' => "ERROR CRÍTICO: El vendedor $nuevoLegajo ya tiene ocupada la hora $nuevaHora:00 del día $fechaFormateada en la sucursal $sucursalConflicto."
                ]));
            }
        }
    }
    // --- SI PASA LA VALIDACIÓN, GUARDAMOS ---
    if (sqlsrv_begin_transaction($conn) === false) {
        die(json_encode(['status' => 'error', 'message' => 'Error transacción.']));
    }

    // 1. Guardar Horarios
    $sqlDelete = "DELETE FROM FP_GESTION_HORARIOS WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
    sqlsrv_query($conn, $sqlDelete, [$nroSucursal, $fechaInicio, $fechaFin]);

    if (!empty($horarios)) {
        $sqlInsert = "INSERT INTO FP_GESTION_HORARIOS (NRO_SUCURSAL, COD_VENDED, FECHA, HORA, TIPO_HORA, MODIFICADO_POR) VALUES (?, ?, ?, ?, ?, ?)";
        foreach ($horarios as $h) {
            sqlsrv_query($conn, $sqlInsert, [$nroSucursal, $h['codVended'], $h['fecha'], $h['hora'], $h['tipo'], $usuario]);
        }
    }

    // 2. Guardar Francos
    $francos = $input['francos'];
    $vendedoresEnGrilla = $input['vendedoresEnGrilla'];
    if (!empty($vendedoresEnGrilla)) {
        $phV = implode(',', array_fill(0, count($vendedoresEnGrilla), '?'));
        $sqlDelF = "DELETE FROM FP_GESTION_FRANCOS WHERE FECHA_FRANCO BETWEEN ? AND ? AND COD_VENDED IN ($phV)";
        $paramsDelF = array_merge([$fechaInicio, $fechaFin], $vendedoresEnGrilla);
        sqlsrv_query($conn, $sqlDelF, $paramsDelF);
    }
    if (!empty($francos)) {
        $sqlInsF = "INSERT INTO FP_GESTION_FRANCOS (COD_VENDED, FECHA_FRANCO, MODIFICADO_POR) VALUES (?, ?, ?)";
        foreach ($francos as $f) sqlsrv_query($conn, $sqlInsF, [$f['codVended'], $f['fechaFranco'], $usuario]);
    }

    // 3. Guardar Notas por Vendedor (Punto 3)
    $notas = $input['notas']; // Array de {codVended, fecha, nota}
    
    // Borramos notas viejas de estos vendedores en estas fechas para esta sucursal
    $sqlDelNotas = "DELETE FROM FP_GESTION_NOTAS_VENDEDOR WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
    sqlsrv_query($conn, $sqlDelNotas, [$nroSucursal, $fechaInicio, $fechaFin]);

    if (!empty($notas)) {
        $sqlInsNota = "INSERT INTO FP_GESTION_NOTAS_VENDEDOR (NRO_SUCURSAL, COD_VENDED, FECHA, NOTA, MODIFICADO_POR) VALUES (?, ?, ?, ?, ?)";
        foreach ($notas as $n) {
            if (!empty(trim($n['nota']))) {
                sqlsrv_query($conn, $sqlInsNota, [$nroSucursal, $n['codVended'], $n['fecha'], $n['nota'], $usuario]);
            }
        }
    }
    
    // (Mantener guardado de Observaciones Semanales si quieres, pero borramos las diarias viejas)

    sqlsrv_commit($conn);
    echo json_encode(['status' => 'ok']);
    sqlsrv_close($conn);
}

function verificarDisponibilidad() {
    // Recibimos IDs por GET (ej: 1869,2150)
    $ids = $_GET['ids'] ?? '';
    $nroSucursal = $_GET['sucursal'] ?? 0;
    $fechaInicio = $_GET['fechaInicio'] ?? '';
    $fechaFin = $_GET['fechaFin'] ?? '';

    if (!$ids || !$nroSucursal || !$fechaInicio || !$fechaFin) {
        echo json_encode([]);
        return;
    }

    // Convertimos la lista "1,2,3" en array para SQL
    $listaIdsArray = explode(',', $ids);
    $listaIdsLimpia = implode("','", array_map('trim', $listaIdsArray));

    $conn = getConexionSistemas();
    
    // CAMBIO: Quitamos el GROUP BY para traer cada hora individualmente
    $sql = "
        SELECT COD_VENDED, FECHA, HORA, NRO_SUCURSAL
        FROM FP_GESTION_HORARIOS 
        WHERE FECHA BETWEEN ? AND ? 
        AND NRO_SUCURSAL <> ? 
        AND COD_VENDED IN ('$listaIdsLimpia')
    ";

    $stmt = sqlsrv_query($conn, $sql, [$fechaInicio, $fechaFin, $nroSucursal]);
    $bloqueos = [];

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $id = $row['COD_VENDED'];
            $fecha = $row['FECHA']->format('Y-m-d');
            $hora = intval($row['HORA']); // Traemos la hora exacta (ej: 7)
            $sucursal = $row['NRO_SUCURSAL'];
            
            if (!isset($bloqueos[$id])) $bloqueos[$id] = [];
            if (!isset($bloqueos[$id][$fecha])) $bloqueos[$id][$fecha] = [];
            
            // Guardamos: [ID][FECHA][HORA] = SUCURSAL
            $bloqueos[$id][$fecha][$hora] = $sucursal;
        }
        sqlsrv_free_stmt($stmt);
    }
    sqlsrv_close($conn);

    echo json_encode($bloqueos);
}

function cargarDatos() {
    // 1. Validar parámetros
    $nroSucursal = intval($_GET['sucursal'] ?? 0);
    $fechaInicio = $_GET['fechaInicio'] ?? '';
    $fechaFin = $_GET['fechaFin'] ?? '';

    if (!$nroSucursal || !$fechaInicio || !$fechaFin) {
        die(json_encode(['status' => 'error', 'message' => 'Faltan parámetros.']));
    }

    $respuesta = [];
// =================================================================================
    // PASO 1: CARGAR PROMOCIONES BANCARIAS (Corrección Fechas 2026)
    // =================================================================================
    
    $connPromos = getConexionCentral(); 
    $sucursalString = strval($nroSucursal); 

    // 1. CONVERSIÓN DE FECHAS A FORMATO ANSI (YYYYMMDD)
    // Esto es fundamental para que SQL Server reconozca fechas futuras correctamente
    // sin importar la configuración regional del servidor.
    $fIniSQL = date('Ymd', strtotime($fechaInicio));
    $fFinSQL = date('Ymd', strtotime($fechaFin));

    // 2. CONSULTA SQL
    $sqlPromociones = "
        SELECT 
            A.DESC_PROMOCION_TARJETA AS DESC_PROMOCION,
            A.APLICA_LUNES, A.APLICA_MARTES, A.APLICA_MIERCOLES, A.APLICA_JUEVES, 
            A.APLICA_VIERNES, A.APLICA_SABADO, A.APLICA_DOMINGO,
            A.DESFE_VIGENCIA, A.HASFE_VIGENCIA
        FROM 
            PROMOCION_TARJETA A
        INNER JOIN 
            CTA_PROMOCION_TARJETA_POR_SUCURSAL B ON A.ID_PROMOCION_TARJETA = B.ID_PROMOCION_TARJETA
        INNER JOIN 
            SUCURSAL C ON B.ID_SUCURSAL = C.ID_SUCURSAL
        WHERE
            C.NRO_SUCURSAL = ?
            AND A.DESFE_VIGENCIA <= ?  -- La promo debe haber empezado antes de que termine la semana
            AND A.HASFE_VIGENCIA >= ?  -- La promo debe terminar después de que empiece la semana
            AND A.HABILITADO = 'S'
    ";

    // 3. PARÁMETROS (Usamos las fechas formateadas)
    $paramsPromo = [$sucursalString, $fFinSQL, $fIniSQL];
    
    $stmtPromociones = sqlsrv_query($connPromos, $sqlPromociones, $paramsPromo);
    
    $promocionesPorDia = [];

    if ($stmtPromociones === false) {
        $respuesta['DEBUG_ERROR_PROMOS'] = sqlsrv_errors();
    } else {
        $promocionesPotenciales = [];
        while ($row = sqlsrv_fetch_array($stmtPromociones, SQLSRV_FETCH_ASSOC)) {
            $promocionesPotenciales[] = $row;
        }
        
        // Filtrado día por día en PHP
        $diasSemana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        $fechaActual = new DateTime($fechaInicio);
        $fechaFinDT = new DateTime($fechaFin);

        while ($fechaActual <= $fechaFinDT) {
            $fechaISO = $fechaActual->format('Y-m-d');
            $promocionesPorDia[$fechaISO] = [];
            $diaDeLaSemanaNum = $fechaActual->format('w');
            $columnaDia = 'APLICA_' . strtoupper($diasSemana[$diaDeLaSemanaNum]);

            foreach ($promocionesPotenciales as $promo) {
                // Convertir fechas SQL a Objetos PHP para comparar con precisión
                $desde = ($promo['DESFE_VIGENCIA'] instanceof DateTime) ? $promo['DESFE_VIGENCIA'] : new DateTime($promo['DESFE_VIGENCIA']);
                $hasta = ($promo['HASFE_VIGENCIA'] instanceof DateTime) ? $promo['HASFE_VIGENCIA'] : new DateTime($promo['HASFE_VIGENCIA']);
                
                // Normalizar horas a 00:00:00
                $desde->setTime(0,0,0);
                $hasta->setTime(0,0,0);
                $fechaCheck = clone $fechaActual;
                $fechaCheck->setTime(0,0,0);

                // Verificar si la fecha actual está dentro de la vigencia de la promo
                if ($fechaCheck >= $desde && $fechaCheck <= $hasta) {
                    $aplica = $promo[$columnaDia];
                    if (!empty($aplica) && ($aplica === 'S' || $aplica === 's' || $aplica == 1)) {
                         $promocionesPorDia[$fechaISO][] = $promo['DESC_PROMOCION'];
                    }
                }
            }
            $fechaActual->modify('+1 day');
        }
        sqlsrv_free_stmt($stmtPromociones);
    }
    
    $respuesta['promociones'] = $promocionesPorDia;
    sqlsrv_close($connPromos); // Cerramos la conexión central

    // =================================================================================
    // PASO 2: CARGAR DATOS GUARDADOS (AHORA SÍ)
    // =================================================================================
    $connSistemas = getConexionSistemas();

    // A. Horarios
    $sqlHorarios = "SELECT COD_VENDED, FECHA, HORA, TIPO_HORA FROM FP_GESTION_HORARIOS WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?"; 
    $stmtHorarios = sqlsrv_query($connSistemas, $sqlHorarios, [$nroSucursal, $fechaInicio, $fechaFin]); 
    $horarios = []; 
    $vendedoresConHorarios = [];
    if ($stmtHorarios) {
        while ($row = sqlsrv_fetch_array($stmtHorarios, SQLSRV_FETCH_ASSOC)) { 
            $row['FECHA'] = $row['FECHA']->format('Y-m-d'); 
            $horarios[] = $row; 
            $vendedoresConHorarios[$row['COD_VENDED']] = true; 
        } 
        sqlsrv_free_stmt($stmtHorarios);
    }
    $respuesta['horarios'] = $horarios; 

    // B. Francos
    $sqlFrancos = "SELECT COD_VENDED, FECHA_FRANCO FROM FP_GESTION_FRANCOS WHERE FECHA_FRANCO BETWEEN ? AND ?"; 
    $stmtFrancos = sqlsrv_query($connSistemas, $sqlFrancos, [$fechaInicio, $fechaFin]); 
    $francos = []; 
    if ($stmtFrancos) {
        while ($row = sqlsrv_fetch_array($stmtFrancos, SQLSRV_FETCH_ASSOC)) { 
            $row['FECHA_FRANCO'] = $row['FECHA_FRANCO']->format('Y-m-d'); 
            $francos[] = $row; 
        } 
        sqlsrv_free_stmt($stmtFrancos);
    }
    $respuesta['francos'] = $francos; 

    // C. Actividad (SOLO HORARIOS para no traer gente de otros locales)
    $vendedoresConActividad = array_keys($vendedoresConHorarios);
    $vendedoresConActividad = array_map('strval', $vendedoresConActividad);
    $respuesta['vendedoresConActividad'] = $vendedoresConActividad;

    // D. Observaciones Semanales
    $sqlObs = "SELECT OBSERVACIONES FROM FP_GESTION_OBSERVACIONES WHERE NRO_SUCURSAL = ? AND FECHA_INICIO_SEMANA = ?"; 
    $stmtObs = sqlsrv_query($connSistemas, $sqlObs, [$nroSucursal, $fechaInicio]); 
    $respuesta['observaciones'] = '';
    if ($stmtObs) {
        $rowObs = sqlsrv_fetch_array($stmtObs, SQLSRV_FETCH_ASSOC);
        $respuesta['observaciones'] = $rowObs ? $rowObs['OBSERVACIONES'] : '';
        sqlsrv_free_stmt($stmtObs);
    }

    // --- Cargar Notas Vendedor ---
    $sqlNotas = "SELECT COD_VENDED, FECHA, NOTA FROM FP_GESTION_NOTAS_VENDEDOR WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
    $stmtNotas = sqlsrv_query($connSistemas, $sqlNotas, [$nroSucursal, $fechaInicio, $fechaFin]);
    $notasVendedor = [];
    while($row = sqlsrv_fetch_array($stmtNotas, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['FECHA']->format('Y-m-d');
        $id = $row['COD_VENDED'];
        if(!isset($notasVendedor[$id])) $notasVendedor[$id] = [];
        $notasVendedor[$id][$fecha] = $row['NOTA'];
    }
    $respuesta['notasVendedor'] = $notasVendedor;
    sqlsrv_free_stmt($stmtNotas);

    // --- Cargar FEEDBACK de la semana ---
    $feedbackVendedor = [];
    $sqlFeed = "SELECT COD_VENDED, MENSAJE FROM FP_GESTION_FEEDBACK 
                WHERE NRO_SUCURSAL = ? AND FECHA_SEMANA = ?";
                
    $stmtFeed = sqlsrv_query($connSistemas, $sqlFeed, [$nroSucursal, $fechaInicio]);
    
    if ($stmtFeed) {
        while ($row = sqlsrv_fetch_array($stmtFeed, SQLSRV_FETCH_ASSOC)) {
            $feedbackVendedor[trim($row['COD_VENDED'])] = $row['MENSAJE'];
        }
        sqlsrv_free_stmt($stmtFeed);
    }
    
    $respuesta['feedback'] = $feedbackVendedor;

    // --- DETECTAR BLOQUEOS EXTERNOS ---
    // Buscamos si los vendedores de esta grilla tienen horas en OTRAS sucursales
    $bloqueosExternos = [];
    
    if (!empty($vendedoresConActividad)) {
        $listaVendedores = implode("','", $vendedoresConActividad);
        
        // CAMBIO: Quitamos el GROUP BY y seleccionamos la HORA
        $sqlBloqueos = "
            SELECT COD_VENDED, FECHA, HORA, NRO_SUCURSAL
            FROM FP_GESTION_HORARIOS 
            WHERE FECHA BETWEEN ? AND ? 
            AND NRO_SUCURSAL <> ?  -- Que no sea esta misma sucursal
            AND COD_VENDED IN ('$listaVendedores')
        ";
        
        $stmtBloq = sqlsrv_query($connSistemas, $sqlBloqueos, [$fechaInicio, $fechaFin, $nroSucursal]);
        
        if ($stmtBloq) {
            while ($row = sqlsrv_fetch_array($stmtBloq, SQLSRV_FETCH_ASSOC)) {
                $id = trim($row['COD_VENDED']);
                $fecha = $row['FECHA']->format('Y-m-d');
                $hora = intval($row['HORA']); // Importante: Hora exacta
                $sucursalOrigen = $row['NRO_SUCURSAL'];
                
                if (!isset($bloqueosExternos[$id])) $bloqueosExternos[$id] = [];
                if (!isset($bloqueosExternos[$id][$fecha])) $bloqueosExternos[$id][$fecha] = [];
                
                // Estructura: [ID][FECHA][HORA] = SUCURSAL
                $bloqueosExternos[$id][$fecha][$hora] = $sucursalOrigen;
            }
            sqlsrv_free_stmt($stmtBloq);
        }
    }
    
    $respuesta['bloqueosExternos'] = $bloqueosExternos;

    sqlsrv_close($connSistemas);

    // =================================================================================
    // ENVIAR RESPUESTA FINAL
    // =================================================================================
    echo json_encode($respuesta);

}