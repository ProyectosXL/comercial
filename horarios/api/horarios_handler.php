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

// --- VALIDACIÓN PUNTO 1: BANCO DE HORAS (P vs D) - CORREGIDO ---
    
    // 1. Identificar vendedores que tienen horas 'D' en lo que se quiere guardar
    $vendedoresConDevolucion = [];
    foreach ($horarios as $h) {
        if ($h['tipo'] === 'devuelve') $vendedoresConDevolucion[$h['codVended']] = true;
    }

    if (!empty($vendedoresConDevolucion)) {
        foreach (array_keys($vendedoresConDevolucion) as $legajo) {
            
            // A. Calcular Saldo Histórico en Base de Datos (TODO MENOS la semana actual)
            $sqlSaldo = "
                SELECT 
                    SUM(CASE WHEN TIPO_HORA = 'permiso' THEN 1 ELSE 0 END) as TotalP,
                    SUM(CASE WHEN TIPO_HORA = 'devuelve' THEN 1 ELSE 0 END) as TotalD
                FROM FP_GESTION_HORARIOS
                WHERE COD_VENDED = ? 
                AND (FECHA < ? OR FECHA > ?) -- Excluye la semana que estamos guardando
            ";
            
            $stmtSaldo = sqlsrv_query($conn, $sqlSaldo, [$legajo, $fechaInicio, $fechaFin]);
            
            $historialP = 0; $historialD = 0;
            if ($stmtSaldo && $row = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)) {
                $historialP = intval($row['TotalP']);
                $historialD = intval($row['TotalD']);
            }
            if($stmtSaldo) sqlsrv_free_stmt($stmtSaldo);

            // B. Calcular lo Nuevo (Lo que el usuario acaba de dibujar en esta semana)
            $nuevoP = 0; $nuevoD = 0;
            foreach ($horarios as $h) {
                if ($h['codVended'] == $legajo) {
                    if ($h['tipo'] === 'permiso') $nuevoP++;
                    if ($h['tipo'] === 'devuelve') $nuevoD++;
                }
            }

            // C. Validación Matemática
            $totalPermisos = $historialP + $nuevoP;
            $totalDevoluciones = $historialD + $nuevoD;
            $saldoFinal = $totalPermisos - $totalDevoluciones;

            if ($saldoFinal < 0) {
                // Buscamos el nombre para el error...
                $connNombres = getConexionCorp();
                $nombreError = "Vendedor $legajo";
                $sqlNom = "SELECT NOMBRE, APELLIDO FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE = ?";
                $stmtNom = sqlsrv_query($connNombres, $sqlNom, [$legajo]);
                if ($stmtNom && $rowNom = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
                    $nombreError = utf8_encode(trim($rowNom['APELLIDO']) . ' ' . trim($rowNom['NOMBRE']));
                }
                if($stmtNom) sqlsrv_free_stmt($stmtNom);
                sqlsrv_close($connNombres);
                sqlsrv_close($conn);

                die(json_encode([
                    'status' => 'error',
                    'message' => "ERROR DE SALDO: <b>$nombreError</b> no puede devolver más horas de las que tiene acumuladas.<br><br>Total Permisos (P): $totalPermisos<br>Total Devoluciones (D): $totalDevoluciones<br><b>Saldo final: $saldoFinal horas.</b>"
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
    // PASO 1: CARGAR PROMOCIONES BANCARIAS
    // =================================================================================
    
    // 1. Obtener NOMBRE EXACTO de la sucursal en RRHH para traducir ID
    $connCorp = getConexionCorp(); 
    $sqlNomRRHH = "SELECT DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
    $stmtNom = sqlsrv_query($connCorp, $sqlNomRRHH, [$nroSucursal]);
    
    $nombreSucursal = "";
    if ($stmtNom && $row = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
        $nombreSucursal = trim($row['DESC_DEPARTAMENTO']); 
    }
    sqlsrv_free_stmt($stmtNom);
    sqlsrv_close($connCorp);

    // 2. Buscar promos usando el nombre
    $connPromos = getConexionCentral(); 
    $fIniSQL = date('Ymd', strtotime($fechaInicio));
    $fFinSQL = date('Ymd', strtotime($fechaFin));
    
    // Si no encontró nombre, usamos un comodín imposible
    $paramBusqueda = !empty($nombreSucursal) ? $nombreSucursal : "NO_ENCONTRADO";

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
            C.DESC_SUCURSAL = ?  -- Buscamos por nombre exacto (traducción manual)
            AND A.HABILITADO = 'S'
    ";

    $stmtPromociones = sqlsrv_query($connPromos, $sqlPromociones, [$paramBusqueda]);
    
    // Si falla exacto, probamos LIKE
    if ($stmtPromociones === false || !sqlsrv_has_rows($stmtPromociones)) {
        $sqlPromociones = str_replace("C.DESC_SUCURSAL =", "C.DESC_SUCURSAL LIKE", $sqlPromociones);
        $stmtPromociones = sqlsrv_query($connPromos, $sqlPromociones, ["%" . $paramBusqueda . "%"]);
    }
    
    $promocionesPorDia = [];
    if ($stmtPromociones) {
        $promocionesPotenciales = [];
        while ($row = sqlsrv_fetch_array($stmtPromociones, SQLSRV_FETCH_ASSOC)) {
            $promocionesPotenciales[] = $row;
        }
        
        $diasSemana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        $fechaActual = new DateTime($fechaInicio);
        $fechaFinDT = new DateTime($fechaFin);

        while ($fechaActual <= $fechaFinDT) {
            $fechaISO = $fechaActual->format('Y-m-d');
            $promosDelDia = []; 
            $columnaDia = 'APLICA_' . strtoupper($diasSemana[$fechaActual->format('w')]);
            $fechaCheck = clone $fechaActual;
            $fechaCheck->setTime(0,0,0);

            foreach ($promocionesPotenciales as $promo) {
                $desde = ($promo['DESFE_VIGENCIA'] instanceof DateTime) ? $promo['DESFE_VIGENCIA'] : new DateTime($promo['DESFE_VIGENCIA']);
                $hasta = ($promo['HASFE_VIGENCIA'] instanceof DateTime) ? $promo['HASFE_VIGENCIA'] : new DateTime($promo['HASFE_VIGENCIA']);
                $desde->setTime(0,0,0); $hasta->setTime(0,0,0);

                if ($fechaCheck >= $desde && $fechaCheck <= $hasta) {
                    $aplica = isset($promo[$columnaDia]) ? trim($promo[$columnaDia]) : 'N';
                    if ($aplica === 'S') {
                         $promosDelDia[] = trim($promo['DESC_PROMOCION']);
                    }
                }
            }
            $promocionesPorDia[$fechaISO] = array_values(array_unique($promosDelDia));
            $fechaActual->modify('+1 day');
        }
        sqlsrv_free_stmt($stmtPromociones);
    }
    $respuesta['promociones'] = $promocionesPorDia;
    sqlsrv_close($connPromos);

    // =================================================================================
    // PASO 2: CARGAR DATOS (SISTEMAS)
    // =================================================================================
    $connSistemas = getConexionSistemas();

    // A. Horarios Reales
    $sqlHorarios = "SELECT COD_VENDED, FECHA, HORA, TIPO_HORA FROM FP_GESTION_HORARIOS WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?"; 
    $stmtHorarios = sqlsrv_query($connSistemas, $sqlHorarios, [$nroSucursal, $fechaInicio, $fechaFin]); 
    $horarios = []; 
    $vendedoresConActividad = [];

    if ($stmtHorarios) {
        while ($row = sqlsrv_fetch_array($stmtHorarios, SQLSRV_FETCH_ASSOC)) { 
            $row['FECHA'] = $row['FECHA']->format('Y-m-d'); 
            $horarios[] = $row; 
            $vendedoresConActividad[trim($row['COD_VENDED'])] = true; 
        } 
        sqlsrv_free_stmt($stmtHorarios);
    }
    $respuesta['horarios'] = $horarios; 

    // B. Francos Reales
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

    // C. Notas y Feedback
    $sqlNotas = "SELECT COD_VENDED, FECHA, NOTA FROM FP_GESTION_NOTAS_VENDEDOR WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
    $stmtNotas = sqlsrv_query($connSistemas, $sqlNotas, [$nroSucursal, $fechaInicio, $fechaFin]);
    $notasVendedor = [];
    if ($stmtNotas) {
        while($row = sqlsrv_fetch_array($stmtNotas, SQLSRV_FETCH_ASSOC)) {
            $f = $row['FECHA']->format('Y-m-d');
            $id = trim($row['COD_VENDED']);
            if(!isset($notasVendedor[$id])) $notasVendedor[$id] = [];
            $notasVendedor[$id][$f] = $row['NOTA'];
        }
        sqlsrv_free_stmt($stmtNotas);
    }
    $respuesta['notasVendedor'] = $notasVendedor;

    $feedbackVendedor = [];
    $sqlFeed = "SELECT COD_VENDED, MENSAJE FROM FP_GESTION_FEEDBACK WHERE NRO_SUCURSAL = ? AND FECHA_SEMANA = ?";
    $stmtFeed = sqlsrv_query($connSistemas, $sqlFeed, [$nroSucursal, $fechaInicio]);
    if ($stmtFeed) {
        while ($row = sqlsrv_fetch_array($stmtFeed, SQLSRV_FETCH_ASSOC)) {
            $feedbackVendedor[trim($row['COD_VENDED'])] = $row['MENSAJE'];
        }
        sqlsrv_free_stmt($stmtFeed);
    }
    $respuesta['feedback'] = $feedbackVendedor;

    // D. Observaciones Semanales
    $sqlObs = "SELECT OBSERVACIONES FROM FP_GESTION_OBSERVACIONES WHERE NRO_SUCURSAL = ? AND FECHA_INICIO_SEMANA = ?"; 
    $stmtObs = sqlsrv_query($connSistemas, $sqlObs, [$nroSucursal, $fechaInicio]); 
    $respuesta['observaciones'] = '';
    if ($stmtObs && $rowObs = sqlsrv_fetch_array($stmtObs, SQLSRV_FETCH_ASSOC)) {
        $respuesta['observaciones'] = $rowObs['OBSERVACIONES'];
    }
    if($stmtObs) sqlsrv_free_stmt($stmtObs);

    // =================================================================================
    // PASO 3: LÓGICA AUTOMÁTICA DE PLANTILLAS BASE
    // =================================================================================
    // Si no hay horarios ni francos guardados, cargamos la plantilla
    if (empty($respuesta['horarios']) && empty($respuesta['francos'])) {
        
        // Calcular Semana A (Impar) o B (Par)
        $date = new DateTime($fechaInicio);
        $numSemana = intval($date->format('W'));
        $tipoPlantilla = ($numSemana % 2 != 0) ? 'A' : 'B'; 

        // Buscar Plantilla en BD
        $sqlBase = "SELECT COD_VENDED, DIA_SEMANA, HORA, TIPO_HORA, ES_FRANCO FROM FP_HORARIOS_BASE WHERE NRO_SUCURSAL = ? AND TIPO_SEMANA = ?";
        $stmtBase = sqlsrv_query($connSistemas, $sqlBase, [$nroSucursal, $tipoPlantilla]);
        
        if ($stmtBase) {
            while ($row = sqlsrv_fetch_array($stmtBase, SQLSRV_FETCH_ASSOC)) {
                $diaSemana = intval($row['DIA_SEMANA']); // 1=Lunes
                // Calcular fecha real: Lunes + (dia-1)
                $fechaReal = date('Y-m-d', strtotime($fechaInicio . ' + ' . ($diaSemana - 1) . ' days'));
                $id = trim($row['COD_VENDED']);

                if ($row['ES_FRANCO'] == 1) {
                    $respuesta['francos'][] = ['COD_VENDED' => $id, 'FECHA_FRANCO' => $fechaReal];
                } else {
                    $respuesta['horarios'][] = [
                        'COD_VENDED' => $id, 
                        'FECHA' => $fechaReal, 
                        'HORA' => $row['HORA'], 
                        'TIPO_HORA' => strtolower($row['TIPO_HORA'] ?: 'normal')
                    ];
                }
                
                // Agregamos a la lista de actividad para que se dibuje la fila
                $vendedoresConActividad[$id] = true;
            }
            sqlsrv_free_stmt($stmtBase);
            $respuesta['es_plantilla_base'] = true; 
        }
    }

    // Convertir keys a lista simple para el frontend
    $respuesta['vendedoresConActividad'] = array_keys($vendedoresConActividad);

    // =================================================================================
    // PASO 4: BLOQUEOS EXTERNOS (Cruce de Sucursales)
    // =================================================================================
    $bloqueosExternos = [];
    if (!empty($respuesta['vendedoresConActividad'])) {
        $listaVendedores = implode("','", $respuesta['vendedoresConActividad']);
        
        $sqlBloqueos = "
            SELECT COD_VENDED, FECHA, HORA, NRO_SUCURSAL
            FROM FP_GESTION_HORARIOS 
            WHERE FECHA BETWEEN ? AND ? 
            AND NRO_SUCURSAL <> ? 
            AND COD_VENDED IN ('$listaVendedores')
        ";
        
        $stmtBloq = sqlsrv_query($connSistemas, $sqlBloqueos, [$fechaInicio, $fechaFin, $nroSucursal]);
        if ($stmtBloq) {
            while ($row = sqlsrv_fetch_array($stmtBloq, SQLSRV_FETCH_ASSOC)) {
                $id = trim($row['COD_VENDED']);
                $fecha = $row['FECHA']->format('Y-m-d');
                $hora = intval($row['HORA']);
                $sucursalOrigen = $row['NRO_SUCURSAL'];
                
                if (!isset($bloqueosExternos[$id])) $bloqueosExternos[$id] = [];
                if (!isset($bloqueosExternos[$id][$fecha])) $bloqueosExternos[$id][$fecha] = [];
                
                $bloqueosExternos[$id][$fecha][$hora] = $sucursalOrigen;
            }
            sqlsrv_free_stmt($stmtBloq);
        }
    }
    $respuesta['bloqueosExternos'] = $bloqueosExternos;

    sqlsrv_close($connSistemas);

    // Salida Final
    echo json_encode($respuesta);
}